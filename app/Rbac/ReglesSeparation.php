<?php

namespace App\Rbac;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Séparation des pouvoirs à l'attribution des rôles.
 *
 * Vendre, encaisser, contrôler et arbitrer sont quatre fonctions qui ne doivent pas se
 * cumuler sur un même compte. Ces règles sont refusées à l'attribution, et non
 * déconseillées dans une note : un cumul qui tient trois mois devient une habitude.
 */
class ReglesSeparation
{
    /**
     * Paires de rôles qui ne peuvent pas coexister, avec la raison.
     *
     * La raison est affichée telle quelle : « interdit » n'apprend rien à qui essaie, et
     * la personne finira par contourner la règle faute de la comprendre.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const CUMULS_INTERDITS = [
        ['guichetier', 'chef_caisse', 'le vendeur validerait ses propres écarts de caisse'],
        ['guichetier', 'comptable', 'encaissement et écriture comptable sur le même compte'],
        ['guichetier', 'agent_embarquement', 'vendre et contrôler le même siège rend un siège revendu indétectable'],
        ['agent_embarquement', 'comptable', "l'agent arbitrerait les conflits qu'il a lui-même générés"],
        ['comptable', 'auditeur', "un auditeur qui écrit n'audite plus"],
    ];

    /**
     * Vérifie un ensemble de rôles destiné à un même compte.
     *
     * @param  Collection<int, Role>|list<Role>  $roles
     * @return list<string> raisons de refus, vide si l'ensemble est acceptable
     */
    public function verifierEnsemble(Collection|array $roles): array
    {
        $roles = $roles instanceof Collection ? $roles : collect($roles);
        $noms = $roles->pluck('name')->all();
        $erreurs = [];

        foreach (self::CUMULS_INTERDITS as [$a, $b, $raison]) {
            if (in_array($a, $noms, true) && in_array($b, $noms, true)) {
                $erreurs[] = sprintf('« %s » et « %s » ne se cumulent pas : %s.', $a, $b, $raison);
            }
        }

        $plateforme = $roles->where('scope', 'platform')->pluck('name');
        $compagnie = $roles->where('scope', 'compagnie')->pluck('name');

        if ($plateforme->isNotEmpty() && $compagnie->isNotEmpty()) {
            $erreurs[] = sprintf(
                'Un rôle plateforme (« %s ») ne se cumule pas avec un rôle de compagnie (« %s ») : '
                .'un employé de compagnie ne doit jamais porter un rôle plateforme.',
                $plateforme->implode('», «'),
                $compagnie->implode('», «'),
            );
        }

        return $erreurs;
    }

    /**
     * Vérifie qu'un auteur peut attribuer cet ensemble de rôles.
     *
     * @param  Collection<int, Role>|list<Role>  $roles
     * @return list<string>
     */
    public function verifierAttribution(User $auteur, User $cible, Collection|array $roles): array
    {
        $roles = $roles instanceof Collection ? $roles : collect($roles);
        $erreurs = $this->verifierEnsemble($roles);

        if ($auteur->getKey() === $cible->getKey()) {
            $erreurs[] = 'Personne ne modifie ses propres rôles : faites-le faire par un autre compte.';

            return $erreurs;
        }

        if ($auteur->isRoot()) {
            return $erreurs;
        }

        $rangAuteur = $this->rangEffectif($auteur);

        foreach ($roles as $role) {
            if ((int) $role->rang >= $rangAuteur) {
                $erreurs[] = sprintf(
                    'On n\'attribue qu\'un rôle de rang inférieur au sien : « %s » (rang %d) '
                    .'est au moins au niveau du vôtre (rang %d).',
                    $role->name,
                    (int) $role->rang,
                    $rangAuteur,
                );
            }
        }

        return $erreurs;
    }

    /**
     * Rang effectif d'un compte : ses rôles attachés, ou son rôle système à défaut.
     *
     * Le second terme n'est pas un confort. Entre le déploiement et le passage de
     * `rbac:migrate-roles`, un patron de compagnie porte `users.role = Companie Bosse`
     * sans aucune ligne dans `role_user` : s'en tenir aux rôles attachés lui donnerait le
     * rang 0 et l'empêcherait d'administrer sa propre équipe.
     */
    public function rangEffectif(User $user): int
    {
        return max(
            $this->rangMaximum($user->roles),
            $this->rangDuRoleSysteme($user),
        );
    }

    /** Rang implicite de la colonne `users.role`, tant qu'elle reste discriminante. */
    private function rangDuRoleSysteme(User $user): int
    {
        return match ($user->role) {
            UserRole::Root => 100,
            UserRole::Admin => 90,
            UserRole::CompagnieBosse => 80,
            default => 0,
        };
    }

    /**
     * Rang le plus élevé d'un ensemble de rôles.
     *
     * @param  Collection<int, Role>|list<Role>  $roles
     */
    public function rangMaximum(Collection|array $roles): int
    {
        $roles = $roles instanceof Collection ? $roles : collect($roles);

        return (int) ($roles->max('rang') ?? 0);
    }

    /**
     * Règle des quatre yeux : l'auteur d'une pièce ne l'approuve pas.
     *
     * Séparer `create` de `approve` dans le catalogue ne suffit pas : un compte peut
     * légitimement porter les deux permissions, et c'est alors sur la pièce elle-même que
     * la règle doit se jouer.
     */
    public function peutApprouver(User $user, Model $piece): bool
    {
        $auteur = $piece->getAttribute('user_id') ?? $piece->getAttribute('created_by');

        if ($auteur === null) {
            // Pièce sans auteur connu : on ne peut pas garantir la séparation, donc on
            // refuse. Un doute sur l'origine d'une écriture financière ne s'arbitre pas
            // en faveur de l'approbation.
            return false;
        }

        return (int) $auteur !== (int) $user->getKey();
    }
}
