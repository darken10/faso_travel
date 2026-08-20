<?php

namespace App\Livewire\Admin;

use App\Models\Compagnie\Compagnie;
use App\Models\Statut;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class CompagnieManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public ?int $statutFilter = null;
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $sigle = '';
    public string $slogant = '';
    public string $description = '';
    public ?int $statut_id = 2;
    public $logo = null;
    public ?string $existingLogo = null;

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:compagnies,name,'.$this->editingId,
            'sigle'       => 'required|string|max:50|unique:compagnies,sigle,'.$this->editingId,
            'slogant'     => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'statut_id'   => 'required|exists:statuts,id',
            'logo'        => 'nullable|image|mimes:jpeg,jpg,png,webp,svg|max:2048',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'name.required'   => 'Le nom de la compagnie est obligatoire.',
            'name.unique'     => 'Une compagnie porte déjà ce nom.',
            'sigle.required'  => 'Le sigle est obligatoire.',
            'sigle.unique'    => 'Ce sigle est déjà utilisé.',
            'logo.image'      => 'Le logo doit être une image.',
            'logo.mimes'      => 'Formats acceptés : JPG, PNG, WEBP ou SVG.',
            'logo.max'        => 'Le logo ne doit pas dépasser 2 Mo.',
            'statut_id.required' => 'Choisissez un statut.',
        ];
    }

    /** Valide le logo dès son dépôt, sans attendre la soumission du formulaire. */
    public function updatedLogo(): void
    {
        $this->validateOnly('logo');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->statut_id = Statut::where('name', 'Activer')->value('id') ?? 2;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $compagnie = Compagnie::findOrFail($id);

        $this->resetForm();
        $this->editingId    = $id;
        $this->name         = $compagnie->name;
        $this->sigle        = $compagnie->sigle;
        $this->slogant      = $compagnie->slogant ?? '';
        $this->description  = $compagnie->description ?? '';
        $this->statut_id    = $compagnie->statut_id;
        $this->existingLogo = $compagnie->logo_uri;
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    /**
     * Retire le logo : annule d'abord le fichier en attente, sinon supprime
     * celui déjà enregistré (fichier sur le disque compris).
     */
    public function removeLogo(): void
    {
        if ($this->logo) {
            $this->reset('logo');

            return;
        }

        if (! $this->existingLogo) {
            return;
        }

        $ancien = $this->existingLogo;
        $this->existingLogo = null;

        if ($this->editingId) {
            Compagnie::findOrFail($this->editingId)->update(['logo_uri' => null]);
            $this->deleteStoredLogo($ancien);
            session()->flash('success', 'Logo supprimé.');
        }
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

        $compagnie = $this->editingId ? Compagnie::findOrFail($this->editingId) : null;
        $ancienLogo = $compagnie?->logo_uri;

        if ($this->logo) {
            $data['logo_uri'] = $this->logo->store('compagnies', 'public');
        } elseif ($compagnie && $this->existingLogo === null) {
            $data['logo_uri'] = null;
        }

        if ($compagnie) {
            $compagnie->update($data);
            session()->flash('success', 'Compagnie mise à jour.');
        } else {
            Compagnie::create($data);
            session()->flash('success', 'Compagnie créée.');
        }

        // Le fichier remplacé n'est effacé qu'une fois la base à jour. Sans clé
        // « logo_uri » dans $data, le logo n'a pas changé : il faut le conserver.
        if ($ancienLogo && array_key_exists('logo_uri', $data) && $data['logo_uri'] !== $ancienLogo) {
            $this->deleteStoredLogo($ancienLogo);
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $compagnie = Compagnie::findOrFail($id);
        $logo = $compagnie->logo_uri;

        $compagnie->delete();
        $this->deleteStoredLogo($logo);

        session()->flash('success', 'Compagnie supprimée.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sigle', 'slogant', 'description', 'statut_id', 'logo', 'existingLogo']);
        $this->resetErrorBag();
    }

    /** Supprime un logo du disque public, en ignorant les chemins vides. */
    private function deleteStoredLogo(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Change le statut d'une compagnie directement depuis sa carte. */
    public function changeStatut(int $id, int $statutId): void
    {
        $statut = Statut::findOrFail($statutId);
        $compagnie = Compagnie::findOrFail($id);

        if ($compagnie->statut_id === $statut->id) {
            return;
        }

        $compagnie->update(['statut_id' => $statut->id]);

        session()->flash('success', "« {$compagnie->name} » est maintenant au statut « {$statut->name} ».");
    }

    public function updatingStatutFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $compagnies = Compagnie::query()
            ->with(['statut', 'user'])
            ->withCount(['voyages', 'gares', 'users'])
            ->when($this->search, fn($q) => $q->where(fn($sub) => $sub->where('name', 'like', "%{$this->search}%")
                ->orWhere('sigle', 'like', "%{$this->search}%")))
            ->when($this->statutFilter, fn($q) => $q->where('statut_id', $this->statutFilter))
            ->latest()
            ->paginate(12);

        $statuts = Statut::orderBy('id')->get();

        return view('livewire.admin.compagnie-manager', compact('compagnies', 'statuts'));
    }
}
