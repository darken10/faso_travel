<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Enums\MoyenPayment;
use App\Enums\SexeUser;
use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Events\PayementEffectuerEvent;
use App\Helper\TicketHelpers;
use App\Models\Finance\Caisse;
use App\Models\Ticket\AutrePersonne;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class VenteTicket extends Component
{
    public int $step = 1;

    // Step 1
    public ?string $voyage_instance_id = null;
    public string $type_ticket = '';
    public $prix = 0;
    public ?int $numero_chaise = null;

    // Step 2
    public string $client_nom = '';
    public string $client_prenom = '';
    public string $client_telephone = '';

    // Step 3
    public $montant_recu = 0;
    public $monnaie = 0;

    // Result
    public ?int $ticketVenduId = null;

    protected function rules(): array
    {
        return [
            'voyage_instance_id' => 'required|exists:voyage_instances,id',
            'type_ticket'        => 'required|in:' . implode(',', TypeTicket::values()),
            'client_nom'         => 'required|string|max:255',
            'client_prenom'      => 'required|string|max:255',
            'client_telephone'   => 'nullable|string|max:20',
            'montant_recu'       => 'required|numeric|min:0',
        ];
    }

    public function mount(): void
    {
        $this->type_ticket = TypeTicket::AllerSimple->value;
    }

    public function updatedVoyageInstanceId(): void
    {
        $this->numero_chaise = null; // le plan des sièges change selon le voyage
        $this->computePrix();
    }

    /** Sièges occupés (tickets non annulés) de l'instance sélectionnée. */
    public function occupiedSeats(): array
    {
        if (!$this->voyage_instance_id) {
            return [];
        }
        $instance = VoyageInstance::find($this->voyage_instance_id);
        if (!$instance) {
            return [];
        }
        return $instance->tickets()
            ->where('statut', '!=', StatutTicket::Annuler)
            ->pluck('numero_chaise')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    public function selectSeat(int $n): void
    {
        if (in_array($n, $this->occupiedSeats(), true)) {
            return; // siège déjà pris
        }
        $this->numero_chaise = $n;
    }

    public function updatedTypeTicket(): void
    {
        $this->computePrix();
    }

    private function computePrix(): void
    {
        if (!$this->voyage_instance_id) {
            $this->prix = 0;
            return;
        }
        $instance = VoyageInstance::find($this->voyage_instance_id);
        if (!$instance) {
            $this->prix = 0;
            return;
        }
        $type = TypeTicket::tryFrom($this->type_ticket) ?? TypeTicket::AllerSimple;
        $this->prix = $instance->getPrix($type);
    }

    public function nextStep(): void
    {
        if (! Caisse::sessionOuverte()) {
            $this->dispatch('toast', type: 'error', message: 'Vous devez ouvrir une caisse avant de pouvoir vendre un ticket.');
            return;
        }

        if ($this->step === 1) {
            $this->validateOnly('voyage_instance_id');
            $this->validateOnly('type_ticket');
            if (!$this->numero_chaise) {
                $this->addError('numero_chaise', 'Veuillez choisir une chaise.');
                return;
            }
        }
        if ($this->step === 2) {
            $this->validateOnly('client_nom');
            $this->validateOnly('client_prenom');
        }
        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function updatedMontantRecu(): void
    {
        $this->monnaie = max(0, (float) $this->montant_recu - (float) $this->prix);
    }

    public function vendreTicket(): void
    {
        $this->validate([
            'voyage_instance_id' => 'required|exists:voyage_instances,id',
            'type_ticket'        => 'required|in:' . implode(',', TypeTicket::values()),
            'client_nom'         => 'required|string|max:255',
            'client_prenom'      => 'required|string|max:255',
            'client_telephone'   => 'nullable|string|max:20',
            'montant_recu'       => 'required|numeric|min:0',
        ]);

        if (! Caisse::sessionOuverte()) {
            $this->dispatch('toast', type: 'error', message: 'Aucune caisse ouverte. Ouvrez une caisse avant de vendre un ticket.');
            $this->step = 1;
            return;
        }

        if ($this->montant_recu < $this->prix) {
            $this->addError('montant_recu', 'Le montant reçu est inférieur au prix du ticket.');
            return;
        }

        DB::beginTransaction();
        try {
            $autrePersonne = AutrePersonne::create([
                'first_name' => $this->client_nom,
                'last_name'  => $this->client_prenom,
                'sexe'       => SexeUser::Homme->value,
                'numero'     => !empty($this->client_telephone)
                    ? (int) preg_replace('/\D/', '', $this->client_telephone)
                    : null,
            ]);

            $voyageInstance = VoyageInstance::findOrFail($this->voyage_instance_id);

            // Vérifie qu'une chaise est choisie et toujours libre.
            if (!$this->numero_chaise) {
                DB::rollBack();
                $this->step = 1;
                $this->addError('numero_chaise', 'Veuillez choisir une chaise.');
                return;
            }

            $dejaPris = $voyageInstance->tickets()
                ->where('statut', '!=', StatutTicket::Annuler)
                ->where('numero_chaise', $this->numero_chaise)
                ->exists();

            if ($dejaPris) {
                DB::rollBack();
                $this->numero_chaise = null;
                $this->step = 1;
                $this->dispatch('toast', type: 'error', message: 'Cette chaise vient d\'être prise. Choisissez-en une autre.');
                return;
            }

            $typeTicket = TypeTicket::from($this->type_ticket);
            $prix = $voyageInstance->getPrix($typeTicket);
            $caisse = Caisse::sessionOuverte();

            $ticket = Ticket::create([
                'user_id'             => Auth::id(),
                'voyage_id'           => $voyageInstance->voyage_id,
                'voyage_instance_id'  => $voyageInstance->id,
                'date'                => $voyageInstance->date->format('Y-m-d'),
                'type'                => $typeTicket->value,
                'statut'              => StatutTicket::Payer->value,
                'numero_ticket'       => TicketHelpers::generateTicketNumber(),
                'numero_chaise'       => $this->numero_chaise,
                'code_sms'            => TicketHelpers::generateTicketCodeSms(),
                'code_qr'             => TicketHelpers::generateTicketCodeQr(),
                'is_my_ticket'        => false,
                'autre_personne_id'   => $autrePersonne->id,
                'a_bagage'            => false,
                'caisse_id'           => $caisse?->id,
            ]);

            Payement::create([
                'ticket_id'     => $ticket->id,
                'montant'       => $prix,
                'statut'        => StatutPayement::Complete->value,
                'moyen_payment' => MoyenPayment::ESPECE->value,
            ]);

            DB::commit();

            PayementEffectuerEvent::dispatch($ticket);
            $ticket->refresh();

            $this->ticketVenduId = $ticket->id;
            $this->monnaie = $this->montant_recu - $prix;
            $this->step = 4; // confirmation step

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    public function nouvelleVente(): void
    {
        $this->ticketVenduId = null;
        $this->prix = 0;
        $this->monnaie = 0;
        $this->montant_recu = 0;
        $this->voyage_instance_id = null;
        $this->client_nom = '';
        $this->client_prenom = '';
        $this->client_telephone = '';
        $this->type_ticket = TypeTicket::AllerSimple->value;
        $this->step = 1;
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $instances = VoyageInstance::query()
            ->whereHas('voyage', fn ($q) => $q->withoutGlobalScopes()->where('compagnie_id', $compagnieId))
            ->avenir()
            ->get()
            ->filter(fn ($i) => count($i->chaiseDispo()) > 0);

        $caisse = Caisse::sessionOuverte();
        $ticketVendu = $this->ticketVenduId ? Ticket::find($this->ticketVenduId) : null;
        $typeTickets = TypeTicket::cases();

        return view('livewire.compagnie.ticket.vente-ticket', compact('instances', 'caisse', 'ticketVendu', 'typeTickets'));
    }
}
