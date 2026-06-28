<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RapportTrajetsExport implements FromArray, WithHeadings, WithTitle
{
    /** @param array<int, array{trajet:string, tickets:int, recette:int}> $rows */
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return array_map(fn ($r) => [
            $r['trajet'],
            $r['tickets'],
            $r['recette'],
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['Trajet', 'Tickets vendus', 'Recette (XOF)'];
    }

    public function title(): string
    {
        return 'Par trajet';
    }
}
