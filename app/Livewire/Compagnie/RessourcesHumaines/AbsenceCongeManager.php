<?php

namespace App\Livewire\Compagnie\RessourcesHumaines;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class AbsenceCongeManager extends Component
{
    public function render()
    {
        return view('livewire.compagnie.ressources-humaines.absence-conge-manager');
    }
}
