<?php

namespace App\Console\Commands;

use App\Services\Voyage\VoyageInstanceService;
use Illuminate\Console\Command;

class GenerateVoyageInstances extends Command
{
    protected $signature = 'voyages:generate-instances
                            {--days=7 : Nombre de jours à générer}
                            {--voyage= : ID d\'un voyage spécifique}
                            {--compagnie= : ID d\'une compagnie spécifique}
                            {--force : Régénérer même si déjà existant}';

    protected $description = 'Génère les instances de voyages (avec affectation automatique véhicule/chauffeur)';

    public function handle(VoyageInstanceService $service): int
    {
        $days        = (int) $this->option('days');
        $voyageId    = $this->option('voyage');
        $compagnieId = $this->option('compagnie') ? (int) $this->option('compagnie') : null;
        $force       = (bool) $this->option('force');

        $this->info("Génération des instances de voyages sur {$days} jour(s)...");

        $result = $service->generate($days, $voyageId, $compagnieId, $force);

        $this->info("Terminé : {$result['created']} instance(s) créée(s), {$result['skipped']} ignorée(s) (déjà existantes).");

        return self::SUCCESS;
    }
}
