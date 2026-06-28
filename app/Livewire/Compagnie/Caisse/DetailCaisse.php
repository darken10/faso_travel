<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Models\Finance\Caisse;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class DetailCaisse extends Component
{
    use WithPagination;

    public Caisse $caisse;

    public function exportPdf()
    {
        $caisse  = $this->caisse;
        $tickets = $caisse->tickets()
            ->with(['autre_personne', 'user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->latest()
            ->get();

        $pdf = Pdf::loadView('exports.caisse', compact('caisse', 'tickets'));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'caisse-' . ($caisse->opened_at?->format('Y-m-d') ?? $caisse->id) . '.pdf',
        );
    }

    public function render()
    {
        $tickets = $this->caisse->tickets()
            ->with(['autre_personne', 'voyageInstance.voyage.trajet'])
            ->latest()
            ->paginate(15);

        return view('livewire.compagnie.caisse.detail-caisse', compact('tickets'));
    }
}
