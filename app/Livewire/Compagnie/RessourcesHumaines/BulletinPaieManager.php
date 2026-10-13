<?php

namespace App\Livewire\Compagnie\RessourcesHumaines;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class BulletinPaieManager extends Component
{
    public function render()
    {
        return view('livewire.compagnie.ressources-humaines.bulletin-paie-manager');
    }
}
