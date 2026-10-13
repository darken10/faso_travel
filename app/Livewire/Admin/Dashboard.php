<?php

namespace App\Livewire\Admin;

use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin-panel')]
class Dashboard extends Component
{
    public function render()
    {
        $totalCompagnies = Compagnie::count();
        $totalVoyages = Voyage::count();
        $totalTickets = Ticket::count();
        $totalUsers = \App\Models\User::where('role', '!=', null)->count();
        $actions = collect([
            ['platform.pays.manage', 'panel.admin.pays', 'Gérer les pays', 'Pays, régions et villes'],
            ['platform.compagnie.view', 'panel.admin.compagnies', 'Compagnies', 'Gestion des compagnies'],
            ['platform.settings.manage', 'panel.admin.settings', 'Paramètres', 'Configuration de la plateforme'],
        ])->filter(fn (array $action): bool => Auth::user()->hasPermission($action[0]))->values();

        return view('livewire.admin.dashboard', compact(
            'totalCompagnies',
            'totalVoyages',
            'totalTickets',
            'totalUsers',
            'actions',
        ));
    }
}
