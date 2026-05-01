<?php

namespace App\Livewire\Admin;

use App\Models\Compagnie\Compagnie;
use App\Models\Statut;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class CompagnieManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $sigle = '';
    public string $slogant = '';
    public string $description = '';
    public ?int $statut_id = 2;
    public $logo = null;

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'sigle'       => 'required|string|max:50',
            'slogant'     => 'nullable|string',
            'description' => 'nullable|string',
            'statut_id'   => 'required|exists:statuts,id',
            'logo'        => 'nullable|image|max:2048',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'sigle', 'slogant', 'description', 'statut_id', 'logo']);
        $this->statut_id = 2;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $compagnie = Compagnie::findOrFail($id);
        $this->editingId = $id;
        $this->name = $compagnie->name;
        $this->sigle = $compagnie->sigle;
        $this->slogant = $compagnie->slogant ?? '';
        $this->description = $compagnie->description ?? '';
        $this->statut_id = $compagnie->statut_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'        => $this->name,
            'sigle'       => $this->sigle,
            'slogant'     => $this->slogant ?: null,
            'description' => $this->description ?: null,
            'statut_id'   => $this->statut_id,
        ];

        if ($this->logo) {
            $data['logo_uri'] = $this->logo->store('compagnies', 'public');
        }

        if ($this->editingId) {
            Compagnie::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Compagnie mise à jour.');
        } else {
            Compagnie::create($data);
            session()->flash('success', 'Compagnie créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'sigle', 'slogant', 'description', 'statut_id', 'logo']);
    }

    public function delete(int $id): void
    {
        Compagnie::findOrFail($id)->delete();
        session()->flash('success', 'Compagnie supprimée.');
    }

    public function render()
    {
        $compagnies = Compagnie::query()
            ->with(['statut', 'user'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sigle', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        $statuts = Statut::all();

        return view('livewire.admin.compagnie-manager', compact('compagnies', 'statuts'));
    }
}
