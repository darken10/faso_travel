<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Models\Compagnie\Chauffer;
use App\Models\Voyage\VoyageInstance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class ChauffeurShow extends Component
{
    public string $chauffeurId;

    public function mount(string $chauffeurId): void
    {
        $this->chauffeurId = $chauffeurId;
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $chauffeur = Chauffer::with('documents')
            ->where('compagnie_id', $compagnieId)
            ->findOrFail($this->chauffeurId);

        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        $assignments = VoyageInstance::where('chauffer_id', $chauffeur->id)
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'care'])
            ->whereRaw("{$dateHeure} >= NOW()")
            ->orderByRaw("{$dateHeure} ASC")
            ->paginate(10);

        return view('livewire.compagnie.compagnie.chauffeur-show', [
            'chauffeur'   => $chauffeur,
            'assignments' => $assignments,
        ]);
    }
}
