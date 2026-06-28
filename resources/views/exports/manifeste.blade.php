<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: bold; color: #111827; }
        .sub { color: #6b7280; font-size: 11px; margin-top: 2px; }
        .grid { width: 100%; margin: 12px 0; }
        .grid td { padding: 3px 6px; font-size: 11px; vertical-align: top; }
        .label { color: #6b7280; }
        .value { font-weight: bold; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.list th { background: #f3f4f6; color: #374151; text-align: left; padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
        table.list td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #f0f0f0; }
        .badge { padding: 1px 6px; border-radius: 8px; font-size: 9px; }
        .b-pay { background: #dcfce7; color: #166534; }
        .b-other { background: #f3f4f6; color: #4b5563; }
        .totals { margin-top: 12px; font-size: 11px; }
        .footer { margin-top: 20px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @php
        $voyage = $instance->voyage;
        $total = (int) $instance->nb_place;
        $occupied = $passengers->count();
    @endphp

    <div class="header">
        <div class="title">Manifeste d'embarquement</div>
        <div class="sub">{{ $voyage?->compagnie?->name }}</div>
    </div>

    <table class="grid">
        <tr>
            <td class="label">Trajet</td>
            <td class="value">{{ $instance->villeDepart()?->name ?? '—' }} → {{ $instance->villeArrive()?->name ?? '—' }}</td>
            <td class="label">Date</td>
            <td class="value">{{ \Carbon\Carbon::parse($instance->date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Heure de départ</td>
            <td class="value">{{ \Carbon\Carbon::parse($instance->heure)->format('H\hi') }}</td>
            <td class="label">Véhicule</td>
            <td class="value">{{ $instance->care?->immatrculation ?? '— Non affecté —' }}</td>
        </tr>
        <tr>
            <td class="label">Chauffeur</td>
            <td class="value">{{ $instance->chauffer ? $instance->chauffer->first_name.' '.$instance->chauffer->last_name : '— Non affecté —' }}</td>
            <td class="label">Occupation</td>
            <td class="value">{{ $occupied }} / {{ $total }} places</td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th style="width:40px;">Siège</th>
                <th>Passager</th>
                <th>Téléphone</th>
                <th>N° Ticket</th>
                <th>Statut</th>
                <th style="width:60px;">Présent</th>
            </tr>
        </thead>
        <tbody>
            @forelse($passengers as $t)
                @php
                    $name = $t->is_my_ticket ? ($t->user?->name) : ($t->autre_personne?->name ?? $t->user?->name);
                    $phone = $t->is_my_ticket ? $t->user?->numero : $t->autre_personne?->numero;
                @endphp
                <tr>
                    <td><strong>{{ $t->numero_chaise }}</strong></td>
                    <td>{{ $name ?? '—' }}</td>
                    <td>{{ $phone ?? '—' }}</td>
                    <td>{{ $t->numero_ticket }}</td>
                    <td>
                        <span class="badge {{ $t->statut === \App\Enums\StatutTicket::Payer ? 'b-pay' : 'b-other' }}">{{ $t->statut?->value }}</span>
                    </td>
                    <td>☐</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:14px;">Aucun passager.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <strong>Total passagers :</strong> {{ $occupied }} &nbsp;·&nbsp;
        <strong>Places restantes :</strong> {{ max(0, $total - $occupied) }}
    </div>

    <div class="footer">
        Édité le {{ now()->format('d/m/Y à H:i') }} — LIPTRA
    </div>
</body>
</html>
