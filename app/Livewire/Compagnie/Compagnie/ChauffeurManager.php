<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Models\Compagnie\Chauffer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class ChauffeurManager extends Component
{
    use WithPagination, WithFileUploads;

    public string  $search       = '';
    public bool    $showModal    = false;
    public ?string $editingId    = null;

    public string  $first_name     = '';
    public string  $last_name      = '';
    public string  $matricule      = '';
    public string  $telephone      = '';
    public string  $date_naissance = '';
    public string  $genre          = '';
    public string  $statut         = 'actif';
    public ?string $existingPhoto  = null;
    public         $photo          = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDocPanel(string $id): void
    {
        $c = Chauffer::findOrFail($id);
        $this->dispatch('open-doc-panel',
            type:     Chauffer::class,
            id:       $id,
            label:    $c->fullName(),
            typeName: 'Chauffeur',
        );
    }

    #[On('doc-panel-saved')]
    public function refreshDocCounts(): void {}

    public function openCreate(): void
    {
        $this->reset([
            'editingId', 'first_name', 'last_name', 'matricule', 'telephone',
            'date_naissance', 'genre', 'existingPhoto', 'photo',
        ]);
        $this->statut = 'actif';
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $c = Chauffer::findOrFail($id);

        $this->editingId      = $id;
        $this->first_name     = $c->first_name;
        $this->last_name      = $c->last_name;
        $this->matricule      = $c->matricule ?? '';
        $this->telephone      = $c->telephone ?? '';
        $this->date_naissance = $c->date_naissance ? $c->date_naissance->format('Y-m-d') : '';
        $this->genre          = $c->genre;
        $this->statut         = $c->statut;
        $this->existingPhoto  = $c->photo;
        $this->photo          = null;
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'matricule'      => [
                'nullable', 'string', 'max:50',
                Rule::unique('chauffers', 'matricule')->ignore($this->editingId, 'id'),
            ],
            'telephone'      => 'nullable|string|max:20',
            'date_naissance' => 'required|date',
            'genre'          => 'required|in:Homme,Femme',
            'statut'         => 'required|in:actif,inactif,suspendu',
            'photo'          => 'nullable|image|max:2048',
        ]);

        $data = [
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'matricule'      => $this->matricule ?: null,
            'telephone'      => $this->telephone ?: null,
            'date_naissance' => $this->date_naissance,
            'genre'          => $this->genre,
            'statut'         => $this->statut,
            'compagnie_id'   => Auth::user()->compagnie_id,
        ];

        if ($this->photo) {
            if ($this->editingId && $this->existingPhoto) {
                Storage::disk('public')->delete($this->existingPhoto);
            }
            $data['photo'] = $this->photo->store('chauffeurs', 'public');
        }

        if ($this->editingId) {
            Chauffer::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Chauffeur mis à jour.');
        } else {
            Chauffer::create($data);
            $this->dispatch('toast', type: 'success', message: 'Chauffeur ajouté.');
        }

        $this->showModal = false;
        $this->reset([
            'editingId', 'first_name', 'last_name', 'matricule', 'telephone',
            'date_naissance', 'genre', 'existingPhoto', 'photo',
        ]);
        $this->statut = 'actif';
    }

    public function delete(string $id): void
    {
        $c = Chauffer::findOrFail($id);
        if ($c->photo) {
            Storage::disk('public')->delete($c->photo);
        }
        $c->delete();
        $this->dispatch('toast', type: 'success', message: 'Chauffeur supprimé.');
    }

    public function render()
    {
        $chauffeurs = Chauffer::withCount('documents')
            ->where('compagnie_id', Auth::user()->compagnie_id)
            ->when($this->search, fn($q) => $q
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('matricule', 'like', "%{$this->search}%")
                ->orWhere('telephone', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(12);

        return view('livewire.compagnie.compagnie.chauffeur-manager', compact('chauffeurs'));
    }
}
