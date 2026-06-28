<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecettesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Date', 'Libellé', 'Source', 'Montant (XOF)', 'Référence'];
    }

    /** @param \App\Models\Finance\Recette $r */
    public function map($r): array
    {
        return [
            $r->date_recette?->format('d/m/Y'),
            $r->libelle,
            $r->source ?? '',
            (int) $r->montant,
            $r->reference ?? '',
        ];
    }
}
