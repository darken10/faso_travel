<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Models\Voyage\Classe;
use App\Models\Voyage\Confort;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class ClasseManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $showConfortsModal = false;
    public ?int $editingId = null;
    public ?int $selectedClasseId = null;

    public string $name = '';
    public string $description = '';

    // Confort inline form
    public string $confort_title = '';
    public bool $showConfortForm = false;
    public array $attachedConfortIds = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $classe = Classe::findOrFail($id);
        $this->editingId    = $id;
        $this->name         = $classe->name;
        $this->description  = $classe->description ?? '';
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data = ['name' => $this->name, 'description' => $this->description ?: null];

        if ($this->editingId) {
            Classe::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Classe mise à jour.');
        } else {
            Classe::create($data);
            session()->flash('success', 'Classe créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'description']);
    }

    public function delete(int $id): void
    {
        $classe = Classe::findOrFail($id);
        if ($classe->is_default) {
            session()->flash('error', 'Impossible de supprimer une classe par défaut.');
            return;
        }
        $classe->delete();
        session()->flash('success', 'Classe supprimée.');
    }

    public function openConforts(int $classeId): void
    {
        $this->selectedClasseId   = $classeId;
        $classe = Classe::with('conforts')->findOrFail($classeId);
        $this->attachedConfortIds = $classe->conforts->pluck('id')->toArray();
        $this->showConfortForm    = false;
        $this->confort_title      = '';
        $this->showConfortsModal  = true;
    }

    public function toggleConfort(int $confortId): void
    {
        if (!$this->selectedClasseId) return;
        $classe = Classe::findOrFail($this->selectedClasseId);
        if (in_array($confortId, $this->attachedConfortIds)) {
            $classe->conforts()->detach($confortId);
            $this->attachedConfortIds = array_values(array_diff($this->attachedConfortIds, [$confortId]));
        } else {
            $classe->conforts()->attach($confortId);
            $this->attachedConfortIds[] = $confortId;
        }
    }

    public function saveConfort(): void
    {
        $this->validate(['confort_title' => 'required|string|max:255']);

        $confort = Confort::create(['title' => $this->confort_title]);

        if ($this->selectedClasseId) {
            $classe = Classe::findOrFail($this->selectedClasseId);
            $classe->conforts()->attach($confort->id);
            $this->attachedConfortIds[] = $confort->id;
        }

        $this->confort_title  = '';
        $this->showConfortForm = false;
    }

    public function render()
    {
        $classes = Classe::query()
            ->withCount('conforts')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $allConforts = Confort::orderBy('title')->get();

        $selectedClasse = $this->selectedClasseId ? Classe::find($this->selectedClasseId) : null;

        return view('livewire.compagnie.voyage.classe-manager', compact('classes', 'allConforts', 'selectedClasse'));
    }
}
