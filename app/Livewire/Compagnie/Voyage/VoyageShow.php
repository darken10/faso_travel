<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\StatutTicket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class VoyageShow extends Component
{
    use WithPagination;

    public int $voyageId;

    public function mount(int $voyageId): void
    {
        $this->voyageId = $voyageId;
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $voyage = Voyage::withoutGlobalScopes()
            ->with(['trajet.depart', 'trajet.arriver', 'gareDepart', 'gareArrive', 'classe', 'vehicule', 'chauffer'])
            ->where('compagnie_id', $compagnieId)
            ->findOrFail($this->voyageId);

        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        // Stats
        $instancesTotal    = VoyageInstance::where('voyage_id', $voyage->id)->count();
        $instancesUpcoming = VoyageInstance::where('voyage_id', $voyage->id)->whereRaw("{$dateHeure} >= NOW()")->count();

        $ticketsPayes = $voyage->tickets()->where('statut', StatutTicket::Payer)->count();

        $revenue = $voyage->tickets()
            ->where('statut', StatutTicket::Payer)
            ->with('payements')
            ->get()
            ->sum(fn ($t) => $t->payements->sum('montant'));

        // Prochaines instances (paginées) avec nombre de tickets actifs.
        $instances = VoyageInstance::where('voyage_id', $voyage->id)
            ->withCount(['tickets as occupied_count' => fn ($q) => $q->where('statut', '!=', StatutTicket::Annuler)])
            ->with(['care', 'chauffer'])
            ->whereRaw("{$dateHeure} >= NOW()")
            ->orderByRaw("{$dateHeure} ASC")
            ->paginate(10);

        return view('livewire.compagnie.voyage.voyage-show', [
            'voyage'            => $voyage,
            'instances'         => $instances,
            'instancesTotal'    => $instancesTotal,
            'instancesUpcoming' => $instancesUpcoming,
            'ticketsPayes'      => $ticketsPayes,
            'revenue'           => $revenue,
        ]);
    }
}
