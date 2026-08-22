<?php

namespace App\Console\Commands;

use App\Services\Ticket\RappelDepartService;
use Illuminate\Console\Command;

/**
 * Souhaite bon voyage aux passagers embarqués.
 *
 * Le délai après embarquement est réglé par chaque compagnie ; celles qui
 * désactivent le message sont ignorées.
 */
class SendBonVoyageMessages extends Command
{
    protected $signature = 'notifications:bon-voyage';

    protected $description = 'Envoie le message de bon voyage aux passagers embarqués';

    public function handle(RappelDepartService $service): int
    {
        $bilan = $service->envoyerBonVoyage();

        $this->info("Messages « bon voyage » envoyés : {$bilan['notifies']}.");

        return self::SUCCESS;
    }
}
