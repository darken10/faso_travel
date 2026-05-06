<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAutrePersonneFormRequest;
use App\Http\Requests\Voyage\IsMyTicketBooleanFormRequest;
use App\Models\Ticket\AutrePersonne;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class VoyageController extends Controller
{
    function index(): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {

        return view('ticket.voyage.index');
    }

    function show(Voyage $voyage): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {

        return view('ticket.voyage.show',[
            'voyage'=>$voyage,
        ]);
    }

    function showVoyageInstance(VoyageInstance $voyageInstance)
    {

        return view('ticket.voyageInstance.show',[
            'voyageInstance'=>$voyageInstance,
        ]);
    }

    function acheterVoyageInstance(VoyageInstance $voyageInstance){
        return view('ticket.voyageInstance.acheter',[
            'voyageInstance'=>$voyageInstance,
            'autre_personne' => null,
        ]);
    }

    function acheter(Voyage $voyage)
    {
        return view('ticket.voyage.achaterTicket',[
            'autre_personne' => null,
            'voyage'=>$voyage,
        ]);
    }

    function payerAutrePersonneTicket(Ticket  $ticket)
    {
        return view('ticket.voyage.achaterTicket',[
            'autre_personne' => $ticket->autre_personne,
            'voyage'=>$ticket->voyage,
        ]);
    }

    function is_my_ticket(VoyageInstance $voyageInstance): Application|Factory|View
    {
        return view('ticket.voyage.is-my-ticket',['voyageInstance'=>$voyageInstance]);
    }

    function is_my_ticket_traitement(Voyage $voyage,IsMyTicketBooleanFormRequest $request): \Illuminate\Http\RedirectResponse
    {
        if ($request->validated()['is_my_ticket'] !== 'is_not_my_ticket'){
            return to_route('voyage.acheter',$voyage);
        }
        return  to_route('voyage.autre-ticket-info',$voyage);
    }

    function autre_ticket_info(Voyage $voyage): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('ticket.voyage.autre-ticket-info',[
            'voyage'=>$voyage,
        ]);
    }

    function register_autre_personne(Voyage $voyage, RegisterAutrePersonneFormRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();

        $autre = AutrePersonne::query()
            ->whereBelongsTo(\Auth::user())
            ->where('numero_identifiant', $data['numero_identifiant'])
            ->where('numero', $data['numero'])
            ->where('sexe', $data['sexe'])
            ->where('lien_relation', $data['lien_relation'])
            ->get()
            ->last();
        if (!($autre instanceof AutrePersonne)){
            $autre = AutrePersonne::create($data);
        }
        return to_route('voyage.payer-ticket-autre-personne',[$voyage,$autre]);
    }


    function payer_ticket_autre_personne(Voyage $voyage,AutrePersonne $autre_personne)
    {
        return view('ticket.voyage.achaterTicket',[
            'voyage'=>$voyage,
            'autre_personne'=>$autre_personne,
        ]);
    }


}
