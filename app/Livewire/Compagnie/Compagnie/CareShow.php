<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Models\Compagnie\Care;
use App\Models\Voyage\VoyageInstance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class CareShow extends Component
{
    public int $careId;

    public function mount(int $careId): void
    {
        $this->careId = $careId;
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $care = Care::withoutGlobalScopes()
            ->with('documents')
            ->where('compagnie_id', $compagnieId)
            ->findOrFail($this->careId);

        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        $assignments = VoyageInstance::where('care_id', $care->id)
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'chauffer'])
            ->whereRaw("{$dateHeure} >= NOW()")
            ->orderByRaw("{$dateHeure} ASC")
            ->paginate(10);

        return view('livewire.compagnie.compagnie.care-show', [
            'care'        => $care,
            'assignments' => $assignments,
        ]);
    }
}
