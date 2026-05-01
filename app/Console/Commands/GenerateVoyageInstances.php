<?php

namespace App\Console\Commands;

use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Enums\StatutVoyageInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateVoyageInstances extends Command
{
    protected $signature = 'voyages:generate-instances
                            {--days=7 : Nombre de jours à générer}
                            {--date= : Date de départ (Y-m-d), défaut = aujourd\'hui}
                            {--voyage= : ID d\'un voyage spécifique}
                            {--force : Régénérer même si déjà existant}';

    protected $description = 'Génère les instances de voyages pour les prochains jours';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $startDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();
        $voyageId = $this->option('voyage');
        $force = $this->option('force');

        $this->info("Génération des instances de voyages du {$startDate->format('d/m/Y')} sur {$days} jour(s)...");

        $query = Voyage::query()->where('is_active', true);
        if ($voyageId) {
            $query->where('id', $voyageId);
        }

        $voyages = $query->get();

        if ($voyages->isEmpty()) {
            $this->warn('Aucun voyage actif trouvé.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $this->withProgressBar($voyages, function (Voyage $voyage) use ($startDate, $days, $force, &$created, &$skipped) {
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);

                $dayName = strtolower($date->locale('fr')->dayName);

                if (!$this->voyageRunsOn($voyage, $date)) {
                    continue;
                }

                $exists = VoyageInstance::where('voyage_id', $voyage->id)
                    ->whereDate('date', $date)
                    ->exists();

                if ($exists && !$force) {
                    $skipped++;
                    continue;
                }

                if ($exists && $force) {
                    VoyageInstance::where('voyage_id', $voyage->id)
                        ->whereDate('date', $date)
                        ->delete();
                }

                DB::transaction(function () use ($voyage, $date, &$created) {
                    VoyageInstance::create([
                        'voyage_id' => $voyage->id,
                        'date' => $date,
                        'heure' => $voyage->heure,
                        'nb_places_disponibles' => $voyage->nb_place ?? 0,
                        'statut' => StatutVoyageInstance::Planifie,
                    ]);
                    $created++;
                });
            }
        });

        $this->newLine(2);
        $this->info("Terminé : {$created} instances créées, {$skipped} ignorées (déjà existantes).");

        return self::SUCCESS;
    }

    private function voyageRunsOn(Voyage $voyage, Carbon $date): bool
    {
        if (!$voyage->days || $voyage->days->isEmpty()) {
            return true;
        }

        $dayNumber = $date->dayOfWeek;

        return $voyage->days->contains(fn ($day) => $day->numero === $dayNumber);
    }
}
