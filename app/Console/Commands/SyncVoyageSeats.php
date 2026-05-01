<?php

namespace App\Console\Commands;

use App\Enums\StatutTicket;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Console\Command;

class SyncVoyageSeats extends Command
{
    protected $signature = 'voyages:sync-seats
                            {--instance= : ID d\'une instance spécifique}';

    protected $description = 'Recalcule le nombre de places disponibles pour les instances de voyage';

    public function handle(): int
    {
        $this->info('Synchronisation des places disponibles...');

        $query = VoyageInstance::query()->with('voyage');

        if ($id = $this->option('instance')) {
            $query->where('id', $id);
        }

        $instances = $query->get();
        $updated = 0;

        $this->withProgressBar($instances, function (VoyageInstance $instance) use (&$updated) {
            $soldSeats = $instance->tickets()
                ->whereNotIn('statut', [StatutTicket::Annuler])
                ->count();

            $totalSeats = $instance->voyage?->nb_place ?? 0;
            $available = max(0, $totalSeats - $soldSeats);

            if ($instance->nb_places_disponibles !== $available) {
                $instance->update(['nb_places_disponibles' => $available]);
                $updated++;
            }
        });

        $this->newLine(2);
        $this->info("{$updated} instance(s) mises à jour.");

        return self::SUCCESS;
    }
}
