<?php

namespace App\Livewire\Voyage;

use App\Enums\TypeTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Livewire\Component;

class SearchVoyageInstanceComponent extends Component
{
    public ?int $compagnie = null;
    public string $date = '';
    public string $villeDepart = '';
    public string $villeArrivee = '';
    public string $typeTicket = 'aller-simple';

    public Collection|array $allCompagnies = [];
    public Collection|array $voyageInstances = [];

    public function mount(): void
    {
        $this->allCompagnies = Compagnie::actives()->get();
        $this->voyageInstances = $this->buildQuery()->get();
    }

    public function search(): void
    {
        $this->voyageInstances = $this->buildQuery()->get();
    }

    public function resetFilters(): void
    {
        $this->compagnie = null;
        $this->date = '';
        $this->villeDepart = '';
        $this->villeArrivee = '';
        $this->voyageInstances = $this->buildQuery()->get();
    }

    private function buildQuery()
    {
        return VoyageInstance::avenir()
            ->with([
                'voyage.trajet.depart',
                'voyage.trajet.arriver',
                'voyage.gareDepart',
                'voyage.gareArriver',
                'voyage.compagnie',
            ])
            ->when($this->compagnie, fn ($q) =>
                $q->whereHas('voyage', fn ($sub) => $sub->where('compagnie_id', $this->compagnie))
            )
            ->when($this->date, fn ($q) => $q->whereDate('date', $this->date))
            ->when($this->villeDepart, fn ($q) =>
                $q->whereHas('voyage.trajet.depart', fn ($sub) =>
                    $sub->where('name', 'like', "%{$this->villeDepart}%")
                )
            )
            ->when($this->villeArrivee, fn ($q) =>
                $q->whereHas('voyage.trajet.arriver', fn ($sub) =>
                    $sub->where('name', 'like', "%{$this->villeArrivee}%")
                )
            )
            ->orderBy('date')
            ->orderBy('heure');
    }

    public function getTypeTicketEnum(): TypeTicket
    {
        return $this->typeTicket === 'aller-retour'
            ? TypeTicket::AllerRetour
            : TypeTicket::AllerSimple;
    }

    public function render(): Factory|Application|View|\Illuminate\View\View
    {
        return view('livewire.voyage.search-voyage-instance-component', [
            'ticketType' => $this->getTypeTicketEnum(),
        ]);
    }
}
