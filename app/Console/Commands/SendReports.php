<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\RapportMail;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReports extends Command
{
    protected $signature = 'reports:send {period=daily : daily|weekly|monthly|yearly}';

    protected $description = 'Envoie les rapports d\'activité par email aux gérants des compagnies';

    public function handle(ReportService $reports): int
    {
        $period = $this->argument('period');
        [$start, $end, $label] = $this->resolvePeriod($period);

        $this->info("Envoi des rapports {$period} ({$start->format('d/m/Y')} → {$end->format('d/m/Y')})…");

        $sent = 0;
        foreach (Compagnie::query()->get() as $compagnie) {
            
            $recipients = User::where('compagnie_id', $compagnie->id)
                ->whereIn('role', [UserRole::Admin->value, UserRole::CompagnieBosse->value])
                ->pluck('email')
                ->filter()
                ->values()
                ->all();
            
            $recipients = array_merge($recipients, ['zerbo@liptra.net']);

            if (empty($recipients)) {
                continue;
            }


            try {
                $data = $reports->data($compagnie->id, $start, $end);
                $pdf  = Pdf::loadView('exports.rapport', ['data' => $data, 'compagnie' => $compagnie])->output();

                Mail::to($recipients)->send(new RapportMail($compagnie, $data, $label, $pdf));
                $sent++;
            } catch (\Throwable $e) {
                Log::error("[reports:send] Compagnie {$compagnie->id} : " . $e->getMessage());
            }
        }

        $this->info("Terminé : {$sent} rapport(s) envoyé(s).");

        return self::SUCCESS;
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function resolvePeriod(string $period): array
    {
        $now = now();

        return match ($period) {
            'yearly' => [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
                'Annuel — ' . $now->copy()->subYear()->format('Y'),
            ],
            'monthly' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'Mensuel — ' . $now->copy()->subMonthNoOverflow()->translatedFormat('F Y'),
            ],
            'weekly' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
                'Hebdomadaire — semaine du ' . $now->copy()->subWeek()->startOfWeek()->format('d/m/Y'),
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Journalier — ' . $now->format('d/m/Y'),
            ],
        };
    }
}
