<?php

namespace App\Rbac;

use App\Models\User;

/**
 * Calcule les permissions effectives d'un compte.
 *
 * Ordre de résolution, dans cet ordre exact :
 *   1. les permissions de tous ses rôles, en gardant la portée la plus large ;
 *   2. les dérogations accordées (`permission_user.accordee = true`) non expirées ;
 *   3. les retraits explicites (`permission_user.accordee = false`) non expirés, qui
 *      effacent la permission même si un rôle la porte.
 *
 * Le cas `root` n'est pas traité ici : il est court-circuité en amont par `Gate::before`,
 * pour qu'un compte racine reste opérationnel même si la base de permissions est vide.
 */
class PermissionResolver
{
    /**
     * @return array<string, string> nom de la permission => portée
     */
    public function resolve(User $user): array
    {
        $effectives = $this->permissionsDesRoles($user);

        foreach ($this->derogations($user) as $nom => $derogation) {
            if ($derogation['accordee']) {
                $effectives[$nom] = $derogation['portee'];

                continue;
            }

            unset($effectives[$nom]);
        }

        return $effectives;
    }

    /**
     * @return array<string, string>
     */
    private function permissionsDesRoles(User $user): array
    {
        $effectives = [];

        foreach ($user->roles()->with('permissions')->get() as $role) {
            foreach ($role->permissions as $permission) {
                $portee = $permission->pivot->portee ?? 'compagnie';

                $effectives[$permission->name] = isset($effectives[$permission->name])
                    ? PorteeVerifier::plusLarge($effectives[$permission->name], $portee)
                    : $portee;
            }
        }

        return $effectives;
    }

    /**
     * Dérogations individuelles encore valides.
     *
     * La table `permission_user` ne porte pas de colonne de portée : une dérogation
     * accordée prend la portée naturelle du domaine de la permission. Lui donner la portée
     * `all` ouvrirait à un employé de compagnie un droit d'agir sur les autres compagnies,
     * ce qu'une dérogation ponctuelle n'est jamais censée faire.
     *
     * @return array<string, array{accordee: bool, portee: string}>
     */
    private function derogations(User $user): array
    {
        $derogations = [];

        $lignes = $user->permissionsDerogees()
            ->where(function ($query) {
                $query->whereNull('permission_user.expire_at')
                    ->orWhere('permission_user.expire_at', '>', now());
            })
            ->get();

        foreach ($lignes as $permission) {
            $derogations[$permission->name] = [
                'accordee' => (bool) $permission->pivot->accordee,
                'portee' => match ($permission->scope) {
                    'platform' => 'all',
                    'client' => 'own',
                    default => 'compagnie',
                },
            ];
        }

        return $derogations;
    }
}
