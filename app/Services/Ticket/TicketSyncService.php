<?php

namespace App\Services\Ticket;

use App\Enums\StatutTicket;
use App\Enums\SyncActionType;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketValidation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rejeu idempotent des opérations réalisées hors connexion par l'application agent.
 *
 * Le contrat tient en une phrase : une opération identifiée par son operation_id
 * n'est exécutée qu'une seule fois, quel que soit le nombre d'envois. C'est
 * indispensable parce qu'un téléphone réessaie après un timeout sans savoir si le
 * serveur avait reçu — et parce que rejouer une validation d'aller-retour
 * consommerait le trajet retour.
 */
class TicketSyncService
{
    public function __construct(
        protected TicketValidationService $validationService,
    ) {}

    /**
     * Applique une opération et renvoie son journal.
     *
     * @param  array{operation_id:string,type:string,ticket_id:int,device_id?:string|null,method?:string|null,verified_offline?:bool,client_created_at?:string|null,voyage_instance_id?:string|null}  $action
     */
    public function apply(array $action, User $agent): SyncOutcome
    {
        $operationId = $action['operation_id'];

        // Déjà traitée lors d'un envoi précédent : on renvoie le verdict d'origine
        // sans rien réexécuter. C'est le cœur de l'idempotence.
        $existing = TicketValidation::where('operation_id', $operationId)->first();
        if ($existing) {
            return SyncOutcome::replayed($existing);
        }

        try {
            return SyncOutcome::applied(DB::transaction(fn () => $this->execute($action, $agent)));
        } catch (UniqueConstraintViolationException) {
            // Course entre deux envois concurrents de la même opération. La
            // transaction de ce fil a été annulée en totalité — y compris l'effet
            // métier — donc l'opération n'a bien été appliquée qu'une fois.
            return SyncOutcome::replayed(
                TicketValidation::where('operation_id', $operationId)->firstOrFail(),
            );
        }
    }

    /**
     * Exécute l'effet métier puis journalise, dans une même transaction.
     *
     * Si l'insertion du journal échoue (opération concurrente), l'effet métier est
     * annulé avec elle : aucun ticket ne peut être consommé sans trace.
     */
    private function execute(array $action, User $agent): TicketValidation
    {
        $type   = SyncActionType::from($action['type']);
        $ticket = $this->findTicket($action, $agent);

        if (! $ticket) {
            return $this->journal($action, $agent, null, SyncResult::Rejected, SyncErrorCode::NotFound);
        }

        // L'agent annonce le voyage qu'il embarque : un ticket d'un autre voyage
        // est un conflit, pas une validation. Le contrôle en ligne ne le faisait pas.
        $announced = $action['voyage_instance_id'] ?? null;
        if ($announced && (string) $ticket->voyage_instance_id !== (string) $announced) {
            return $this->journal($action, $agent, $ticket, SyncResult::Rejected, SyncErrorCode::WrongVoyage);
        }

        return match ($type) {
            SyncActionType::ValidateTicket => $this->applyValidate($action, $agent, $ticket),
            SyncActionType::PauseTicket    => $this->applyGuarded($action, $agent, $ticket,
                [StatutTicket::Payer],
                fn () => $this->validationService->pause($ticket)),
            SyncActionType::BlockTicket    => $this->applyGuarded($action, $agent, $ticket,
                [StatutTicket::Payer, StatutTicket::Pause],
                fn () => $this->validationService->block($ticket)),
            // Constat de non-présentation : aucune transition de statut n'existe
            // pour l'instant côté ticket, l'opération n'est donc que journalisée.
            SyncActionType::MarkAbsent     => $this->journal($action, $agent, $ticket, SyncResult::Applied),
        };
    }

    /**
     * Pause et blocage ne s'appliquent qu'à un ticket encore en circulation.
     *
     * L'opération a été décidée hors ligne, sur un état parfois ancien de plusieurs
     * jours. Sans ce garde, un « bloquer » rejoué tardivement écrasait un ticket
     * entre-temps embarqué ou annulé ailleurs, et effaçait cet état.
     *
     * @param  list<StatutTicket>  $allowedFrom
     * @param  callable():bool  $transition
     */
    private function applyGuarded(
        array $action,
        User $agent,
        Ticket $ticket,
        array $allowedFrom,
        callable $transition,
    ): TicketValidation {
        if (! in_array($ticket->statut, $allowedFrom, true)) {
            return $this->journal($action, $agent, $ticket, SyncResult::Rejected, SyncErrorCode::InvalidStatus);
        }

        return $this->applyTransition($action, $agent, $ticket, $transition);
    }

    private function applyValidate(array $action, User $agent, Ticket $ticket): TicketValidation
    {
        // Payer = aller simple à valider ; Pause = retour d'un aller-retour dont
        // l'aller a déjà été consommé.
        if (! in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause], true)) {
            $code = $ticket->statut === StatutTicket::Valider
                ? SyncErrorCode::AlreadyValidated   // conflit : un autre agent a embarqué ce passager
                : SyncErrorCode::InvalidStatus;     // annulé, suspendu, remboursé...

            return $this->journal($action, $agent, $ticket, SyncResult::Rejected, $code);
        }

        return $this->applyTransition($action, $agent, $ticket,
            fn () => $this->validationService->validate($ticket));
    }

    /**
     * @param  callable():bool  $transition
     *
     * Une exception n'est volontairement PAS journalisée. Le journal sert de clé
     * d'idempotence : y inscrire une panne transitoire (base indisponible,
     * deadlock) la figerait en refus définitif, rejoué à l'identique à chaque
     * retry. On laisse l'exception annuler la transaction — effet métier compris —
     * et remonter : le client recevra SERVER_ERROR et réessaiera.
     */
    private function applyTransition(array $action, User $agent, Ticket $ticket, callable $transition): TicketValidation
    {
        $ok = $transition();

        return $ok
            ? $this->journal($action, $agent, $ticket, SyncResult::Applied)
            : $this->journal($action, $agent, $ticket, SyncResult::Rejected, SyncErrorCode::InvalidStatus);
    }

    /**
     * Ticket appartenant à un voyage de la compagnie de l'agent, ou null.
     *
     * L'identifiant prime. Le QR sert quand le téléphone n'avait pas le ticket
     * dans son cache et a dû être confirmé à l'aveugle : il n'a alors que le code
     * scanné. Dans les deux cas la recherche reste bornée à la compagnie.
     */
    private function findTicket(array $action, User $agent): ?Ticket
    {
        if ($agent->compagnie_id === null) {
            return null;
        }

        $query = Ticket::ofCompagnie((int) $agent->compagnie_id);

        if (! empty($action['ticket_id'])) {
            return $query->find((int) $action['ticket_id']);
        }

        if (! empty($action['qr_code'])) {
            return $query->where('code_qr', $action['qr_code'])->first();
        }

        return null;
    }

    private function journal(
        array $action,
        User $agent,
        ?Ticket $ticket,
        SyncResult $result,
        ?SyncErrorCode $errorCode = null,
    ): TicketValidation {
        return TicketValidation::create([
            'operation_id'       => $action['operation_id'],
            'ticket_id'           => $ticket?->id,
            'requested_ticket_id' => ! empty($action['ticket_id']) ? (int) $action['ticket_id'] : null,
            // Jamais le QR en clair : c'est le secret du billet.
            'requested_qr_hash'   => ! empty($action['qr_code']) ? hash('sha256', $action['qr_code']) : null,
            'voyage_instance_id' => $ticket?->voyage_instance_id ?? ($action['voyage_instance_id'] ?? null),
            'agent_id'           => $agent->id,
            'device_id'          => $action['device_id'] ?? null,
            'action'             => $action['type'],
            'method'             => $action['method'] ?? null,
            'verified_offline'   => $action['verified_offline'] ?? true,
            // L'horodatage du terrain fait foi. À défaut, l'instant de la synchro.
            'client_created_at'  => isset($action['client_created_at'])
                ? Carbon::parse($action['client_created_at'])
                : now(),
            'result'             => $result,
            'error_code'         => $errorCode,
        ]);
    }
}
