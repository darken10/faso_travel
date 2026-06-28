<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #111827; }
        .code { font-family: DejaVu Sans Mono, monospace; color: #7c3aed; }
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
        .footer { margin-top: 22px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Code promo <span class="code">{{ $promo->code }}</span></div>
        <div class="sub">
            {{ $compagnie?->name }} —
            @if($promo->type === 'pourcentage') −{{ $promo->valeur }}% @else −{{ number_format($promo->valeur, 0, ',', ' ') }} F @endif
            · {{ $promo->date_debut?->format('d/m/Y') ?? '—' }} → {{ $promo->date_fin?->format('d/m/Y') ?? '∞' }}
            · édité le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>

    <table class="cards">
        <tr>
            <td><div class="clabel">Utilisations</div><div class="cval">{{ $totalUtilisations }}{{ $promo->usage_limit ? ' / ' . $promo->usage_limit : '' }}</div></td>
            <td><div class="clabel">Réduction totale</div><div class="cval purple">{{ number_format($totalReduction, 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Montant min.</div><div class="cval">{{ $promo->min_montant ? number_format($promo->min_montant, 0, ',', ' ') . ' F' : '—' }}</div></td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th>N° Ticket</th>
                <th>Bénéficiaire</th>
                <th>Trajet</th>
                <th>Date</th>
                <th class="right">Réduction</th>
                <th class="right">Payé</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $t)
                @php
                    $nom = $t->is_my_ticket
                        ? ($t->user?->name ?? '—')
                        : ($t->autre_personne?->name ?? $t->user?->name ?? '—');
                @endphp
                <tr>
                    <td>{{ $t->numero_ticket }}</td>
                    <td>{{ $nom }}</td>
                    <td>{{ $t->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $t->voyageInstance?->voyage?->trajet?->arriver?->name }}</td>
                    <td>{{ $t->created_at?->format('d/m/Y') }}</td>
                    <td class="right purple">−{{ number_format($t->reduction ?? 0, 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($t->payements->sum('montant'), 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:12px;">Aucun ticket n'a utilisé ce code.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LIPTRA — Bénéficiaires du code {{ $promo->code }}</div>
</body>
</html>
