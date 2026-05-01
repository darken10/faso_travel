<?php

namespace App\Http\Controllers\Ticket;

use App\Enums\StatutTicket;
use App\Enums\StatutUser;
use App\Enums\TypeNotification;
use App\Enums\TypeTicket;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Events\TranfererTicketToOtherUserEvent;
use App\Helper\TicketHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Models\Ticket\AutrePersonne;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Voyage\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function createTicket(CreateTicketRequest $request, Voyage $voyage)
    {
        $data                  = $request->validated();
        $data['voyage_id']     = $voyage->id;
        $data['statut']        = StatutTicket::EnAttente;
        $data['numero_ticket'] = TicketHelpers::generateTicketNumber();
        $data['code_sms']      = TicketHelpers::generateTicketCodeSms();
        $data['code_qr']       = TicketHelpers::generateTicketCodeQr();
        $data['type']          = ($data['type'] ?? '') === 'aller_retour' ? TypeTicket::AllerRetour : TypeTicket::AllerSimple;
        $data['user_id']       = Auth::id();
        $data['a_bagage']      = array_key_exists('a_bagage', $data);

        $existing = Ticket::where('user_id', Auth::id())
            ->where('voyage_id', $voyage->id)
            ->where('statut', StatutTicket::EnAttente)
            ->where('date', $data['date'])
            ->where('is_my_ticket', true)
            ->where('type', $data['type'])
            ->first();

        if ($existing) {
            return view('ticket.ticket.choix-moyen-payment', ['ticket' => $existing])
                ->with('success', 'Un ticket non payé existe déjà à votre nom pour ce trajet à cette date.');
        }

        if (array_key_exists('autre_personne_id', $data)) {
            $data['is_my_ticket'] = false;
        }

        $ticket = Ticket::create($data);

        return view('ticket.ticket.choix-moyen-payment', ['ticket' => $ticket]);
    }

    public function createTicketWithVoyageInstance(CreateTicketRequest $request, VoyageInstance $voyage_instance)
    {
        $data = $request->validated();

        $ticket = $this->ticketService->createTicket($voyage_instance->id, $data);

        return view('ticket.ticket.choix-moyen-payment', ['ticket' => $ticket]);
    }

    public function myTickets()
    {
        $userId = Auth::id();

        $tickets = Ticket::with(['voyageInstance.voyage.trajet'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->whereNull('transferer_a_user_id');
            })
            ->orWhere('transferer_a_user_id', $userId)
            ->latest()
            ->paginate(20);

        return view('ticket.ticket.my-tickets', ['tickets' => $tickets]);
    }

    public function showMyTicket(Ticket $ticket)
    {
        return view('ticket.ticket.show-my-ticket', ['ticket' => $ticket]);
    }

    public function navigateToGare(Ticket $ticket)
    {
        $gareDepart = $ticket->gareDepart();

        return view('ticket.ticket.navigate-to-gare', [
            'ticket'   => $ticket,
            'gare'     => $gareDepart,
            'gareLat'  => $gareDepart?->lat,
            'gareLng'  => $gareDepart?->lng,
            'gareName' => $gareDepart?->name,
        ]);
    }

    public function editTicket(Ticket $ticket)
    {
        return view('ticket.ticket.edit-ticket', ['ticket' => $ticket]);
    }

    public function reenvoyer(Ticket $ticket)
    {
        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::EnAttente, StatutTicket::Pause])) {
            return back()->with('error', 'Votre ticket est dans un état invalide pour cette opération.');
        }

        try {
            PayementEffectuerEvent::dispatch($ticket);
            SendClientTicketByMailEvent::dispatch($ticket, TypeNotification::TICKET_REDELIVERED);
        } catch (\Throwable $e) {
            Log::error('[Ticket] Renvoi échoué pour ticket ' . $ticket->id . ': ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors du renvoi du ticket.');
        }

        return back()->with('success', 'Votre ticket a été renvoyé par email.');
    }

    public function regenerer(Ticket $ticket)
    {
        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            return back()->with('error', 'Votre ticket est dans un état invalide pour la régénération.');
        }

        $success = TicketHelpers::regenerateTicket($ticket);

        if (!$success) {
            return back()->with('error', 'Une erreur est survenue lors de la régénération du ticket.');
        }

        try {
            PayementEffectuerEvent::dispatch($ticket);
            SendClientTicketByMailEvent::dispatch($ticket, TypeNotification::TICKET_REGENERATED);
        } catch (\Throwable $e) {
            Log::error('[Ticket] Régénération email échouée pour ticket ' . $ticket->id . ': ' . $e->getMessage());
        }

        return back()->with('success', 'Votre ticket a été régénéré et vous sera envoyé par email.');
    }

    public function tranfererTicketToOtherUser(Ticket $ticket)
    {
        return view('ticket.ticket.transferer.transfert-choix-user', ['ticket' => $ticket]);
    }

    public function tranfererTicketToOtherUserTraitement(Ticket $ticket, Request $request)
    {
        $data = $request->validate(['user_selected' => ['required', 'integer', 'exists:users,id']]);

        $user = User::findOrFail($data['user_selected']);

        if ($user->statut !== StatutUser::Active) {
            return back()->with('error', "L'utilisateur sélectionné n'est pas autorisé à utiliser le système.");
        }

        return view('ticket.ticket.transferer.tranferer-ticket-valider-choix', [
            'user'   => $user,
            'ticket' => $ticket,
        ]);
    }

    public function tranfererTicketTraitement(Ticket $ticket, Request $request)
    {
        $data = $request->validate([
            'password' => ['required'],
            'accepted' => ['required'],
            'user_id'  => ['required', 'integer', 'exists:users,id'],
        ]);

        if (!Hash::check($data['password'], $ticket->user->password)) {
            return back()->with('error', 'Mot de passe incorrect.');
        }

        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            return back()->with('error', 'Le ticket est dans un état invalide pour le transfert.');
        }

        if (!$ticket->is_my_ticket) {
            return to_route('ticket.myTickets')->with('error', "Ce ticket n'est plus en votre possession.");
        }

        try {
            DB::beginTransaction();
            $ticket->is_my_ticket         = false;
            $ticket->transferer_at        = now();
            $ticket->transferer_a_user_id = $data['user_id'];
            $ticket->save();
            $regenerated = TicketHelpers::regenerateTicket($ticket);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Ticket] Transfert échoué pour ticket ' . $ticket->id . ': ' . $e->getMessage());
            return back()->with('error', "Une erreur inattendue est survenue. Veuillez contacter l'administrateur.");
        }

        if (!$regenerated) {
            return back()->with('error', 'Une erreur est survenue lors de la régénération du ticket après transfert.');
        }

        TranfererTicketToOtherUserEvent::dispatch($ticket);

        return to_route('ticket.myTickets')->with('success', "Le ticket a été transféré avec succès. Vous n'en êtes plus le détenteur.");
    }

    public function mettreEnPause(Request $request, Ticket $ticket)
    {
        if ($ticket->statut !== StatutTicket::Payer) {
            return back()->with('error', 'Seul un ticket payé peut être mis en pause.');
        }

        if (!$ticket->is_my_ticket && $ticket->transferer_a_user_id !== null) {
            return to_route('ticket.myTickets')->with('error', "Ce ticket n'est plus en votre possession.");
        }

        try {
            DB::beginTransaction();
            $ticket->statut = StatutTicket::Pause;
            $ticket->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', "Une erreur inattendue est survenue.");
        }

        return to_route('ticket.show-ticket', $ticket)->with('success', 'Votre ticket a bien été mis en pause.');
    }

    public function gotoPayment(Ticket $ticket)
    {
        if ($ticket->statut !== StatutTicket::EnAttente) {
            return redirect()->back()->with('error', "Ce ticket n'est pas en attente de paiement.");
        }

        return view('ticket.ticket.choix-moyen-payment', ['ticket' => $ticket]);
    }
}
