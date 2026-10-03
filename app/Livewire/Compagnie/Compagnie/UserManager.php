<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Enums\SexeUser;
use App\Enums\StatutUser;
use App\Mail\CompanyAccountActivationMail;
use App\Models\AccountActivation;
use App\Models\Compagnie\Gare;
use App\Models\Role;
use App\Models\User;
use App\Rbac\ReglesSeparation;
use App\Traits\AutoriseLesActions;
use App\Traits\JournaliseLesActions;
use App\Traits\ScopedToCompagnie;
use App\Traits\ScopedToGare;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class UserManager extends Component
{
    use AutoriseLesActions;
    use JournaliseLesActions;
    use ScopedToCompagnie;
    use ScopedToGare;
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $sexe = '';

    public string $numero = '';

    public string $numero_identifiant = '+226';

    public array $selectedRoles = [];

    public array $selectedGares = [];

    public ?int $garePrincipale = null;

    protected function rules(): array
    {
        $emailRule = $this->editingId
            ? 'required|email|unique:users,email,'.$this->editingId
            : 'required|email|unique:users,email';

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => $emailRule,
            'sexe' => 'required|string',
            'numero' => 'nullable|numeric',
            'numero_identifiant' => 'nullable|string|max:10',
            'selectedRoles' => 'required|array|min:1',
            // Seuls les rôles de compagnie sont attribuables ici, gabarits comme rôles
            // historiques. Restreindre aux six valeurs de CompanyRole excluait les rôles
            // gabarits, et un compte créé depuis cet écran se retrouvait avec un rôle sans
            // aucune permission. Les rôles plateforme restent inaccessibles : c'est la
            // protection contre l'élévation de privilèges.
            'selectedRoles.*' => [
                'integer',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query->where('scope', 'compagnie')
                        ->where(
                            fn ($q) => $q->whereNull('compagnie_id')
                                ->orWhere('compagnie_id', $this->compagnieId())
                        )
                ),
            ],
            'selectedGares' => 'array',
            // Une gare d'une autre compagnie ne peut pas être affectée : `selectedGares`
            // est une propriété publique Livewire, donc pilotable depuis le navigateur.
            'selectedGares.*' => [
                'integer',
                Rule::exists('gares', 'id')->where(
                    fn ($query) => $query->where('compagnie_id', $this->compagnieId())
                        ->orWhere('is_default', true)
                ),
            ],
            'garePrincipale' => ['nullable', 'integer', Rule::in($this->selectedGares)],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'first_name', 'last_name', 'email', 'sexe', 'numero', 'selectedRoles', 'selectedGares', 'garePrincipale']);
        $this->numero_identifiant = '+226';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::ofCompagnie($this->compagnieId())->findOrFail($id);
        $this->editingId = $id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->sexe = $user->sexe?->value ?? $user->sexe ?? '';
        $this->numero = $user->numero ?? '';
        $this->numero_identifiant = $user->numero_identifiant ?? '+226';
        $this->selectedRoles = $user->roles()->pluck('roles.id')->toArray();
        $this->selectedGares = $user->gareIds();
        $this->garePrincipale = $user->garePrincipale()?->id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->autoriser($this->editingId ? 'compagnie.user.update' : 'compagnie.user.create');
        $this->validate();
        $this->verifierSeparationDesPouvoirs();

        $compagnieId = Auth::user()->compagnie_id;

        if ($this->editingId) {
            $user = User::ofCompagnie($this->compagnieId())->findOrFail($this->editingId);
            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'sexe' => $this->sexe,
                'numero' => $this->numero ?: null,
                'numero_identifiant' => $this->numero_identifiant,
                'name' => $this->first_name.' '.$this->last_name,
            ]);
            $rolesAvant = $user->roles()->pluck('name')->sort()->values()->all();
            $user->syncRoles($this->selectedRoles);
            $user->syncGares($this->affectationsGares());
            $this->journaliserAttributionDeRoles($user, $rolesAvant);
            $this->dispatch('toast', type: 'success', message: 'Utilisateur mis à jour.');
        } else {
            $password = Str::random(12);
            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => $this->first_name.' '.$this->last_name,
                'email' => $this->email,
                'password' => Hash::make($password),
                'sexe' => $this->sexe,
                'numero' => $this->numero ?: null,
                'numero_identifiant' => $this->numero_identifiant,
                'compagnie_id' => $compagnieId,
                'statut' => StatutUser::EnAttente->value,
            ]);

            $user->syncRoles($this->selectedRoles);
            $user->syncGares($this->affectationsGares());
            $this->journaliserAttributionDeRoles($user, []);

            // Send activation email
            $activation = AccountActivation::create([
                'user_id' => $user->id,
                'token' => Str::random(64),
                'expires_at' => now()->addHours(24),
            ]);

            $companyName = Auth::user()->compagnie?->name ?? 'Votre entreprise';
            Mail::to($user->email)->send(new CompanyAccountActivationMail($user, $activation, $companyName));

            $this->dispatch('toast', type: 'success', message: 'Utilisateur créé. Un email d\'activation a été envoyé.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'first_name', 'last_name', 'email', 'sexe', 'numero', 'selectedRoles', 'selectedGares', 'garePrincipale']);
        $this->numero_identifiant = '+226';
    }

    /**
     * Refuse les cumuls dangereux, le dépassement de rang et l'auto-administration.
     *
     * Le contrôle est ici et non dans `rules()` : il porte sur la combinaison des rôles et
     * sur l'identité de l'auteur, pas sur la forme d'un champ.
     */
    private function verifierSeparationDesPouvoirs(): void
    {
        $roles = Role::whereIn('id', $this->selectedRoles)->get();

        $cible = $this->editingId
            ? User::ofCompagnie($this->compagnieId())->findOrFail($this->editingId)
            : new User;

        $erreurs = app(ReglesSeparation::class)->verifierAttribution(Auth::user(), $cible, $roles);

        if ($erreurs === []) {
            return;
        }

        foreach ($erreurs as $i => $erreur) {
            $this->addError('selectedRoles'.($i === 0 ? '' : '.'.$i), $erreur);
        }

        throw ValidationException::withMessages(['selectedRoles' => $erreurs[0]]);
    }

    /**
     * Journalise l'attribution de rôles et de gares.
     *
     * N'écrit rien si rien n'a bougé : une trace par enregistrement de formulaire noierait
     * les attributions réelles sous les modifications de numéro de téléphone.
     *
     * @param  list<string>  $rolesAvant
     */
    private function journaliserAttributionDeRoles(User $user, array $rolesAvant): void
    {
        $rolesApres = $user->roles()->pluck('name')->sort()->values()->all();

        if ($rolesAvant === $rolesApres) {
            return;
        }

        $this->journaliser(
            'compagnie.role.assign',
            $user,
            ['roles' => $rolesAvant],
            ['roles' => $rolesApres, 'gares' => $user->gareIds()],
        );
    }

    /**
     * Affectations de gare au format attendu par le pivot.
     *
     * @return array<int, array{is_principale: bool}>
     */
    private function affectationsGares(): array
    {
        $affectations = [];

        foreach ($this->selectedGares as $gareId) {
            $affectations[(int) $gareId] = [
                'is_principale' => (int) $gareId === $this->garePrincipale,
            ];
        }

        return $affectations;
    }

    public function bloquer(int $id): void
    {
        $this->autoriser('compagnie.user.disable');
        $this->changerStatut($id, StatutUser::Bloquer);
        $this->dispatch('toast', type: 'success', message: 'Utilisateur bloqué.');
    }

    public function debloquer(int $id): void
    {
        $this->autoriser('compagnie.user.disable');
        $this->changerStatut($id, StatutUser::Active);
        $this->dispatch('toast', type: 'success', message: 'Utilisateur débloqué.');
    }

    /**
     * Change le statut d'un membre de l'équipe.
     *
     * `statut` est volontairement absent du `$fillable` de User — l'ajouter
     * l'exposerait à l'affectation de masse des formulaires d'inscription. On
     * assigne donc l'attribut directement ; un `update()` était ici sans effet.
     */
    private function changerStatut(int $id, StatutUser $statut): void
    {
        $user = User::ofCompagnie($this->compagnieId())->findOrFail($id);
        $user->statut = $statut->value;
        $user->save();
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $users = User::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) => $q->where('first_name', 'like', '%'.$this->search.'%')
                ->orWhere('last_name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
            )
            ->with(['roles', 'gares'])
            ->latest()
            ->paginate(15);

        $sexes = SexeUser::cases();
        $roles = Role::where('scope', 'compagnie')
            ->where(fn ($q) => $q->whereNull('compagnie_id')->orWhere('compagnie_id', $compagnieId))
            ->orderBy('label')
            ->get();
        $gares = Gare::orderBy('name')->get(['id', 'name', 'is_default']);

        return view('livewire.compagnie.compagnie.user-manager', compact('users', 'sexes', 'roles', 'gares'));
    }
}
