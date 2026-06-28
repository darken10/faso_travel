<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class InstancesExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Date', 'Heure', 'Trajet', 'Véhicule', 'Chauffeur', 'Occupées', 'Places', 'Statut'];
    }

    public function title(): string
    {
        return 'Planning';
    }

    /** @param \App\Models\Voyage\VoyageInstance $i */
    public function map($i): array
    {
        return [
            $i->date ? Carbon::parse($i->date)->format('d/m/Y') : '',
            $i->heure ? Carbon::parse($i->heure)->format('H:i') : '',
            ($i->voyage?->trajet?->depart?->name ?? '—') . ' → ' . ($i->voyage?->trajet?->arriver?->name ?? '—'),
            $i->care?->immatrculation ?? '',
            $i->chauffer ? trim($i->chauffer->first_name . ' ' . $i->chauffer->last_name) : '',
            (int) ($i->occupied_count ?? 0),
            (int) $i->nb_place,
            $i->statut->value ?? '',
        ];
    }
}
