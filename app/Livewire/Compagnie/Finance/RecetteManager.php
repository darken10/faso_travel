<?php

namespace App\Livewire\Compagnie\Finance;

use App\Exports\RecettesExport;
use App\Models\Finance\Recette;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.compagnie-panel')]
class RecetteManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $libelle = '';
    public int $montant = 0;
    public string $date_recette = '';
    public string $source = '';
    public string $reference = '';
    public string $note = '';

    protected function rules(): array
    {
        return [
            'libelle'      => 'required|string|max:255',
            'montant'      => 'required|integer|min:1',
            'date_recette' => 'required|date',
            'source'       => 'nullable|string|max:255',
            'reference'    => 'nullable|string|max:100',
            'note'         => 'nullable|string|max:500',
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function openDocPanel(int $id): void
    {
        $rec = Recette::findOrFail($id);
        $this->dispatch('open-doc-panel',
            type:     Recette::class,
            id:       (string) $id,
            label:    $rec->libelle . ' · ' . number_format($rec->montant, 0, ',', ' ') . ' F',
            typeName: 'Recette',
        );
    }

    #[On('doc-panel-saved')]
    public function refreshDocCounts(): void {}

    public function openCreate(): void
    {
        $this->reset(['editingId', 'libelle', 'montant', 'date_recette', 'source', 'reference', 'note']);
        $this->date_recette = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $rec = Recette::findOrFail($id);
        $this->editingId = $id;
        $this->libelle = $rec->libelle;
        $this->montant = $rec->montant;
        $this->date_recette = $rec->date_recette?->format('Y-m-d') ?? '';
        $this->source = $rec->source ?? '';
        $this->reference = $rec->reference ?? '';
        $this->note = $rec->note ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $compagnieId = Auth::user()->compagnie_id;

        $data = [
            'libelle'      => $this->libelle,
            'montant'      => $this->montant,
            'date_recette' => $this->date_recette,
            'source'       => $this->source ?: null,
            'reference'    => $this->reference ?: null,
            'note'         => $this->note ?: null,
        ];

        if ($this->editingId) {
            Recette::findOrFail($this->editingId)->update($data);
        } else {
            Recette::create(array_merge($data, [
                'compagnie_id' => $compagnieId,
                'user_id'      => Auth::id(),
            ]));
        }

        $this->showModal = false;
        $this->dispatch('toast', type: 'success', message: $this->editingId ? 'Recette mise à jour.' : 'Recette enregistrée.');
        $this->reset(['editingId', 'libelle', 'montant', 'date_recette', 'source', 'reference', 'note']);
    }

    public function delete(int $id): void
    {
        Recette::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Recette supprimée.');
    }

    public function export()
    {
        $compagnieId = Auth::user()->compagnie_id;
        $query = Recette::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->latest('date_recette');

        return Excel::download(new RecettesExport($query), 'recettes-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $recettes = Recette::withCount('documents')
            ->where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->latest('date_recette')
            ->paginate(15);

        $totalFiltre = Recette::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->sum('montant');

        return view('livewire.compagnie.finance.recette-manager', compact('recettes', 'totalFiltre'));
    }
}
