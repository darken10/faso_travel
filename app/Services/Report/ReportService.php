<?php

namespace App\Services\Report;

use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Agrège toutes les statistiques d'une compagnie sur une période.
     *
     * @return array<string, mixed>
     */
    public function data(int $compagnieId, Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end   = $end->copy()->endOfDay();

        // ── Tickets payés/validés de la période (par date de vente) ────────────
        $tickets = Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
            ->whereBetween('created_at', [$start, $end])
            ->with([
                'user:id,first_name,last_name,name',
                'payements:id,ticket_id,montant',
                'voyageInstance.voyage.trajet.depart:id,name',
                'voyageInstance.voyage.trajet.arriver:id,name',
            ])
            ->get();

        $revenueBilletterie = $tickets->sum(fn ($t) => $t->payements->sum('montant'));
        $ticketsCount       = $tickets->count();

        // Réductions accordées via codes promo sur la période.
        // NB : revenueBilletterie est déjà NET (Payement.montant est le montant
        // réellement encaissé, après remise) ; on expose la remise pour la transparence
        // et on en déduit la recette brute (avant remise).
        $reductionsPromo = (int) $tickets->sum('reduction');
        $ticketsAvecPromo = $tickets->whereNotNull('promo_code_id')->count();

        // ── Top trajets (nb tickets + recette + remplissage) ───────────────────
        $topTrajets = $tickets
            ->groupBy(fn ($t) => ($t->voyageInstance?->voyage?->trajet?->depart?->name ?? '—')
                . ' – ' . ($t->voyageInstance?->voyage?->trajet?->arriver?->name ?? '—'))
            ->map(fn ($grp, $label) => [
                'trajet'  => $label,
                'tickets' => $grp->count(),
                'recette' => $grp->sum(fn ($t) => $t->payements->sum('montant')),
            ])
            ->sortByDesc('recette')
            ->values()
            ->all();

        // ── Ventes par agent ───────────────────────────────────────────────────
        $parAgent = $tickets
            ->groupBy('user_id')
            ->map(fn ($grp) => [
                'agent'   => optional($grp->first()->user)->name
                    ?? trim((optional($grp->first()->user)->first_name ?? '') . ' ' . (optional($grp->first()->user)->last_name ?? ''))
                    ?: '—',
                'tickets' => $grp->count(),
                'montant' => $grp->sum(fn ($t) => $t->payements->sum('montant')),
            ])
            ->sortByDesc('montant')
            ->values()
            ->all();

        // ── Finances ───────────────────────────────────────────────────────────
        $recettesManuelles = (int) Recette::where('compagnie_id', $compagnieId)
            ->whereBetween('date_recette', [$start->toDateString(), $end->toDateString()])
            ->sum('montant');

        $depenses = Depense::where('compagnie_id', $compagnieId)
            ->whereBetween('date_depense', [$start->toDateString(), $end->toDateString()])
            ->with('categorie:id,nom')
            ->get();

        $totalDepenses = (int) $depenses->sum('montant');

        $depensesParCategorie = $depenses
            ->groupBy(fn ($d) => $d->categorie?->nom ?? 'Sans catégorie')
            ->map(fn ($grp, $nom) => ['categorie' => $nom, 'montant' => (int) $grp->sum('montant')])
            ->sortByDesc('montant')
            ->values()
            ->all();

        $totalRecettes = (int) $revenueBilletterie + $recettesManuelles;
        $benefice      = $totalRecettes - $totalDepenses;

        $embarques  = $this->ticketsEmbarques($compagnieId, $start, $end);
        $pausesAuto = $this->ticketsEnPauseAutomatique($compagnieId, $start, $end);

        return [
            'start'                => $start,
            'end'                  => $end,
            'revenueBilletterie'   => (int) $revenueBilletterie,
            'reductionsPromo'      => $reductionsPromo,
            'revenueBrut'          => (int) $revenueBilletterie + $reductionsPromo,
            'ticketsAvecPromo'     => $ticketsAvecPromo,
            'recettesManuelles'    => $recettesManuelles,
            'totalRecettes'        => $totalRecettes,
            'totalDepenses'        => $totalDepenses,
            'benefice'             => $benefice,
            'ticketsCount'         => $ticketsCount,
            'topTrajets'           => $topTrajets,
            'parAgent'             => $parAgent,
            'depensesParCategorie' => $depensesParCategorie,

            // Embarquements et absences : comptés sur la date de l'événement, et
            // non sur la date de vente — un billet vendu lundi peut n'embarquer
            // que jeudi. Ces sections ne recoupent donc pas `ticketsCount`.
            'embarques'            => $embarques,
            'embarquesCount'       => count($embarques),
            'pausesAuto'           => $pausesAuto,
            'pausesAutoCount'      => count($pausesAuto),
            'pausesAutoMontant'    => (int) array_sum(array_column($pausesAuto, 'montant')),
        ];
    }

    /**
     * Logo de la compagnie réduit et encodé en data URI, prêt à être posé dans
     * le PDF.
     *
     * Le fichier d'origine est une photo pleine résolution : l'insérer tel quel
     * alourdirait chaque rapport de plusieurs dizaines de kilo-octets pour une
     * vignette de deux centimètres. On le redimensionne donc à la taille
     * réellement affichée, et le résultat est mémorisé — un même logo sert à
     * tous les rapports d'une campagne d'envoi.
     */
    public function logoDataUri(mixed $compagnie, int $largeurMax = 160): ?string
    {
        $chemin = $compagnie?->logo_uri;

        if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
            return null;
        }

        return Cache::remember(
            'report_logo:'.md5($chemin.'|'.$largeurMax),
            now()->addDay(),
            function () use ($chemin, $largeurMax) {
                try {
                    $binaire = Storage::disk('public')->get($chemin);

                    if (! function_exists('imagecreatefromstring')) {
                        return $this->dataUri($binaire, Storage::disk('public')->mimeType($chemin));
                    }

                    $source = @imagecreatefromstring($binaire);

                    if ($source === false) {
                        return null;
                    }

                    $reduit = imagescale($source, min($largeurMax, imagesx($source)));
                    imagedestroy($source);

                    if ($reduit === false) {
                        return $this->dataUri($binaire, 'image/png');
                    }

                    // Un logo photographique se compresse bien mieux en JPEG ;
                    // le PNG n'est conservé que pour les logos à fond transparent.
                    $estPhoto = in_array(
                        strtolower(pathinfo($chemin, PATHINFO_EXTENSION)),
                        ['jpg', 'jpeg'],
                        true,
                    );

                    ob_start();
                    $estPhoto ? imagejpeg($reduit, null, 82) : imagepng($reduit, null, 9);
                    $optimise = (string) ob_get_clean();
                    imagedestroy($reduit);

                    return $this->dataUri($optimise, $estPhoto ? 'image/jpeg' : 'image/png');
                } catch (\Throwable) {
                    // Un logo illisible ne doit jamais empêcher l'envoi du rapport.
                    return null;
                }
            },
        );
    }

    private function dataUri(string $binaire, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($binaire);
    }

    /**
     * Billets effectivement embarqués sur la période, d'après la date de
     * validation par l'agent.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ticketsEmbarques(int $compagnieId, Carbon $start, Carbon $end): array
    {
        return $this->ticketsQuery($compagnieId)
            ->where('statut', StatutTicket::Valider)
            ->whereBetween('valider_at', [$start, $end])
            ->with(['validePar:id,first_name,last_name,name'])
            ->orderBy('valider_at')
            ->get()
            ->map(fn (Ticket $ticket) => $this->ligneTicket($ticket) + [
                // `valider_at` n'est pas casté sur le modèle : plusieurs ressources API
                // exposent la valeur brute et un cast global changerait leur format.
                'embarque_le' => $ticket->valider_at ? Carbon::parse($ticket->valider_at)->format('d/m/Y H:i') : null,
                'valide_par'  => $this->nomUtilisateur($ticket->validePar),
            ])
            ->all();
    }

    /**
     * Billets payés basculés en pause par la tâche planifiée, faute d'avoir été
     * scannés — autrement dit les voyageurs absents au départ.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ticketsEnPauseAutomatique(int $compagnieId, Carbon $start, Carbon $end): array
    {
        return $this->ticketsQuery($compagnieId)
            ->where('statut', StatutTicket::Pause)
            ->where('paused_auto', true)
            ->whereBetween('paused_at', [$start, $end])
            ->orderBy('paused_at')
            ->get()
            ->map(fn (Ticket $ticket) => $this->ligneTicket($ticket) + [
                'pause_le' => $ticket->paused_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /** Requête de base des billets d'une compagnie, relations d'affichage incluses. */
    private function ticketsQuery(int $compagnieId): \Illuminate\Database\Eloquent\Builder
    {
        return Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->with([
                'user:id,first_name,last_name,name',
                'payements:id,ticket_id,montant',
                'voyageInstance:id,voyage_id,date,heure',
                'voyageInstance.voyage.trajet.depart:id,name',
                'voyageInstance.voyage.trajet.arriver:id,name',
            ]);
    }

    /**
     * Colonnes communes aux listes de billets des rapports.
     *
     * @return array<string, mixed>
     */
    private function ligneTicket(Ticket $ticket): array
    {
        $trajet = $ticket->voyageInstance?->voyage?->trajet;

        return [
            'numero'    => $ticket->numero_ticket,
            'passager'  => $this->nomUtilisateur($ticket->user),
            'trajet'    => ($trajet?->depart?->name ?? '—').' – '.($trajet?->arriver?->name ?? '—'),
            'depart_le' => $ticket->voyageInstance?->date
                ? Carbon::parse($ticket->voyageInstance->date)->format('d/m/Y')
                : null,
            'siege'     => $ticket->numero_chaise,
            'montant'   => (int) $ticket->payements->sum('montant'),
        ];
    }

    private function nomUtilisateur(mixed $user): string
    {
        if (! $user) {
            return '—';
        }

        return $user->name
            ?: (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: '—');
    }

    /**
     * Paiements de la période (pour l'export comptable).
     */
    public function paiementsQuery(int $compagnieId, Carbon $start, Carbon $end)
    {
        return Payement::query()
            ->whereHas('ticket.voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('statut', StatutPayement::Complete->value)
            ->with([
                'ticket.user', 'ticket.autre_personne', 'ticket.promoCode',
                'ticket.voyageInstance.voyage.trajet.depart',
                'ticket.voyageInstance.voyage.trajet.arriver',
            ])
            ->latest();
    }
}
