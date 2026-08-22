<?php

namespace App\Services\Ticket;

use App\Enums\CompagnieSettingKey;
use App\Enums\RappelDepart;
use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use App\Notifications\Ticket\BonVoyageNotification;
use App\Notifications\Ticket\DepartureReminderNotification;
use App\Services\Compagnie\CompagnieSettingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rappels de départ et message de bon voyage.
 *
 * Les paliers sont fixes — la veille, peu avant le départ, à l'embarquement —
 * mais chaque compagnie active ceux qu'elle veut et règle leur avance de tir
 * depuis le panel (groupe « Notifications »). Une compagnie peut ainsi ne garder
 * que le rappel de la veille, ou au contraire l'ignorer pour ne prévenir qu'au
 * moment de l'embarquement.
 *
 * Aucun schéma dédié n'est nécessaire : le canal `database` enregistre déjà
 * chaque envoi dans la table `notifications`, ce qui suffit à ne jamais envoyer
 * deux fois le même palier à un même passager.
 */
class RappelDepartService
{
    /** Au-delà, un envoi manqué relève d'un incident passé qu'on ne rattrape pas. */
    private const FENETRE_RATTRAPAGE_HEURES = 2;

    public function __construct(private readonly CompagnieSettingService $settings) {}

    /**
     * Envoie les rappels dus pour un palier donné.
     *
     * @return array{notifies: int, departs: int}
     */
    public function envoyerPalier(RappelDepart $palier): array
    {
        $instances = $this->instancesCandidates();
        $dejaEnvoyes = $this->ticketsDejaNotifies(DepartureReminderNotification::class, $palier->value);

        $notifies = 0;
        $departs = 0;

        foreach ($instances as $instance) {
            if (! $this->palierEstDu($palier, $instance)) {
                continue;
            }

            $concernes = $instance->tickets
                ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
                ->reject(fn (Ticket $ticket) => $dejaEnvoyes->has((string) $ticket->id));

            if ($concernes->isEmpty()) {
                continue;
            }

            foreach ($concernes as $ticket) {
                try {
                    $ticket->user?->notify(new DepartureReminderNotification($ticket, $instance, $palier));
                    $notifies++;
                } catch (\Throwable $e) {
                    Log::warning("[rappel:{$palier->value}] ticket {$ticket->id} : {$e->getMessage()}");
                }
            }

            $departs++;
        }

        return ['notifies' => $notifies, 'departs' => $departs];
    }

    /**
     * Souhaite bon voyage aux passagers embarqués depuis assez longtemps.
     *
     * @return array{notifies: int}
     */
    public function envoyerBonVoyage(): array
    {
        $dejaEnvoyes = $this->ticketsDejaNotifies(BonVoyageNotification::class);
        $notifies = 0;

        foreach ($this->ticketsEmbarquesRecents() as $ticket) {
            if ($dejaEnvoyes->has((string) $ticket->id) || ! $this->bonVoyageEstDu($ticket)) {
                continue;
            }

            try {
                $ticket->user?->notify(new BonVoyageNotification($ticket));
                $notifies++;
            } catch (\Throwable $e) {
                Log::warning("[bon-voyage] ticket {$ticket->id} : {$e->getMessage()}");
            }
        }

        return ['notifies' => $notifies];
    }

    /** Le palier doit-il partir maintenant pour ce départ ? */
    public function palierEstDu(RappelDepart $palier, VoyageInstance $instance): bool
    {
        if (! $palier->concerne($instance)) {
            return false;
        }

        $compagnieId = $instance->voyage?->compagnie_id;

        // Palier désactivé par la compagnie : rien ne part.
        if (! $compagnieId || ! $this->settings->get($compagnieId, $palier->cleActivation())) {
            return false;
        }

        $depart = $this->departAt($instance);

        if (! $depart) {
            return false;
        }

        $moment = $this->momentEnvoi($palier, $depart, $compagnieId);

        // Dû dès l'heure atteinte, et rattrapable un court moment : un
        // planificateur arrêté deux heures ne doit pas déclencher un rappel de
        // la veille pour un voyage déjà parti.
        return $moment->isPast()
            && $moment->copy()->addHours(self::FENETRE_RATTRAPAGE_HEURES)->isFuture();
    }

    /** Heure à laquelle ce palier doit partir. */
    public function momentEnvoi(RappelDepart $palier, Carbon $depart, int $compagnieId): Carbon
    {
        $cle = $palier->cleDelai();

        if (! $cle) {
            return $depart->copy();
        }

        $valeur = (int) $this->settings->get($compagnieId, $cle);

        return $cle === CompagnieSettingKey::RAPPEL_VEILLE_HEURES
            ? $depart->copy()->subHours($valeur)
            : $depart->copy()->subMinutes($valeur);
    }

    /** Le message de bon voyage est-il dû pour ce billet ? */
    public function bonVoyageEstDu(Ticket $ticket): bool
    {
        if (! $ticket->valider_at) {
            return false;
        }

        $compagnieId = $ticket->voyageInstance?->voyage?->compagnie_id;

        if (! $compagnieId || ! $this->settings->get($compagnieId, CompagnieSettingKey::BON_VOYAGE_ACTIF)) {
            return false;
        }

        $delai = (int) $this->settings->get($compagnieId, CompagnieSettingKey::BON_VOYAGE_DELAI_MINUTES);

        return Carbon::parse($ticket->valider_at)->addMinutes($delai)->isPast();
    }

    /** Date et heure de départ effectives d'une instance. */
    public function departAt(VoyageInstance $instance): ?Carbon
    {
        if (! $instance->date) {
            return null;
        }

        $heure = $instance->heure ? Carbon::parse($instance->heure)->format('H:i:s') : '00:00:00';

        return Carbon::parse(Carbon::parse($instance->date)->toDateString().' '.$heure);
    }

    /**
     * Billets ayant déjà reçu cette notification, lus dans la trace laissée par
     * le canal `database`.
     *
     * Une seule requête sert tout le lot : interroger le journal billet par
     * billet coûterait une requête par passager à chaque passage du planificateur.
     *
     * @return Collection<string, true>
     */
    private function ticketsDejaNotifies(string $notification, ?string $palier = null): Collection
    {
        $lignes = DB::table('notifications')
            ->where('type', $notification)
            ->where('created_at', '>=', now()->subDays(9))
            ->when($palier, fn ($q) => $q->where('data', 'like', '%"palier":"'.$palier.'"%'))
            ->pluck('data');

        return $lignes
            ->map(fn ($data) => json_decode((string) $data, true)['ticket_id'] ?? null)
            ->filter()
            ->flip()
            ->map(fn () => true);
    }

    /**
     * Départs non annulés dans la fenêtre couverte par les rappels, le réglage
     * de chaque compagnie venant ensuite affiner.
     *
     * @return Collection<int, VoyageInstance>
     */
    private function instancesCandidates(): Collection
    {
        $departAt = "STR_TO_DATE(CONCAT(date, ' ', TIME_FORMAT(COALESCE(heure, '00:00:00'), '%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        return VoyageInstance::query()
            ->where('statut', '!=', StatutVoyageInstance::ANNULE)
            ->whereRaw("{$departAt} BETWEEN DATE_SUB(NOW(), INTERVAL 3 HOUR) AND DATE_ADD(NOW(), INTERVAL 8 DAY)")
            ->with([
                'voyage.compagnie',
                'voyage.trajet.depart',
                'voyage.trajet.arriver',
                'voyage.gareDepart',
                'tickets.user',
            ])
            ->get();
    }

    /**
     * Billets scannés à l'embarquement récemment.
     *
     * @return Collection<int, Ticket>
     */
    private function ticketsEmbarquesRecents(): Collection
    {
        return Ticket::query()
            ->where('statut', StatutTicket::Valider)
            ->whereNotNull('valider_at')
            // Au-delà de deux jours, souhaiter « bon voyage » n'a plus de sens.
            ->where('valider_at', '>=', now()->subDays(2))
            ->with([
                'user',
                'voyageInstance.voyage.compagnie',
                'voyageInstance.voyage.trajet.depart',
                'voyageInstance.voyage.trajet.arriver',
            ])
            ->get();
    }
}
