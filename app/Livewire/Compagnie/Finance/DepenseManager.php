<?php

namespace App\Livewire\Compagnie\Finance;

use App\Models\Finance\CategorieDepense;
use App\Models\Finance\Depense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class DepenseManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categorieFilter = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $libelle = '';
    public int $montant = 0;
    public string $date_depense = '';
    public ?int $categorie_depense_id = null;
    public string $reference = '';
    public string $note = '';

    protected function rules(): array
    {
        return [
            'libelle'              => 'required|string|max:255',
            'montant'              => 'required|integer|min:1',
            'date_depense'         => 'required|date',
            'categorie_depense_id' => 'nullable|exists:categorie_depenses,id',
            'reference'            => 'nullable|string|max:100',
            'note'                 => 'nullable|string|max:500',
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategorieFilter(): void { $this->resetPage(); }

    public function openDocPanel(int $id): void
    {
        $dep = Depense::findOrFail($id);
        $this->dispatch('open-doc-panel',
            type:     Depense::class,
            id:       (string) $id,
            label:    $dep->libelle . ' · ' . number_format($dep->montant, 0, ',', ' ') . ' F',
            typeName: 'Dépense',
        );
    }

    #[On('doc-panel-saved')]
    public function refreshDocCounts(): void {}

    public function openCreate(): void
    {
        $this->reset(['editingId', 'libelle', 'montant', 'date_depense', 'categorie_depense_id', 'reference', 'note']);
        $this->date_depense = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $dep = Depense::findOrFail($id);
        $this->editingId = $id;
        $this->libelle = $dep->libelle;
        $this->montant = $dep->montant;
        $this->date_depense = $dep->date_depense?->format('Y-m-d') ?? '';
        $this->categorie_depense_id = $dep->categorie_depense_id;
        $this->reference = $dep->reference ?? '';
        $this->note = $dep->note ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $compagnieId = Auth::user()->compagnie_id;

        $data = [
            'libelle'              => $this->libelle,
            'montant'              => $this->montant,
            'date_depense'         => $this->date_depense,
            'categorie_depense_id' => $this->categorie_depense_id,
            'reference'            => $this->reference ?: null,
            'note'                 => $this->note ?: null,
        ];

        if ($this->editingId) {
            Depense::findOrFail($this->editingId)->update($data);
        } else {
            Depense::create(array_merge($data, [
                'compagnie_id' => $compagnieId,
                'user_id'      => Auth::id(),
            ]));
        }

        $this->showModal = false;
        $this->dispatch('toast', type: 'success', message: $this->editingId ? 'Dépense mise à jour.' : 'Dépense enregistrée.');
        $this->reset(['editingId', 'libelle', 'montant', 'date_depense', 'categorie_depense_id', 'reference', 'note']);
    }

    public function delete(int $id): void
    {
        Depense::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Dépense supprimée.');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $depenses = Depense::withCount('documents')
            ->where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->when($this->categorieFilter, fn ($q) => $q->where('categorie_depense_id', $this->categorieFilter))
            ->with('categorie')
            ->latest('date_depense')
            ->paginate(15);

        $categories = CategorieDepense::where('compagnie_id', $compagnieId)->orderBy('nom')->get();
        $totalFiltre = Depense::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('libelle', 'like', '%' . $this->search . '%'))
            ->when($this->categorieFilter, fn ($q) => $q->where('categorie_depense_id', $this->categorieFilter))
            ->sum('montant');

        return view('livewire.compagnie.finance.depense-manager', compact('depenses', 'categories', 'totalFiltre'));
    }
}
