<?php

namespace App\Livewire\Admin;

use App\Models\Ville\Pays;
use App\Models\Ville\Region;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class PaysManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $showRegionsModal = false;
    public ?int $editingId = null;
    public ?int $selectedPaysId = null;

    // Pays form
    public string $name = '';
    public string $money = '';
    public string $identity_number = '';
    public string $iso2 = '';

    // Region inline form
    public string $region_name = '';
    public bool $showRegionForm = false;
    public ?int $editingRegionId = null;

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'money'           => 'required|string|max:10',
            'identity_number' => 'required|numeric',
            'iso2'            => 'required|string|max:5',
        ];
    }

    protected $messages = [
        'name.required'            => 'Le nom est obligatoire.',
        'money.required'           => 'La devise est obligatoire.',
        'identity_number.required' => 'Le numéro identifiant est obligatoire.',
        'iso2.required'            => 'Le code ISO est obligatoire.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'money', 'identity_number', 'iso2']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $pays = Pays::findOrFail($id);
        $this->editingId = $id;
        $this->name = $pays->name;
        $this->money = $pays->money;
        $this->identity_number = $pays->identity_number;
        $this->iso2 = $pays->iso2;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'            => $this->name,
            'money'           => $this->money,
            'identity_number' => $this->identity_number,
            'iso2'            => $this->iso2,
        ];

        if ($this->editingId) {
            Pays::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Pays mis à jour avec succès.');
        } else {
            Pays::create($data);
            session()->flash('success', 'Pays créé avec succès.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'money', 'identity_number', 'iso2']);
    }

    public function delete(int $id): void
    {
        Pays::findOrFail($id)->delete();
        session()->flash('success', 'Pays supprimé.');
    }

    // ─── Gestion des régions inline ───
    public function openRegions(int $paysId): void
    {
        $this->selectedPaysId = $paysId;
        $this->reset(['region_name', 'editingRegionId', 'showRegionForm']);
        $this->showRegionsModal = true;
    }

    public function openRegionForm(): void
    {
        $this->reset(['region_name', 'editingRegionId']);
        $this->showRegionForm = true;
    }

    public function editRegion(int $id): void
    {
        $region = Region::findOrFail($id);
        $this->editingRegionId = $id;
        $this->region_name = $region->name;
        $this->showRegionForm = true;
    }

    public function saveRegion(): void
    {
        $this->validateOnly('region_name', ['region_name' => 'required|string|max:255']);

        $data = ['name' => $this->region_name, 'pays_id' => $this->selectedPaysId];

        if ($this->editingRegionId) {
            Region::findOrFail($this->editingRegionId)->update($data);
        } else {
            Region::create($data);
        }

        $this->reset(['region_name', 'editingRegionId', 'showRegionForm']);
    }

    public function deleteRegion(int $id): void
    {
        Region::findOrFail($id)->delete();
    }

    public function render()
    {
        $pays = Pays::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('iso2', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $regions = $this->selectedPaysId
            ? Region::where('pays_id', $this->selectedPaysId)->orderBy('name')->get()
            : collect();

        $selectedPays = $this->selectedPaysId ? Pays::find($this->selectedPaysId) : null;

        return view('livewire.admin.pays-manager', compact('pays', 'regions', 'selectedPays'));
    }
}
