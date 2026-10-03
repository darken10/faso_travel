<?php

namespace App\Traits;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Rbac\PermissionResolver;
use App\Rbac\PorteeVerifier;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

/**
 * Habilitation d'un compte.
 *
 * Tout contrôle d'accès s'écrit contre une permission, jamais contre un rôle : un rôle
 * n'est qu'un paquet de permissions, et il change au fil de la vie d'une compagnie.
 */
trait HasPermissions
{
    /** Dérogations individuelles, en plus ou en moins des rôles. */
    public function permissionsDerogees(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withPivot(['accordee', 'expire_at']);
    }

    /**
     * @return array<string, string> nom de la permission => portée
     */
    public function permissionsEffectives(): array
    {
        $version = Cache::get($this->clePermVersion(), 1);

        return Cache::remember(
            "user:{$this->getKey()}:permissions:v{$version}",
            (int) config('rbac.cache_ttl'),
            fn (): array => app(PermissionResolver::class)->resolve($this),
        );
    }

    public function hasPermission(string $permission, mixed $sujet = null): bool
    {
        if ($this->isRoot()) {
            return true;
        }

        $portee = $this->permissionsEffectives()[$permission] ?? null;

        if ($portee === null) {
            return false;
        }

        return app(PorteeVerifier::class)->autorise($this, $portee, $sujet);
    }

    /**
     * Comme `hasPermission()`, mais tolérant tant que les contrôles ne sont pas actifs.
     *
     * Réservé à l'affichage. Pendant la période d'observation, les routes laissent passer
     * et journalisent : masquer malgré tout l'entrée de menu priverait les métiers des
     * écrans dont on cherche justement à mesurer l'usage, et aucun refus ne serait jamais
     * observé. Un menu vide ferait conclure à une panne.
     */
    public function peutOuObserve(string $permission, mixed $sujet = null): bool
    {
        if ($this->hasPermission($permission, $sujet)) {
            return true;
        }

        return (bool) config('rbac.enforce') !== true;
    }

    /** Portée d'une permission pour ce compte, ou null s'il ne la détient pas. */
    public function porteePour(string $permission): ?string
    {
        if ($this->isRoot()) {
            return 'all';
        }

        return $this->permissionsEffectives()[$permission] ?? null;
    }

    public function isRoot(): bool
    {
        return $this->role === UserRole::Root;
    }

    /** Compte de l'équipe LIPTRA, par opposition à un compte de compagnie ou de client. */
    public function isPlatformUser(): bool
    {
        return in_array($this->role, [UserRole::Root, UserRole::Admin], true);
    }

    /**
     * Invalide le cache des permissions de ce compte.
     *
     * Par incrément de version plutôt que par `Cache::forget` : les stores `file` et
     * `database` ne gèrent pas les tags, et la clé exacte dépend de la version courante.
     */
    public function invaliderCachePermissions(): void
    {
        Cache::add($this->clePermVersion(), 1);
        Cache::increment($this->clePermVersion());
    }

    /**
     * Remplace les rôles du compte et invalide son cache d'habilitation.
     *
     * Passer par cette méthode plutôt que par `roles()->sync()` : sans l'invalidation, le
     * compte garderait ses anciens droits jusqu'à expiration du cache.
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->invaliderCachePermissions();
    }

    private function clePermVersion(): string
    {
        return "user:{$this->getKey()}:perm_version";
    }
}
