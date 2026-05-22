<?php

namespace App\Services\ticket;

use App\Enums\TypeTicket;
use App\Enums\StatutTicket;
use App\Helper\TicketHelpers;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket\AutrePersonne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator as FacadesValidator;


class TicketService
{

    public function create($voyageInstaceId,$data = null,bool $isMine = true)
    {
        $oldeTicket = Ticket::where('voyage_instance_id',$voyageInstaceId)
                        ->where('is_my_ticket',true)
                        ->where('numero_chaise',$data['numero_chaise'])
                        ->where('statut',StatutTicket::EnAttente)
                        ->where('type',$data['voyageType'])
                        ->get();
        if ($oldeTicket->isNotEmpty()){
            return $oldeTicket;
        }

        $data['voyage_instance_id'] = $voyageInstaceId;
        $data['statut'] = StatutTicket::EnAttente;
        $data['numero_ticket'] = TicketHelpers::generateTicketNumber();
        $data['code_sms'] = TicketHelpers::generateTicketCodeSms();
        $data['code_qr'] = TicketHelpers::generateTicketCodeQr();
        $date['user_id'] = Auth::user()->id ;
        $data['type']  = $data['voyageType'] == 'aller_retour'? TypeTicket::AllerRetour : TypeTicket::AllerSimple;
        $data['is_my_ticket'] = $isMine;
        if (!$isMine){
            $otherPersonData = $data['otherPerson'];
            $otherPerson = AutrePersonne::create($otherPersonData);
            $data['autre_personne_id'] = $otherPerson->id;
        }
        $data['date'] = now();
        $data['heure'] = now();

        return Ticket::create($data);

    }


    public function createTicket(array $data): ?Ticket
    {
        try {

            DB::beginTransaction();
            $ticket = Ticket::create([
                'numero_ticket' => TicketHelpers::generateTicketNumber(),
                'code_sms' => TicketHelpers::generateTicketCodeSms(),
                'code_qr' => TicketHelpers::generateTicketCodeQr(),
                'statut' => StatutTicket::EnAttente,
                'type' => $data['type'] === 'aller_retour' ? TypeTicket::AllerRetour : TypeTicket::AllerSimple,
                'numero_chaise' => $data['numero_chaise'],
                'is_my_ticket' => $data['is_my_ticket'],
                'a_bagage' => $data['a_bagage'] ?? false,
                'bagages_data' => json_encode($data['bagages_data'] ?? []),
                'voyage_instance_id' => $data['voyage_instance_id'],
                'date' => now(),
            ]);

            if (!$data['is_my_ticket']) {
                $autrePersonne = AutrePersonne::create($data['autre_personne']);
                $ticket->autre_personne_id = $autrePersonne->id;
            }

            $ticket->user_id = Auth::user()->id();
            $ticket->save();

            DB::commit();
            return $ticket;
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error or handle it as needed
            Log::error('Error creating ticket: ' . $e->getMessage());
            return null;
        }
    }


    public function createautrePersonne(array $data,) : ?AutrePersonne
    {


        $dataOK = FacadesValidator::validate($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:20',
            'sexe' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'numero' => 'nullable|string|max:255',
            'numero_identifiant' => 'nullable|string|max:20',
            'lien_relation' => 'nullable|string|max:100',
        ],
            [
                'first_name.required' => 'Le prénom est requis.',
                'last_name.required' => 'Le nom est requis.',
                'sexe.required' => 'Le sexe est requis.',
                'email.email' => 'L\'email doit être une adresse email valide.',
                'numero.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
                'numero_identifiant.string' => 'Le numéro d\'identifiant doit être une chaîne de caractères.',
                'lien_relation.string' => 'Le lien de relation doit être une chaîne de caractères.',
            ],
            [
                'first_name' => 'Prénom',
                'last_name' => 'Nom',
                'sexe' => 'Sexe',
                'email' => 'Email',
                'numero' => 'Numéro de téléphone',
                'numero_identifiant' => 'Numéro d\'identifiant',
                'lien_relation' => 'Lien de relation',
            ]
        );

        // If validation fails, it will throw an exception
        if (!$dataOK) {
            Log::error('Validation failed for autre personne data: ' . json_encode($data));
            return FacadesValidator::errors();
        }

        $dataOK['name'] = $dataOK['first_name'] . ' ' . $dataOK['last_name'];
        $dataOK['user_id'] = Auth::id();

        try {
            DB::beginTransaction();
            $autrePersonne = AutrePersonne::create($dataOK);
            DB::commit();
            return $autrePersonne;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating autre personne: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Change le statut d'un ticket en respectant la chronologie
     *
     * @param Ticket $ticket
     * @param string $newStatus
     * @return Ticket|null
     */
    public function changeStatus(Ticket $ticket, string $newStatus): ?Ticket
    {
        try {
            $currentStatus = $ticket->statut;
            
            // Vérification de la chronologie des statuts
            $allowedTransitions = [
                StatutTicket::EnAttente->value => [
                    StatutTicket::Payer->value,
                    StatutTicket::Annuler->value,
                    StatutTicket::Refuser->value,
                    StatutTicket::Pause->value
                ],
                StatutTicket::Payer->value => [
                    StatutTicket::Valider->value,
                    StatutTicket::Annuler->value,
                    StatutTicket::Bloquer->value,
                    StatutTicket::Suspendre->value
                ],
                StatutTicket::Pause->value => [
                    StatutTicket::EnAttente->value,
                    StatutTicket::Payer->value,
                    StatutTicket::Annuler->value,
                    StatutTicket::Refuser->value
                ],
                StatutTicket::Bloquer->value => [
                    StatutTicket::Payer->value,
                    StatutTicket::Annuler->value
                ],
                StatutTicket::Suspendre->value => [
                    StatutTicket::Payer->value,
                    StatutTicket::Annuler->value
                ],
                StatutTicket::Valider->value => [
                    StatutTicket::Annuler->value
                ],
                // Les statuts finaux qui ne peuvent plus changer
                StatutTicket::Annuler->value => [],
                StatutTicket::Refuser->value => []
            ];

            // Vérifier si la transition est autorisée
            if (!isset($allowedTransitions[$currentStatus]) || 
                !in_array($newStatus, $allowedTransitions[$currentStatus])) {
                throw new \Exception("Transition de statut non autorisée: de $currentStatus vers $newStatus");
            }

            DB::beginTransaction();

            $ticket->statut = $newStatus;
            $ticket->save();

            // Log le changement de statut
            Log::info("Ticket #{$ticket->numero_ticket} status changed from $currentStatus to $newStatus");

            DB::commit();
            return $ticket;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error changing ticket status: " . $e->getMessage());
            return null;
        }
    }

}
