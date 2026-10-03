<?php

namespace App\Traits;

use App\Rbac\ControleAcces;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôle de permission pour les actions d'un composant Livewire.
 *
 * Le middleware de route ne protège que la permission de l'écran. Une méthode d'action
 * est un point d'entrée HTTP à part entière : ouvrir l'écran des dépenses ne doit pas
 * suffire à en supprimer une.
 */
trait AutoriseLesActions
{
    protected function autoriser(string $permission, mixed $sujet = null): void
    {
        $autorise = app(ControleAcces::class)->autorise(
            Auth::user(),
            $permission,
            $sujet,
            ['origine' => static::class],
        );

        abort_unless($autorise, 403, 'Vous n\'avez pas l\'autorisation « '.$permission.' ».');
    }
}
