<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-existing
                            {--force : Re-vérifie aussi les comptes déjà vérifiés}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marque les comptes existants comme vérifiés depuis leur date de création (created_at).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        // Comptes avec email → email vérifié à la date de création
        $emailQuery = DB::table('users')->whereNotNull('email');
        if (!$force) {
            $emailQuery->whereNull('email_verified_at');
        }
        $emailCount = $emailQuery->update(['email_verified_at' => DB::raw('created_at')]);

        // Comptes restants (sans email vérifié) → téléphone vérifié à la création
        $phoneCount = DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => DB::raw('created_at')]);

        $remaining = DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNull('phone_verified_at')
            ->count();

        $this->info("Comptes vérifiés par email : {$emailCount}");
        $this->info("Comptes vérifiés par téléphone : {$phoneCount}");

        if ($remaining > 0) {
            $this->warn("Comptes encore non vérifiés (ni email ni numéro) : {$remaining}");
        } else {
            $this->info('Tous les comptes sont désormais vérifiés.');
        }

        return self::SUCCESS;
    }
}
