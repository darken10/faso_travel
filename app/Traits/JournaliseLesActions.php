<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Raccourci de journalisation pour les classes qui *agissent* — composants Livewire,
 * services métier, contrôleurs.
 *
 * À ne pas confondre avec un trait posé sur le modèle audité : ici c'est l'auteur de
 * l'action qui écrit la trace, parce que lui seul connaît le nom de l'action métier. Un
 * observateur Eloquent saurait dire « une dépense a été supprimée », pas « une dépense a
 * été supprimée depuis l'écran des dépenses par un comptable ».
 */
trait JournaliseLesActions
{
    /**
     * @param  array<string, mixed>  $avant
     * @param  array<string, mixed>  $apres
     */
    protected function journaliser(
        string $action,
        ?Model $cible = null,
        array $avant = [],
        array $apres = [],
        ?string $motif = null,
    ): ?AuditLog {
        return app(AuditLogger::class)->log($action, $cible, $avant, $apres, $motif);
    }

    /**
     * Journalise une modification en ne retenant que les attributs réellement changés.
     *
     * @param  array<string, mixed>  $avant
     */
    protected function journaliserModification(
        string $action,
        Model $cible,
        array $avant,
        ?string $motif = null,
    ): ?AuditLog {
        return app(AuditLogger::class)->logModification($action, $cible, $avant, $motif);
    }
}
