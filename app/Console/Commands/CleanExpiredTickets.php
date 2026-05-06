<?php

namespace App\Console\Commands;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanExpiredTickets extends Command
{
    protected $signature = 'tickets:clean-expired
                            {--hours=24 : Heures après lesquelles un ticket non payé est expiré}
                            {--dry-run : Simuler sans modifier la base}';

    protected $description = 'Annule les tickets créés mais non payés après un délai';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $cutoff = Carbon::now()->subHours($hours);

        $this->info("Recherche des tickets non payés créés avant {$cutoff->format('d/m/Y H:i')}...");

        $tickets = Ticket::whereNotIn('statut', [
                StatutTicket::Payer,
                StatutTicket::Valider,
                StatutTicket::Bloquer,
                StatutTicket::Annuler,
                StatutTicket::Pause,
            ])
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Aucun ticket expiré trouvé.');
            return self::SUCCESS;
        }

        $this->warn("{$tickets->count()} ticket(s) seront annulés.");

        if ($dryRun) {
            $this->table(
                ['ID', 'Numéro', 'Statut', 'Créé le'],
                $tickets->map(fn ($t) => [$t->id, $t->numero_ticket, $t->statut->value, $t->created_at->format('d/m/Y H:i')])
            );
            $this->info('Mode dry-run: aucune modification effectuée.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($tickets as $ticket) {
            $ticket->update(['statut' => StatutTicket::Annuler]);
            $count++;
        }

        $this->info("{$count} ticket(s) annulé(s) avec succès.");

        return self::SUCCESS;
    }
}
