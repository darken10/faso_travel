<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Garde-fou des écrans du panel compagnie.
 *
 * Toutes les propriétés publiques d'un composant Livewire sont modifiables
 * depuis le navigateur : un identifiant reçu dans `$editingId`, `$assigningId`
 * ou un paramètre de route n'est jamais une donnée de confiance. Chaque accès
 * par identifiant doit donc être restreint à la compagnie de l'utilisateur,
 * via le scope `ofCompagnie()` du modèle concerné.
 */
trait ScopedToCompagnie
{
    /** Identifiant de la compagnie de l'utilisateur connecté. */
    protected function compagnieId(): int
    {
        $compagnieId = Auth::user()?->compagnie_id;

        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        return (int) $compagnieId;
    }
}
