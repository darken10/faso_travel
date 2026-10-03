<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Écrit la piste d'audit des actions sensibles.
 *
 * Le service ne lève jamais d'exception : une trace qu'on n'arrive pas à écrire ne doit
 * pas annuler la vente, le remboursement ou l'arbitrage qu'elle documentait. Un échec
 * part sur le canal de log dédié, où il reste visible.
 */
class AuditLogger
{
    /**
     * Attributs jamais recopiés dans une trace.
     *
     * Journaliser l'attribution d'un rôle revient à écrire l'état d'un compte : sans ce
     * filtre, un hachage de mot de passe et un secret de double authentification
     * finiraient en clair dans une table que des comptes d'audit peuvent lire.
     */
    private const ATTRIBUTS_MASQUES = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'token',
        'secret',
        'access_token',
        'refresh_token',
    ];

    /**
     * @param  array<string, mixed>  $avant
     * @param  array<string, mixed>  $apres
     */
    public function log(
        string $action,
        ?Model $cible = null,
        array $avant = [],
        array $apres = [],
        ?string $motif = null,
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'user_id' => Auth::id(),
                'compagnie_id' => $this->compagnie($cible),
                'action' => $action,
                'auditable_type' => $cible !== null ? $cible->getMorphClass() : null,
                'auditable_id' => $cible?->getKey(),
                'avant' => $this->filtrer($avant),
                'apres' => $this->filtrer($apres),
                'motif' => $motif,
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
            ]);
        } catch (Throwable $e) {
            Log::channel(config('rbac.log_channel'))->error('Écriture de piste d\'audit impossible.', [
                'action' => $action,
                'auditable_type' => $cible !== null ? $cible->getMorphClass() : null,
                'auditable_id' => $cible?->getKey(),
                'user_id' => Auth::id(),
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Journalise une modification en comparant l'état avant et l'état courant.
     *
     * Ne retient que les attributs qui ont réellement changé : une trace qui recopie
     * trente colonnes identiques est illisible au moment où on en a besoin.
     *
     * `$avant` doit provenir de `attributesToArray()`, et la comparaison se fait contre
     * `attributesToArray()` : comparer un instantané sérialisé à des attributs bruts fait
     * passer toute colonne castée — une date, un enum — pour modifiée alors qu'elle ne
     * l'est pas.
     *
     * @param  array<string, mixed>  $avant
     */
    public function logModification(string $action, Model $cible, array $avant, ?string $motif = null): ?AuditLog
    {
        $apres = [];

        foreach ($cible->attributesToArray() as $cle => $valeur) {
            if (! array_key_exists($cle, $avant) || $this->valeur($avant[$cle]) !== $this->valeur($valeur)) {
                $apres[$cle] = $valeur;
            }
        }

        return $this->log($action, $cible, array_intersect_key($avant, $apres), $apres, $motif);
    }

    private function compagnie(?Model $cible): ?int
    {
        $compagnieId = Auth::user()?->compagnie_id
            ?? $cible?->getAttribute('compagnie_id');

        return $compagnieId === null ? null : (int) $compagnieId;
    }

    /**
     * @param  array<string, mixed>  $attributs
     * @return array<string, mixed>|null
     */
    private function filtrer(array $attributs): ?array
    {
        if ($attributs === []) {
            return null;
        }

        foreach (array_keys($attributs) as $cle) {
            if (in_array($cle, self::ATTRIBUTS_MASQUES, true)) {
                $attributs[$cle] = '[masqué]';
            }
        }

        return $attributs;
    }

    private function ip(): ?string
    {
        // En console (commande artisan, job en file) il n'y a pas de requête HTTP.
        return app()->runningInConsole() ? null : Request::ip();
    }

    private function userAgent(): ?string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        $agent = Request::userAgent();

        return $agent === null ? null : mb_substr($agent, 0, 255);
    }

    private function valeur(mixed $valeur): string
    {
        return is_scalar($valeur) || $valeur === null
            ? (string) $valeur
            : json_encode($valeur);
    }
}
