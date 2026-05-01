<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Enums\SexeUser;
use App\Enums\StatutUser;
use App\Mail\CompanyAccountActivationMail;
use App\Models\AccountActivation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class UserManager extends Component
{
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

    protected function rules(): array
    {
        $emailRule = $this->editingId
            ? 'required|email|unique:users,email,' . $this->editingId
            : 'required|email|unique:users,email';

        return [
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'email'              => $emailRule,
            'sexe'               => 'required|string',
            'numero'             => 'nullable|numeric',
            'numero_identifiant' => 'nullable|string|max:10',
            'selectedRoles'      => 'required|array|min:1',
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'first_name', 'last_name', 'email', 'sexe', 'numero', 'selectedRoles']);
        $this->numero_identifiant = '+226';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->sexe = $user->sexe?->value ?? $user->sexe ?? '';
        $this->numero = $user->numero ?? '';
        $this->numero_identifiant = $user->numero_identifiant ?? '+226';
        $this->selectedRoles = $user->roles()->pluck('roles.id')->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $compagnieId = Auth::user()->compagnie_id;

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update([
                'first_name'         => $this->first_name,
                'last_name'          => $this->last_name,
                'email'              => $this->email,
                'sexe'               => $this->sexe,
                'numero'             => $this->numero ?: null,
                'numero_identifiant' => $this->numero_identifiant,
                'name'               => $this->first_name . ' ' . $this->last_name,
            ]);
            $user->roles()->sync($this->selectedRoles);
            session()->flash('success', 'Utilisateur mis à jour.');
        } else {
            $password = Str::random(12);
            $user = User::create([
                'first_name'         => $this->first_name,
                'last_name'          => $this->last_name,
                'name'               => $this->first_name . ' ' . $this->last_name,
                'email'              => $this->email,
                'password'           => Hash::make($password),
                'sexe'               => $this->sexe,
                'numero'             => $this->numero ?: null,
                'numero_identifiant' => $this->numero_identifiant,
                'compagnie_id'       => $compagnieId,
                'statut'             => StatutUser::EnAttente->value,
            ]);

            $user->roles()->sync($this->selectedRoles);

            // Send activation email
            $activation = AccountActivation::create([
                'user_id'    => $user->id,
                'token'      => Str::random(64),
                'expires_at' => now()->addHours(24),
            ]);

            $companyName = Auth::user()->compagnie?->name ?? 'Votre entreprise';
            Mail::to($user->email)->send(new CompanyAccountActivationMail($user, $activation, $companyName));

            session()->flash('success', 'Utilisateur créé. Un email d\'activation a été envoyé.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'first_name', 'last_name', 'email', 'sexe', 'numero', 'selectedRoles']);
        $this->numero_identifiant = '+226';
    }

    public function bloquer(int $id): void
    {
        User::findOrFail($id)->update(['statut' => StatutUser::Bloquer->value]);
        session()->flash('success', 'Utilisateur bloqué.');
    }

    public function debloquer(int $id): void
    {
        User::findOrFail($id)->update(['statut' => StatutUser::Active->value]);
        session()->flash('success', 'Utilisateur débloqué.');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $users = User::where('compagnie_id', $compagnieId)
            ->when($this->search, fn ($q) =>
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
            )
            ->with('roles')
            ->latest()
            ->paginate(15);

        $sexes = SexeUser::cases();
        $roles = Role::orderBy('label')->get();

        return view('livewire.compagnie.compagnie.user-manager', compact('users', 'sexes', 'roles'));
    }
}
