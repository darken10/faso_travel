<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #111827; }
        .sub { color: #6b7280; font-size: 11px; margin-top: 3px; }
        .cards { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .cards td { width: 33.33%; padding: 10px; border: 1px solid #e5e7eb; }
        .clabel { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .cval { font-size: 16px; font-weight: bold; margin-top: 4px; }
        .purple { color: #7c3aed; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.list th { background: #f3f4f6; color: #374151; text-align: left; padding: 6px 8px; font-size: 11px; }
        table.list td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #f0f0f0; }
        .right { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
        .badge-on { color: #16a34a; } .badge-off { color: #9ca3af; }
        .footer { margin-top: 22px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Rapport des codes promo</div>
        <div class="sub">{{ $compagnie?->name }} — édité le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>

    <table class="cards">
        <tr>
            <td><div class="clabel">Codes promo</div><div class="cval">{{ $rows->count() }}</div></td>
            <td><div class="clabel">Utilisations totales</div><div class="cval">{{ number_format($totalUtilisations, 0, ',', ' ') }}</div></td>
            <td><div class="clabel">Réductions accordées</div><div class="cval purple">{{ number_format($totalReduction, 0, ',', ' ') }} F</div></td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th>Code</th>
                <th>Réduction</th>
                <th>Validité</th>
                <th>Statut</th>
                <th class="right">Utilisations</th>
                <th class="right">Réduction cumulée</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="mono">{{ $r['code'] }}</td>
                    <td>@if($r['type'] === 'pourcentage') −{{ $r['valeur'] }}% @else −{{ number_format($r['valeur'], 0, ',', ' ') }} F @endif</td>
                    <td>{{ $r['periode'] }}</td>
                    <td class="{{ $r['active'] ? 'badge-on' : 'badge-off' }}">{{ $r['active'] ? 'Actif' : 'Inactif' }}</td>
                    <td class="right">{{ $r['utilisations'] }}{{ $r['usage_limit'] ? ' / ' . $r['usage_limit'] : '' }}</td>
                    <td class="right purple">−{{ number_format($r['reduction'], 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:12px;">Aucun code promo.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="right" style="font-weight:bold; border-top:2px solid #e5e7eb;">Total</td>
                    <td class="right" style="font-weight:bold; border-top:2px solid #e5e7eb;">{{ $totalUtilisations }}</td>
                    <td class="right purple" style="font-weight:bold; border-top:2px solid #e5e7eb;">−{{ number_format($totalReduction, 0, ',', ' ') }} F</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">LIPTRA — Rapport des codes promo</div>
</body>
</html>
