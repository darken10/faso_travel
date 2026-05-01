<?php

namespace App\Livewire\Admin;

use App\Enums\CompagnieSettingKey;
use App\Models\Compagnie\Compagnie;
use App\Models\CompagnieSetting;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class SettingsManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $compagnie_id = null;
    public string $key = '';
    public string $value = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'compagnie_id', 'key', 'value']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $setting = CompagnieSetting::findOrFail($id);
        $this->editingId = $id;
        $this->compagnie_id = $setting->compagnie_id;
        $this->key = $setting->key;
        $this->value = $setting->value;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'compagnie_id' => 'required|exists:compagnies,id',
            'key'          => 'required|string',
            'value'        => 'required|string|max:500',
        ]);

        $data = [
            'compagnie_id' => $this->compagnie_id,
            'key'          => $this->key,
            'value'        => $this->value,
        ];

        if ($this->editingId) {
            CompagnieSetting::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Paramètre mis à jour.');
        } else {
            CompagnieSetting::create($data);
            session()->flash('success', 'Paramètre créé.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'compagnie_id', 'key', 'value']);
    }

    public function delete(int $id): void
    {
        CompagnieSetting::findOrFail($id)->delete();
        session()->flash('success', 'Paramètre supprimé.');
    }

    public function render()
    {
        $settings = CompagnieSetting::query()
            ->with('compagnie')
            ->when($this->search, fn($q) => $q->whereHas('compagnie', fn($c) => $c->where('name', 'like', "%{$this->search}%"))
                ->orWhere('key', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        $compagnies = Compagnie::orderBy('name')->get();
        $keys = CompagnieSettingKey::cases();

        return view('livewire.admin.settings-manager', compact('settings', 'compagnies', 'keys'));
    }
}
