<?php

namespace App\Console\Commands;

use App\Http\Middleware\AuthorizeOrObserve;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Agrège les refus d'autorisation observés, pour décider quand fermer les accès.
 *
 * C'est la liste de travail avant de passer `RBAC_ENFORCE` à `true` : chaque ligne est
 * soit une habilitation à corriger, soit un écran dont on avait mal estimé l'usage.
 */
class RbacRefusalsReport extends Command
{
    protected $signature = 'rbac:refusals
                            {--days=14 : Profondeur d\'analyse en jours}
                            {--permission= : Limite à une permission}
                            {--limit=50 : Nombre de lignes affichées}';

    protected $description = 'Agrège les refus d\'autorisation journalisés en mode observation.';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('days'));
        $depuis = CarbonImmutable::now()->subDays($jours)->startOfDay();

        $fichiers = $this->fichiers($depuis);

        if ($fichiers === []) {
            $this->warn('Aucun journal RBAC sur les '.$jours.' derniers jours.');
            $this->line('Chemin attendu : '.$this->motifDeFichiers());

            return self::SUCCESS;
        }

        [$refus, $illisibles] = $this->lire($fichiers, $depuis);

        if ($refus === []) {
            $this->info('Aucun refus observé sur les '.$jours.' derniers jours.');
            $this->line('Si les écrans sont bien protégés, RBAC_ENFORCE peut passer à true.');

            return self::SUCCESS;
        }

        $this->restituer($refus, $jours, $illisibles);

        return self::SUCCESS;
    }

    /**
     * Fichiers journaliers couvrant la période.
     *
     * @return list<string>
     */
    private function fichiers(CarbonImmutable $depuis): array
    {
        $fichiers = [];
        $prefixe = preg_quote($this->prefixeDeFichier(), '/');

        foreach (glob($this->motifDeFichiers()) ?: [] as $chemin) {
            if (! preg_match('/'.$prefixe.'-(\d{4}-\d{2}-\d{2})\.log$/', $chemin, $m)) {
                continue;
            }

            if (CarbonImmutable::parse($m[1])->endOfDay()->greaterThanOrEqualTo($depuis)) {
                $fichiers[] = $chemin;
            }
        }

        sort($fichiers);

        return $fichiers;
    }

    /**
     * Chemin du canal de journalisation, tel que configuré.
     *
     * Dérivé de `logging.channels.*.path` et non codé en dur : le canal et son dossier
     * restent réglables, et un test peut pointer ailleurs que le vrai dossier de logs.
     */
    private function cheminDuCanal(): string
    {
        $canal = (string) config('rbac.log_channel');

        return (string) config(
            'logging.channels.'.$canal.'.path',
            storage_path('logs/'.$canal.'.log')
        );
    }

    private function prefixeDeFichier(): string
    {
        return pathinfo($this->cheminDuCanal(), PATHINFO_FILENAME);
    }

    private function motifDeFichiers(): string
    {
        return dirname($this->cheminDuCanal()).'/'.$this->prefixeDeFichier().'-*.log';
    }

    /**
     * Compte les refus par couple permission / rôles.
     *
     * @param  list<string>  $fichiers
     * @return array{0: array<string, array{permission: string, roles: string, nombre: int, dernier: string}>, 1: int}
     */
    private function lire(array $fichiers, CarbonImmutable $depuis): array
    {
        $refus = [];
        $illisibles = 0;
        $filtre = $this->option('permission');

        foreach ($fichiers as $chemin) {
            foreach (preg_split('/\R/', File::get($chemin)) ?: [] as $ligne) {
                if (! str_contains($ligne, AuthorizeOrObserve::MARQUEUR)) {
                    continue;
                }

                $contexte = $this->contexte($ligne);

                if ($contexte === null) {
                    $illisibles++;

                    continue;
                }

                if (isset($contexte['at']) && CarbonImmutable::parse($contexte['at'])->lessThan($depuis)) {
                    continue;
                }

                $permission = $contexte['permission'] ?? '(inconnue)';

                if ($filtre !== null && $permission !== $filtre) {
                    continue;
                }

                $roles = $contexte['roles'] === [] ? '(aucun)' : implode(', ', $contexte['roles']);
                $cle = $permission.'|'.$roles;

                $refus[$cle] ??= [
                    'permission' => $permission,
                    'roles' => $roles,
                    'nombre' => 0,
                    'dernier' => '',
                ];
                $refus[$cle]['nombre']++;
                $refus[$cle]['dernier'] = max($refus[$cle]['dernier'], $contexte['at'] ?? '');
            }
        }

        return [$refus, $illisibles];
    }

    /**
     * Contexte JSON d'une ligne de log.
     *
     * Le formateur de Laravel accole le contexte en JSON à la fin de la ligne. On remonte
     * depuis la première accolade qui suit le marqueur, plutôt que de découper sur des
     * positions fixes : le préfixe horodaté change de longueur selon le fuseau.
     */
    private function contexte(string $ligne): ?array
    {
        $debut = strpos($ligne, '{', strpos($ligne, AuthorizeOrObserve::MARQUEUR));

        if ($debut === false) {
            return null;
        }

        $contexte = json_decode(substr($ligne, $debut), true);

        return is_array($contexte) && isset($contexte['permission']) ? $contexte : null;
    }

    /**
     * @param  array<string, array{permission: string, roles: string, nombre: int, dernier: string}>  $refus
     */
    private function restituer(array $refus, int $jours, int $illisibles): void
    {
        usort($refus, fn (array $a, array $b): int => $b['nombre'] <=> $a['nombre']);

        $total = array_sum(array_column($refus, 'nombre'));
        $limite = max(1, (int) $this->option('limit'));

        $this->warn($total.' refus observé(s) sur '.$jours.' jour(s), '.count($refus).' combinaison(s).');
        $this->newLine();

        $this->table(
            ['Permission', 'Rôles du compte', 'Refus', 'Dernier'],
            array_map(
                fn (array $l): array => [
                    $l['permission'],
                    $l['roles'],
                    (string) $l['nombre'],
                    $l['dernier'] === '' ? '—' : CarbonImmutable::parse($l['dernier'])->format('d/m H:i'),
                ],
                array_slice($refus, 0, $limite)
            )
        );

        if (count($refus) > $limite) {
            $this->line((count($refus) - $limite).' combinaison(s) de plus — utiliser --limit.');
        }

        if ($illisibles > 0) {
            $this->line($illisibles.' ligne(s) de journal illisible(s), ignorée(s).');
        }

        $this->newLine();
        $this->line('<comment>Chaque ligne est soit une habilitation à corriger, soit un écran');
        $this->line('dont l\'usage avait été mal estimé. Tant que ce tableau n\'est pas vide,</comment>');
        $this->line('<comment>passer RBAC_ENFORCE à true fermera des accès encore utilisés.</comment>');
    }
}
