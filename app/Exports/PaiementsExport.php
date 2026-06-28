<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaiementsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Date', 'N° Ticket', 'Client', 'Trajet', 'Montant net (XOF)', 'Code promo', 'Réduction (XOF)', 'Moyen', 'Agent'];
    }

    /** @param \App\Models\Ticket\Payement $p */
    public function map($p): array
    {
        $t  = $p->ticket;
        $vi = $t?->voyageInstance;
        $client = $t?->is_my_ticket
            ? ($t?->user?->name ?? '')
            : ($t?->autre_personne?->name ?? $t?->user?->name ?? '');

        return [
            $p->created_at?->format('d/m/Y H:i'),
            $t?->numero_ticket ?? '—',
            $client,
            ($vi?->voyage?->trajet?->depart?->name ?? '—') . ' → ' . ($vi?->voyage?->trajet?->arriver?->name ?? '—'),
            (int) $p->montant,
            $t?->promoCode?->code ?? '',
            (int) ($t?->reduction ?? 0),
            $p->moyen_payment?->value ?? (string) $p->moyen_payment,
            $t?->user?->name ?? '—',
        ];
    }
}
