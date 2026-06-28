<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'N° Ticket', 'Passager', 'Téléphone', 'Trajet',
            'Date voyage', 'Heure', 'Siège', 'Type', 'Montant (XOF)', 'Statut', 'Acheté le',
        ];
    }

    /** @param \App\Models\Ticket\Ticket $ticket */
    public function map($ticket): array
    {
        $vi        = $ticket->voyageInstance;
        $passenger = $ticket->is_my_ticket
            ? ($ticket->user?->name ?? '')
            : ($ticket->autre_personne?->name ?? $ticket->user?->name ?? '');
        $phone = $ticket->is_my_ticket
            ? ($ticket->user?->numero ?? '')
            : ($ticket->autre_personne?->numero ?? '');

        return [
            $ticket->numero_ticket,
            $passenger,
            (string) $phone,
            ($vi?->voyage?->trajet?->depart?->name ?? '—') . ' → ' . ($vi?->voyage?->trajet?->arriver?->name ?? '—'),
            $vi?->date ? Carbon::parse($vi->date)->format('d/m/Y') : '',
            $vi?->heure ? Carbon::parse($vi->heure)->format('H:i') : '',
            $ticket->numero_chaise,
            $ticket->type?->value ?? '',
            $ticket->payements->sum('montant'),
            $ticket->statut?->value ?? '',
            $ticket->created_at?->format('d/m/Y H:i'),
        ];
    }
}
