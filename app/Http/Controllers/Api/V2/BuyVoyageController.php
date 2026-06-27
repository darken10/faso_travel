<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Voyage\VoyageInstance;
use App\Services\V2\BuyVoyageService;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ResourceNotFoundException;

class BuyVoyageController extends Controller
{

    public function __construct(
        public BuyVoyageService $buy_voyage_service ,
    )
    {
    }

    /**
     * Buy a ticket for a voyage.
     *
     * @param Request $request
     * @param VoyageInstance $voyageInstance
     * @return \Illuminate\Http\Response
     */
    public function reservation(string $id, Request $request)
    {
        $voyageInstance = VoyageInstance::find($id);
        if (!$voyageInstance) {
            throw new ResourceNotFoundException('Voyage instance ' . $id . ' not found');
        }

        $validator = Validator::make($request->all(), [
            'seats' => 'required|string', // ou 'regex' si tu veux valider le format exact
            'totalPrice' => 'required|numeric|min:0',
            'isForSelf' => 'required|boolean',
            'tripType' => 'required|in:one-way,round-trip', // adapte selon tes types

            'passenger.first_name' => 'required_if:isForSelf,false|string|max:255',
            'passenger.last_name' => 'required_if:isForSelf,false|string|max:255',
            'passenger.sexe' => 'required_if:isForSelf,false|string|in:Homme,Femme,Autre',
            'passenger.lien_relation' => 'required_if:isForSelf,false|string|max:255',
            'passenger.email' => 'nullable|string|email|max:255',
            'passenger.numero' => 'nullable|string|max:255',
            'passenger.numero_identifiant' => 'nullable|string|max:255',
            'passenger.note' => 'nullable|string|max:500',

            'a_bagage' => 'nullable|boolean',
            'bagages_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Logique pour réserver le voyage
        
        ;

        return $this->buy_voyage_service->buyTicket($voyageInstance, $validator->validated());
    }
}
