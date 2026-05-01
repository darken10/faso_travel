<?php

namespace App\Livewire\Compagnie\Finance;

use App\Models\Finance\CategorieDepense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class CategorieManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $nom = '';
    public string $description = '';

    protected function rules(): array
    {
        return [
            'nom'         => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nom', 'description']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $cat = CategorieDepense::findOrFail($id);
        $this->editingId = $id;
        $this->nom = $cat->nom;
        $this->description = $cat->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $compagnieId = Auth::user()->compagnie_id;

        if ($this->editingId) {
            CategorieDepense::findOrFail($this->editingId)->update([
                'nom'         => $this->nom,
                'description' => $this->description ?: null,
            ]);
        } else {
            CategorieDepense::create([
                'compagnie_id' => $compagnieId,
                'nom'          => $this->nom,
                'description'  => $this->description ?: null,
            ]);
        }

        $this->showModal = false;
        session()->flash('success', $this->editingId ? 'Catégorie mise à jour.' : 'Catégorie créée.');
        $this->reset(['editingId', 'nom', 'description']);
    }

    public function delete(int $id): void
    {
        $cat = CategorieDepense::findOrFail($id);
        if ($cat->depenses()->count() > 0) {
            session()->flash('error', 'Impossible de supprimer : cette catégorie est utilisée par des dépenses.');
            return;
        }
        $cat->delete();
        session()->flash('success', 'Catégorie supprimée.');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $categories = CategorieDepense::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('nom', 'like', '%' . $this->search . '%'))
            ->withCount('depenses')
            ->orderBy('nom')
            ->paginate(15);

        return view('livewire.compagnie.finance.categorie-manager', compact('categories'));
    }
}
