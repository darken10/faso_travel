<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Enums\StatutCare;
use App\Models\Compagnie\Care;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class CareManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $immatrculation = '';
    public string $number_place = '';
    public string $statut = '';
    public string $etat = '';
    public $image = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'immatrculation', 'number_place', 'statut', 'etat', 'image']);
        $this->statut = StatutCare::Disponible->value;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $care = Care::findOrFail($id);
        $this->editingId      = $id;
        $this->immatrculation = $care->immatrculation;
        $this->number_place   = $care->number_place;
        $this->statut         = $care->statut->value ?? '';
        $this->etat           = $care->etat ?? '';
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate([
            'immatrculation' => 'required|string|max:100',
            'number_place'   => 'required|integer|min:1',
            'statut'         => 'required|string',
            'etat'           => 'nullable|string|max:255',
            'image'          => 'nullable|image|max:2048',
        ]);

        $data = [
            'immatrculation' => $this->immatrculation,
            'number_place'   => $this->number_place,
            'statut'         => $this->statut,
            'etat'           => $this->etat ?: null,
        ];

        if ($this->image) {
            $data['image_uri'] = $this->image->store('cares', 'public');
        }

        if ($this->editingId) {
            Care::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Véhicule mis à jour.');
        } else {
            Care::create($data);
            session()->flash('success', 'Véhicule créé.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'immatrculation', 'number_place', 'statut', 'etat', 'image']);
    }

    public function delete(int $id): void
    {
        Care::findOrFail($id)->delete();
        session()->flash('success', 'Véhicule supprimé.');
    }

    public function render()
    {
        $cares = Care::query()
            ->when($this->search, fn($q) => $q->where('immatrculation', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        $statuts = StatutCare::cases();

        return view('livewire.compagnie.compagnie.care-manager', compact('cares', 'statuts'));
    }
}
