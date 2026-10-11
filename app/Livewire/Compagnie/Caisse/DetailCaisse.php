<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Models\Finance\Caisse;
use App\Services\BordereauVersementService;
use App\Traits\AutoriseLesActions;
use App\Traits\JournaliseLesActions;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class DetailCaisse extends Component
{
    use AutoriseLesActions;
    use JournaliseLesActions;
    use WithPagination;

    public Caisse $caisse;

    public function exportPdf()
    {
        $this->autoriser('caisse.historique.export');
        $caisse = $this->caisse;
        $tickets = $caisse->tickets()
            ->with(['autre_personne', 'user', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver'])
            ->latest()
            ->get();

        $pdf = Pdf::loadView('exports.caisse', compact('caisse', 'tickets'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'caisse-'.($caisse->opened_at?->format('Y-m-d') ?? $caisse->id).'.pdf',
        );
    }

    public function emettreBordereau(): void
    {
        $this->autoriser('caisse.bordereau.emit', $this->caisse);

        try {
            $bordereau = app(BordereauVersementService::class)->emettre($this->caisse, auth()->user());
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->journaliser('caisse.bordereau.emit', $bordereau, [], $bordereau->attributesToArray());
        $this->caisse->load('bordereauVersement');
        $this->dispatch('toast', type: 'success', message: 'Bordereau '.$bordereau->numero.' émis. Présentez-le à la finance avec les espèces.');
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
