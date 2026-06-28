<?php

namespace App\Livewire\Compagnie\Rapport;

use App\Exports\PaiementsExport;
use App\Exports\RapportTrajetsExport;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.compagnie-panel')]
class RapportManager extends Component
{
    public string $preset = 'this_month';
    public string $dateDebut = '';
    public string $dateFin = '';

    public function mount(): void
    {
        $this->applyPreset('this_month');
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;
        $now = now();

        match ($preset) {
            'this_month' => [$this->dateDebut = $now->copy()->startOfMonth()->toDateString(), $this->dateFin = $now->copy()->endOfMonth()->toDateString()],
            'last_month' => [$this->dateDebut = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $this->dateFin = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this_week'  => [$this->dateDebut = $now->copy()->startOfWeek()->toDateString(), $this->dateFin = $now->copy()->endOfWeek()->toDateString()],
            '7d'         => [$this->dateDebut = $now->copy()->subDays(6)->toDateString(), $this->dateFin = $now->toDateString()],
            '30d'        => [$this->dateDebut = $now->copy()->subDays(29)->toDateString(), $this->dateFin = $now->toDateString()],
            default      => null, // custom : on garde les dates saisies
        };
    }

    public function updatedDateDebut(): void { $this->preset = 'custom'; }
    public function updatedDateFin(): void { $this->preset = 'custom'; }

    private function range(): array
    {
        $start = $this->dateDebut ? Carbon::parse($this->dateDebut) : now()->startOfMonth();
        $end   = $this->dateFin ? Carbon::parse($this->dateFin) : now();
        return [$start, $end];
    }

    private function compagnieId(): int
    {
        return auth()->user()->compagnie_id;
    }

    public function exportRapportPdf()
    {
        [$start, $end] = $this->range();
        $data = app(ReportService::class)->data($this->compagnieId(), $start, $end);
        $compagnie = auth()->user()->compagnie;

        $pdf = Pdf::loadView('exports.rapport', compact('data', 'compagnie'));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'rapport-' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.pdf',
        );
    }

    public function exportPaiements()
    {
        [$start, $end] = $this->range();
        $query = app(ReportService::class)->paiementsQuery($this->compagnieId(), $start, $end);

        return Excel::download(
            new PaiementsExport($query),
            'paiements-' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.xlsx',
        );
    }

    public function exportTrajets()
    {
        [$start, $end] = $this->range();
        $data = app(ReportService::class)->data($this->compagnieId(), $start, $end);

        return Excel::download(
            new RapportTrajetsExport($data['topTrajets']),
            'rapport-trajets-' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.xlsx',
        );
    }

    public function render()
    {
        [$start, $end] = $this->range();
        $data = app(ReportService::class)->data($this->compagnieId(), $start, $end);

        return view('livewire.compagnie.rapport.rapport-manager', compact('data'));
    }
}
