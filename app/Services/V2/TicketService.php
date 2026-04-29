<?php

namespace App\Services\V2;

use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Ticket\AutrePersonne;
use App\Models\Voyage\VoyageInstance;
use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketService
{
    /**
     * Get all tickets for the authenticated user
     *
     * @return Collection
     */
    public function getUserTickets(): Collection
    {
        return Ticket::with(['voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get ticket details by ID
     *
     * @param string $ticketId
     * @return Ticket
     */
    public function getUserTicketDetails(string $ticketId): Ticket
    {
        return Ticket::with(['voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne'])
            ->where('id', $ticketId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Create a new ticket
     *
     * @param array $data
     * @return Ticket
     */
    public function createTicket(array $data): Ticket
    {
        // Vérifier que l'instance de voyage existe
        $voyageInstance = VoyageInstance::with('care')->findOrFail($data['voyage_instance_id']);
        
        // Vérifier la disponibilité des places
        $placesOccupees = Ticket::where('voyage_instance_id', $voyageInstance->id)
            ->where('statut', '!=', StatutTicket::Annuler)
            ->count();
            
        if ($placesOccupees >= ($voyageInstance->care?->number_place ?? 50)) {
            throw new \Exception('Plus de places disponibles pour ce voyage');
        }
        
        // Créer le ticket
        $ticket = new Ticket();
        $ticket->voyage_instance_id = $data['voyage_instance_id'];
        $ticket->user_id = Auth::id();
        $ticket->type = $data['type'] ?? TypeTicket::Normal;
        $ticket->statut = StatutTicket::Payer; // Supposons que le paiement est déjà effectué
        $ticket->code_qr = Str::uuid()->toString();
        $ticket->numero_place = $this->attribuerNumeroPlace($voyageInstance->id);
        
        // Si le ticket est pour une autre personne
        if (isset($data['autre_personne']) && $data['autre_personne']) {
            $autrePersonne = new AutrePersonne();
            $autrePersonne->nom = $data['nom_autre_personne'];
            $autrePersonne->prenom = $data['prenom_autre_personne'];
            $autrePersonne->telephone = $data['telephone_autre_personne'];
            $autrePersonne->save();
            
            $ticket->autre_personne_id = $autrePersonne->id;
        }
        
        $ticket->save();
        
        return $ticket->load(['voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne']);
    }

    /**
     * Cancel a ticket
     *
     * @param string $ticketId
     * @return Ticket
     */
    public function cancelTicket(string $ticketId): Ticket
    {
        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        // Vérifier que le ticket n'est pas déjà annulé ou validé
        if ($ticket->statut === StatutTicket::Annuler) {
            throw new \Exception('Ce ticket est déjà annulé');
        }
        
        if ($ticket->statut === StatutTicket::Valider) {
            throw new \Exception('Ce ticket a déjà été validé et ne peut plus être annulé');
        }
        
        // Vérifier que le voyage n'a pas déjà commencé
        $voyageInstance = $ticket->voyageInstance;
        $departTime = Carbon::parse($voyageInstance->date);
        
        if ($departTime->isPast()) {
            throw new \Exception('Le voyage a déjà commencé, vous ne pouvez plus annuler ce ticket');
        }
        
        // Annuler le ticket
        $ticket->statut = StatutTicket::Annuler;
        $ticket->save();
        
        // Ici, on pourrait implémenter la logique de remboursement
        
        return $ticket;
    }

    /**
     * Transfer a ticket to another user
     *
     * @param string $ticketId
     * @param array $data
     * @return Ticket
     */
    public function transferTicket(string $ticketId, array $data): Ticket
    {
        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        // Vérifier que le ticket n'est pas déjà annulé ou validé
        if ($ticket->statut === StatutTicket::Annuler) {
            throw new \Exception('Ce ticket est annulé et ne peut pas être transféré');
        }
        
        if ($ticket->statut === StatutTicket::Valider) {
            throw new \Exception('Ce ticket a déjà été validé et ne peut plus être transféré');
        }
        
        // Vérifier que le voyage n'a pas déjà commencé
        $voyageInstance = $ticket->voyageInstance;
        $departTime = Carbon::parse($voyageInstance->date);
        
        if ($departTime->isPast()) {
            throw new \Exception('Le voyage a déjà commencé, vous ne pouvez plus transférer ce ticket');
        }
        
        // Créer ou mettre à jour les informations de l'autre personne
        if ($ticket->autre_personne_id) {
            $autrePersonne = AutrePersonne::find($ticket->autre_personne_id);
            $autrePersonne->nom = $data['nom'];
            $autrePersonne->prenom = $data['prenom'];
            $autrePersonne->telephone = $data['telephone'];
            $autrePersonne->save();
        } else {
            $autrePersonne = new AutrePersonne();
            $autrePersonne->nom = $data['nom'];
            $autrePersonne->prenom = $data['prenom'];
            $autrePersonne->telephone = $data['telephone'];
            $autrePersonne->save();
            
            $ticket->autre_personne_id = $autrePersonne->id;
            $ticket->save();
        }
        
        return $ticket->load(['voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'autre_personne']);
    }

    /**
     * Get QR code for a ticket
     *
     * @param string $ticketId
     * @return string Base64 encoded QR code image
     */
    public function getTicketQrCode(string $ticketId): string
    {
        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        // Générer le QR code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->generate($ticket->code_qr);
            
        return base64_encode($qrCode);
    }

    /**
     * Attribuer un numéro de place disponible
     *
     * @param string $voyageInstanceId
     * @return int
     */
    private function attribuerNumeroPlace(string $voyageInstanceId): int
    {
        $placesOccupees = Ticket::where('voyage_instance_id', $voyageInstanceId)
            ->where('statut', '!=', StatutTicket::Annuler)
            ->pluck('numero_place')
            ->toArray();
            
        $voyageInstance = VoyageInstance::with('care')->findOrFail($voyageInstanceId);
        $nombrePlaces = $voyageInstance->care?->number_place ?? 50;
        
        for ($i = 1; $i <= $nombrePlaces; $i++) {
            if (!in_array($i, $placesOccupees)) {
                return $i;
            }
        }
        
        throw new \Exception('Plus de places disponibles pour ce voyage');
    }
}
