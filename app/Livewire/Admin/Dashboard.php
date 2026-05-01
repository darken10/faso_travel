<?php

namespace App\Livewire\Admin;

use App\Helper\QueryHelpers;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
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

        return view('livewire.admin.dashboard', compact(
            'totalCompagnies',
            'totalVoyages',
            'totalTickets',
            'totalUsers',
        ));
    }
}
