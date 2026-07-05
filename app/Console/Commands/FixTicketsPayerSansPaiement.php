<?php

namespace App\Console\Commands;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use Illuminate\Console\Command;

class FixTicketsPayerSansPaiement extends Command
{
    protected $signature = 'tickets:fix-payer-sans-paiement {--apply : Applique la correction (sinon simple aperçu)}';

    protected $description = "Repasse en 'En attente' les tickets marqués 'Payer' alors qu'aucun paiement n'est enregistré (bug debutAchat).";

    public function handle(): int
    {
        $query = Ticket::withoutGlobalScopes()
            ->where('statut', StatutTicket::Payer)
            ->whereDoesntHave('payements');

        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucun ticket à corriger. ✅');
            return self::SUCCESS;
        }

        $this->warn("{$count} ticket(s) 'Payer' sans aucun paiement détecté(s).");
        $this->table(
            ['ID', 'N° ticket', 'voyage_instance_id', 'user_id', 'créé le'],
            $query->clone()->take(20)->get()
                ->map(fn ($t) => [$t->id, $t->numero_ticket, $t->voyage_instance_id, $t->user_id, (string) $t->created_at])
                ->toArray()
        );

        if (!$this->option('apply')) {
            $this->line('');
            $this->info("Aperçu uniquement. Relancez avec --apply pour corriger.");
            return self::SUCCESS;
        }

        $updated = $query->update(['statut' => StatutTicket::EnAttente->value]);

        $this->info("{$updated} ticket(s) repassé(s) en 'En attente'. ✅");

        return self::SUCCESS;
    }
}
