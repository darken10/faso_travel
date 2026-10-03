<?php

namespace App\Livewire\Compagnie\Habilitation;

use App\Enums\PermissionDomaine;
use App\Models\Permission;
use App\Models\Role;
use App\Rbac\ReglesSeparation;
use App\Traits\AutoriseLesActions;
use App\Traits\JournaliseLesActions;
use App\Traits\ScopedToCompagnie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Administration des rôles propres à une compagnie.
 *
 * Les dix-neuf rôles gabarits sont livrés avec le code et ne se modifient pas depuis cet
 * écran : une compagnie en dérive les siens. Sans cela, toute adaptation du découpage
 * métier d'un transporteur passerait par un déploiement.
 */
#[Layout('layouts.compagnie-panel')]
class RoleManager extends Component
{
    use AutoriseLesActions;
    use JournaliseLesActions;
    use ScopedToCompagnie;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $description = '';

    public int $rang = 10;

    /** @var array<string, string> nom de permission => portée */
    public array $selectedPermissions = [];

    public string $domaineOuvert = '';

    protected function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'rang' => ['required', 'integer', 'min:0', 'max:99'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [Rule::in(['compagnie', 'gare', 'own'])],
        ];
    }

    public function mount(): void
    {
        $this->autoriser('compagnie.role.manage');
        $this->domaineOuvert = PermissionDomaine::Guichet->value;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'label', 'description', 'selectedPermissions']);
        $this->rang = 10;
        $this->showModal = true;
    }

    /**
     * Prépare un nouveau rôle à partir d'un gabarit.
     *
     * Partir d'un gabarit plutôt que d'une page blanche : un rôle construit de zéro oublie
     * toujours une permission de lecture, et l'écran paraît cassé à son titulaire.
     */
    public function derive(int $gabaritId): void
    {
        $this->autoriser('compagnie.role.manage');

        $gabarit = Role::whereNull('compagnie_id')->where('is_system', true)->findOrFail($gabaritId);

        $this->reset(['editingId']);
        $this->label = $gabarit->label.' (adapté)';
        $this->description = $gabarit->description ?? '';
        $this->rang = min((int) $gabarit->rang, $this->rangMaximumAttribuable());
        $this->selectedPermissions = $gabarit->permissions
            ->mapWithKeys(fn (Permission $p): array => [$p->name => $p->pivot->portee])
            ->all();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $role = $this->roleDeLaCompagnie($id);

        $this->editingId = $role->id;
        $this->label = (string) $role->label;
        $this->description = (string) $role->description;
        $this->rang = (int) $role->rang;
        $this->selectedPermissions = $role->permissions
            ->mapWithKeys(fn (Permission $p): array => [$p->name => $p->pivot->portee])
            ->all();
        $this->showModal = true;
    }

    public function togglePermission(string $nom): void
    {
        if (isset($this->selectedPermissions[$nom])) {
            unset($this->selectedPermissions[$nom]);

            return;
        }

        $this->selectedPermissions[$nom] = 'compagnie';
    }

    public function save(): void
    {
        $this->autoriser('compagnie.role.manage');
        $this->validate();

        if ($this->rang > $this->rangMaximumAttribuable()) {
            $this->addError('rang', sprintf(
                'Un rôle ne peut pas être créé au-dessus du vôtre : rang %d maximum.',
                $this->rangMaximumAttribuable()
            ));

            return;
        }

        $compagnieId = $this->compagnieId();

        $role = $this->editingId
            ? $this->roleDeLaCompagnie($this->editingId)
            : new Role(['compagnie_id' => $compagnieId, 'scope' => 'compagnie', 'is_system' => false]);

        $avant = $role->exists ? $role->permissions->pluck('name')->sort()->values()->all() : [];

        $role->fill([
            'name' => $role->name ?? $this->nomTechnique($compagnieId),
            'label' => $this->label,
            'description' => $this->description ?: null,
            'rang' => $this->rang,
        ])->save();

        $ids = Permission::whereIn('name', array_keys($this->selectedPermissions))->pluck('id', 'name');
        $pivot = [];

        foreach ($this->selectedPermissions as $nom => $portee) {
            if (isset($ids[$nom])) {
                $pivot[$ids[$nom]] = ['portee' => $portee];
            }
        }

        $role->permissions()->sync($pivot);

        $this->journaliser(
            'compagnie.role.manage',
            $role,
            ['permissions' => $avant],
            ['permissions' => array_keys($this->selectedPermissions), 'rang' => $this->rang],
        );

        $this->showModal = false;
        $this->reset(['editingId', 'label', 'description', 'selectedPermissions']);
        $this->dispatch('toast', type: 'success', message: 'Rôle enregistré.');
    }

    public function delete(int $id): void
    {
        $this->autoriser('compagnie.role.manage');

        $role = $this->roleDeLaCompagnie($id);

        if ($role->users()->exists()) {
            $this->dispatch('toast', type: 'error', message: 'Ce rôle est encore attribué : retirez-le d\'abord des comptes concernés.');

            return;
        }

        $this->journaliser('compagnie.role.manage', $role, ['supprime' => $role->label]);
        $role->permissions()->detach();
        $role->delete();

        $this->dispatch('toast', type: 'success', message: 'Rôle supprimé.');
    }

    /**
     * Un rôle système n'est jamais modifiable, et un rôle d'une autre compagnie n'est
     * même pas visible : `$id` arrive d'une propriété publique Livewire.
     */
    private function roleDeLaCompagnie(int $id): Role
    {
        return Role::where('compagnie_id', $this->compagnieId())
            ->where('is_system', false)
            ->findOrFail($id);
    }

    /** Rang plafond : strictement sous celui de l'auteur, sauf pour un compte racine. */
    private function rangMaximumAttribuable(): int
    {
        if (Auth::user()->isRoot()) {
            return 99;
        }

        return max(0, app(ReglesSeparation::class)->rangEffectif(Auth::user()) - 1);
    }

    /** Nom technique stable, unique par compagnie. */
    private function nomTechnique(int $compagnieId): string
    {
        return 'c'.$compagnieId.'_'.substr(md5($this->label.microtime()), 0, 12);
    }

    public function render()
    {
        $compagnieId = $this->compagnieId();

        return view('livewire.compagnie.habilitation.role-manager', [
            'gabarits' => Role::whereNull('compagnie_id')
                ->where('is_system', true)
                ->where('scope', 'compagnie')
                ->withCount('permissions')
                ->orderByDesc('rang')
                ->get(),
            'rolesCompagnie' => Role::where('compagnie_id', $compagnieId)
                ->withCount(['permissions', 'users'])
                ->orderByDesc('rang')
                ->get(),
            'domaines' => PermissionDomaine::cases(),
            'permissionsDuDomaine' => Permission::where('domaine', $this->domaineOuvert)
                ->where('scope', 'compagnie')
                ->orderBy('name')
                ->get(),
            'rangMaximum' => $this->rangMaximumAttribuable(),
            'cumulsInterdits' => ReglesSeparation::CUMULS_INTERDITS,
        ]);
    }
}
