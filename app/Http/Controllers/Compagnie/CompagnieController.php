<?php

namespace App\Http\Controllers\Compagnie;

use App\Http\Controllers\Controller;
use App\Models\Compagnie\Compagnie;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Http\Request;

class CompagnieController extends Controller
{
    public function index(){
        return view('client.compagnie.index',[
            'compagnies' => Compagnie::all(),
        ]);
    }

    public function show(Compagnie $compagnie){
        $voyageInstances = VoyageInstance::whereHas('voyage', fn ($q) => $q->where('compagnie_id', $compagnie->id))
            ->avenir()
            ->orderBy('date')
            ->orderBy('heure')
            ->get();

        return view('client.compagnie.show', compact('compagnie', 'voyageInstances'));
    }
}
