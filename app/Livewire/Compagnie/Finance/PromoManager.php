<?php

namespace App\Livewire\Compagnie\Finance;

use App\Models\Finance\PromoCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class PromoManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $type = 'pourcentage';
    public string $valeur = '';
    public string $date_debut = '';
    public string $date_fin = '';
    public string $usage_limit = '';
    public string $min_montant = '';
    public bool $active = true;

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'code', 'valeur', 'date_debut', 'date_fin', 'usage_limit', 'min_montant']);
        $this->type = 'pourcentage';
        $this->active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $p = PromoCode::where('compagnie_id', Auth::user()->compagnie_id)->findOrFail($id);
        $this->editingId   = $p->id;
        $this->code        = $p->code;
        $this->type        = $p->type;
        $this->valeur      = (string) $p->valeur;
        $this->date_debut  = $p->date_debut?->toDateString() ?? '';
        $this->date_fin    = $p->date_fin?->toDateString() ?? '';
        $this->usage_limit = (string) ($p->usage_limit ?? '');
        $this->min_montant = (string) ($p->min_montant ?? '');
        $this->active      = $p->active;
        $this->showModal   = true;
    }

    public function save(): void
    {
        $compagnieId = Auth::user()->compagnie_id;

        $this->validate([
            'code'        => ['required', 'string', 'max:50', Rule::unique('promo_codes', 'code')->where('compagnie_id', $compagnieId)->ignore($this->editingId)],
            'type'        => 'required|in:pourcentage,montant',
            'valeur'      => 'required|integer|min:1' . ($this->type === 'pourcentage' ? '|max:100' : ''),
            'date_debut'  => 'nullable|date',
            'date_fin'    => 'nullable|date|after_or_equal:date_debut',
            'usage_limit' => 'nullable|integer|min:1',
            'min_montant' => 'nullable|integer|min:0',
        ]);

        $data = [
            'code'        => strtoupper(trim($this->code)),
            'type'        => $this->type,
            'valeur'      => (int) $this->valeur,
            'date_debut'  => $this->date_debut ?: null,
            'date_fin'    => $this->date_fin ?: null,
            'usage_limit' => $this->usage_limit !== '' ? (int) $this->usage_limit : null,
            'min_montant' => $this->min_montant !== '' ? (int) $this->min_montant : null,
            'active'      => $this->active,
        ];

        if ($this->editingId) {
            PromoCode::where('compagnie_id', $compagnieId)->findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Code promo mis à jour.');
        } else {
            PromoCode::create(array_merge($data, ['compagnie_id' => $compagnieId]));
            $this->dispatch('toast', type: 'success', message: 'Code promo créé.');
        }

        $this->showModal = false;
    }

    public function toggleActive(int $id): void
    {
        $p = PromoCode::where('compagnie_id', Auth::user()->compagnie_id)->findOrFail($id);
        $p->update(['active' => !$p->active]);
        $this->dispatch('toast', type: 'success', message: $p->active ? 'Code activé.' : 'Code désactivé.');
    }

    public function delete(int $id): void
    {
        PromoCode::where('compagnie_id', Auth::user()->compagnie_id)->findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Code promo supprimé.');
    }

    public function render()
    {
        $promos = PromoCode::where('compagnie_id', Auth::user()->compagnie_id)
            ->when($this->search, fn ($q) => $q->where('code', 'like', '%' . strtoupper($this->search) . '%'))
            ->latest()
            ->paginate(15);

        return view('livewire.compagnie.finance.promo-manager', compact('promos'));
    }
}
