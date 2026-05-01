<?php

namespace App\Livewire\Compagnie\Finance;

use App\Models\Finance\Recette;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

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
        session()->flash('success', $this->editingId ? 'Recette mise à jour.' : 'Recette enregistrée.');
        $this->reset(['editingId', 'libelle', 'montant', 'date_recette', 'source', 'reference', 'note']);
    }

    public function delete(int $id): void
    {
        Recette::findOrFail($id)->delete();
        session()->flash('success', 'Recette supprimée.');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $recettes = Recette::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->latest('date_recette')
            ->paginate(15);

        $totalFiltre = Recette::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->sum('montant');

        return view('livewire.compagnie.finance.recette-manager', compact('recettes', 'totalFiltre'));
    }
}
