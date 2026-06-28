<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepensesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Date', 'Libellé', 'Catégorie', 'Montant (XOF)', 'Référence'];
    }

    /** @param \App\Models\Finance\Depense $d */
    public function map($d): array
    {
        return [
            $d->date_depense?->format('d/m/Y'),
            $d->libelle,
            $d->categorie?->nom ?? 'Sans catégorie',
            (int) $d->montant,
            $d->reference ?? '',
        ];
    }
}
