<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\TypeTicket;
use App\Http\Controllers\Controller;
use App\Models\Finance\PromoCode;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /** Valide un code promo pour un voyage et renvoie la réduction. */
    public function validatePromo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'      => 'required|string|max:50',
            'trip_id'   => 'required|string|exists:voyage_instances,id',
            'trip_type' => 'nullable|in:one-way,round-trip',
        ]);

        $instance = VoyageInstance::with('voyage')->findOrFail($validated['trip_id']);
        $type   = ($validated['trip_type'] ?? 'one-way') === 'round-trip' ? TypeTicket::AllerRetour : TypeTicket::AllerSimple;
        $amount = (int) $instance->getPrix($type);

        $promo = PromoCode::where('compagnie_id', $instance->voyage->compagnie_id)
            ->where('code', strtoupper(trim($validated['code'])))
            ->first();

        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Code promo introuvable.'], 200);
        }

        if (!$promo->isValide($amount)) {
            return response()->json(['valid' => false, 'message' => $promo->raisonInvalide($amount)], 200);
        }

        $reduction = $promo->reductionPour($amount);

        return response()->json([
            'valid'     => true,
            'code'      => $promo->code,
            'reduction' => $reduction,
            'montant'   => $amount,
            'final'     => max(0, $amount - $reduction),
            'message'   => 'Code appliqué : -' . number_format($reduction, 0, ',', ' ') . ' XOF',
        ]);
    }
}
