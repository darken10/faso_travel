<?php

namespace App\Services\Ticket;

use App\Enums\TypeTicket;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Génération de PDF tickets entièrement en mémoire.
 * Aucun fichier n'est écrit sur le disque.
 */
final class PdfService
{
    public function __construct(private readonly QrCodeService $qrCode) {}

    /**
     * Retourne le contenu binaire brut du PDF.
     * Utiliser pour les pièces jointes email ou tout autre usage custom.
     */
    public function output(Ticket $ticket): string
    {
        return $this->build($ticket)->output();
    }

    /**
     * Retourne une réponse HTTP de téléchargement direct (Content-Disposition: attachment).
     */
    public function download(Ticket $ticket, string $filename = 'ticket.pdf'): \Illuminate\Http\Response
    {
        return $this->build($ticket)->download($filename);
    }

    /**
     * Retourne une réponse HTTP d'affichage inline dans le navigateur (Content-Disposition: inline).
     */
    public function stream(Ticket $ticket, string $filename = 'ticket.pdf'): \Illuminate\Http\Response
    {
        return $this->build($ticket)->stream($filename);
    }

    /**
     * Format billet : 240 mm × 116 mm en points (1 mm = 2.834645 pt).
     * Hauteur calée sur le contenu réel, marge comprise pour les libellés les
     * plus longs. Le reliquat se fond dans le gris du pied de page appliqué
     * sur <body>. Couvert par TicketPdfTest.
     */
    private const PAPER = [0.0, 0.0, 680.31, 330.00];

    private function build(Ticket $ticket): DomPdfDocument
    {
        return Pdf::loadView('ticket.ticket.pdf.ticket', $this->viewData($ticket))
            // Le format doit être passé ici : setPaper() écrase toujours la règle
            // CSS @page, et un A4 paysage laisserait un tiers de page vide.
            ->setPaper(self::PAPER)
            ->setOptions([
                // DomPDF convertit les px CSS en points avec ce dpi (pt = px × 72 / dpi).
                // À 150, la maquette de 907 px ne couvrait que 435 pt des 680 pt de la page.
                'dpi'                     => 96,
                'defaultFont'             => 'DejaVu Sans',
                'isRemoteEnabled'         => false,
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
            ]);
    }

    /**
     * Prépare toutes les valeurs d'affichage : le template reste purement présentationnel.
     *
     * @return array<string, mixed>
     */
    private function viewData(Ticket $ticket): array
    {
        $instance = $ticket->voyageInstance;
        $voyage   = $instance->voyage;
        $duree    = $voyage->temps;

        $departure = $instance->villeDepart()->name;
        $arrival   = $instance->villeArrive()->name;

        return [
            'ticket'      => $ticket,
            'qrCodePath'  => $this->qrCode->dataUri($ticket->code_qr),
            'logo'        => $this->companyLogo($ticket),
            'company'     => mb_strtoupper($ticket->compagnie()->name, 'UTF-8'),
            'passenger'   => mb_strtoupper($this->passengerName($ticket), 'UTF-8'),
            'classe'      => $this->classeLabel($ticket->classe()?->name),
            'vehicle'     => $instance->care?->immatrculation ?? '—',
            'isRoundTrip' => $ticket->type === TypeTicket::AllerRetour,

            'depCity'     => $departure,
            'arrCity'     => $arrival,
            'depStation'  => $this->stationLabel($instance->gareDepart()?->name),
            'arrStation'  => $this->stationLabel($instance->gareArrive()?->name),

            'dateLabel'   => $this->frenchDate($instance->date),
            'depTime'     => $instance->heure->format('H\hi'),
            'boardTime'   => $ticket->heureRdv()->format('H\hi'),
            // Sans durée renseignée, getHeureArrive() retomberait sur l'heure courante.
            'arrTime'     => $duree ? $instance->getHeureArrive()->format('H\hi') : null,
            'duration'    => $duree ? $this->humanDuration($duree) : null,

            'price'       => number_format($ticket->prix(), 0, ',', ' '),
            'reduction'   => $ticket->reduction > 0 ? number_format((float) $ticket->reduction, 0, ',', ' ') : null,
            'emittedAt'   => now()->format('d/m/Y à H\hi'),
        ];
    }

    /**
     * Les noms de gares administratifs peuvent être très longs : on les borne
     * pour que le billet garde une hauteur stable et tienne sur une page.
     */
    private function stationLabel(?string $station): ?string
    {
        return $station ? Str::limit($station, 46) : null;
    }

    private function passengerName(Ticket $ticket): string
    {
        if ($ticket->is_my_ticket) {
            return $ticket->user?->name ?? '';
        }

        if ($ticket->autre_personne_id !== null) {
            return $ticket->autre_personne?->name ?? '';
        }

        if ($ticket->transferer_a_user_id !== null) {
            return User::find($ticket->transferer_a_user_id)?->name ?? '';
        }

        return $ticket->user?->name ?? '';
    }

    /**
     * Les classes sont souvent nommées « Classe Économique » en base : on retire
     * le préfixe pour ne pas afficher « Classe Classe Économique ».
     */
    private function classeLabel(?string $classe): string
    {
        if (!$classe) {
            return '—';
        }

        return trim(preg_replace('/^classe\s+/i', '', $classe)) ?: $classe;
    }

    private function frenchDate(Carbon $date): string
    {
        $days   = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return $days[$date->dayOfWeek] . ' ' . $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
    }

    private function humanDuration(Carbon $temps): string
    {
        return $temps->minute > 0
            ? $temps->hour . 'h' . str_pad((string) $temps->minute, 2, '0', STR_PAD_LEFT)
            : $temps->hour . 'h';
    }

    /**
     * Le logo n'est embarqué que s'il est lisible localement : DomPDF tourne avec
     * isRemoteEnabled = false et échouerait sur une URL distante.
     */
    private function companyLogo(Ticket $ticket): ?string
    {
        $uri = $ticket->compagnie()->logo_uri;

        if (!$uri) {
            return null;
        }

        foreach ([storage_path('app/public/' . ltrim($uri, '/')), public_path(ltrim($uri, '/'))] as $path) {
            if (is_file($path) && ($binary = @file_get_contents($path)) !== false) {
                $mime = @mime_content_type($path) ?: 'image/png';

                return 'data:' . $mime . ';base64,' . base64_encode($binary);
            }
        }

        return null;
    }
}
