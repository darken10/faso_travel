<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Models\Finance\BordereauVersement;
use App\Services\BordereauVersementService;
use App\Traits\AutoriseLesActions;
use App\Traits\JournaliseLesActions;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class BordereauxVersement extends Component
{
    use AutoriseLesActions;
    use JournaliseLesActions;
    use WithPagination;

    public ?int $bordereauId = null;
    public ?string $actionEnCours = null;
    public int $montant = 0;
    public string $reference = '';
    public string $note = '';

    public function ouvrirAction(string $action, int $bordereauId): void
    {
        $bordereau = BordereauVersement::findOrFail($bordereauId);
        $this->autoriser($this->permissionPour($action), $bordereau);

        $this->resetValidation();
        $this->bordereauId = $bordereau->id;
        $this->actionEnCours = $action;
        $this->montant = match ($action) {
            'reception' => $bordereau->montant_declare,
            'comptabilisation' => $bordereau->montant_recu ?? $bordereau->montant_declare,
            default => 0,
        };
        $this->reference = $bordereau->reference_comptable ?? '';
        $this->note = '';
    }

    public function annulerAction(): void
    {
        $this->reset(['bordereauId', 'actionEnCours', 'montant', 'reference', 'note']);
        $this->resetValidation();
    }

    public function confirmerAction(): void
    {
        $bordereau = BordereauVersement::findOrFail($this->bordereauId);
        $action = $this->actionEnCours;

        if ($action === null) {
            return;
        }

        $this->autoriser($this->permissionPour($action), $bordereau);
        $this->validate($this->rulesPour($action));
        $avant = $bordereau->attributesToArray();

        try {
            $service = app(BordereauVersementService::class);
            $bordereau = match ($action) {
                'reception' => $service->prendreEnCharge($bordereau, auth()->user(), $this->montant, $this->note ?: null),
                'transmission' => $service->transmettreComptabilite($bordereau, auth()->user(), $this->reference ?: null, $this->note ?: null),
                'comptabilisation' => $service->comptabiliser($bordereau, auth()->user(), $this->montant, $this->note ?: null),
            };
        } catch (DomainException $exception) {
            $this->addError('action', $exception->getMessage());

            return;
        }

        $this->journaliserModification('caisse.bordereau.'.match ($action) {
            'reception' => 'receive',
            'transmission' => 'transfer',
            'comptabilisation' => 'account',
        }, $bordereau, $avant, $this->note ?: null);

        $message = match ($action) {
            'reception' => 'Versement pris en charge par la finance.',
            'transmission' => 'Versement transmis à la comptabilité.',
            'comptabilisation' => 'Versement comptabilisé et recette créée.',
        };

        $this->annulerAction();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function exportPdf(int $bordereauId)
    {
        $bordereau = BordereauVersement::with([
            'caisse.user', 'emetteur', 'recepteurFinance', 'transmetteur', 'comptable',
        ])->findOrFail($bordereauId);
        $this->autoriser('caisse.bordereau.export', $bordereau);

        $pdf = Pdf::loadView('exports.bordereau-versement', compact('bordereau'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'bordereau-'.$bordereau->numero.'.pdf',
        );
    }

    public function render()
    {
        $query = BordereauVersement::with([
            'caisse.user', 'emetteur', 'recepteurFinance', 'comptable',
        ])->latest('emitted_at');

        if (auth()->user()->porteePour('caisse.bordereau.view') === 'own') {
            $query->where('emitted_by_id', auth()->id());
        }

        $bordereaux = $query->paginate(15);

        return view('livewire.compagnie.caisse.bordereaux-versement', compact('bordereaux'));
    }

    private function permissionPour(string $action): string
    {
        return match ($action) {
            'reception' => 'caisse.bordereau.receive',
            'transmission' => 'caisse.bordereau.transfer',
            'comptabilisation' => 'caisse.bordereau.account',
            default => abort(404),
        };
    }

    /** @return array<string, string> */
    private function rulesPour(string $action): array
    {
        return match ($action) {
            'reception', 'comptabilisation' => [
                'montant' => 'required|integer|min:0',
                'note' => 'nullable|string|max:1000',
            ],
            'transmission' => [
                'reference' => 'nullable|string|max:100',
                'note' => 'nullable|string|max:1000',
            ],
            default => [],
        };
    }
}
