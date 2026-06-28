<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #111827; }
        .sub { color: #6b7280; font-size: 11px; margin-top: 3px; }
        .cards { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .cards td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; }
        .clabel { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .cval { font-size: 16px; font-weight: bold; margin-top: 4px; }
        .pos { color: #16a34a; } .neg { color: #dc2626; }
        h2 { font-size: 13px; color: #374151; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background: #f3f4f6; color: #374151; text-align: left; padding: 6px 8px; font-size: 11px; }
        table.list td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #f0f0f0; }
        .right { text-align: right; }
        .footer { margin-top: 22px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Rapport d'activité</div>
        <div class="sub">
            {{ $compagnie?->name }} — du {{ $data['start']->format('d/m/Y') }} au {{ $data['end']->format('d/m/Y') }}
        </div>
    </div>

    {{-- KPIs --}}
    <table class="cards">
        <tr>
            <td><div class="clabel">Recettes totales</div><div class="cval pos">{{ number_format($data['totalRecettes'], 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Dépenses</div><div class="cval neg">{{ number_format($data['totalDepenses'], 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Bénéfice net</div><div class="cval {{ $data['benefice'] >= 0 ? 'pos' : 'neg' }}">{{ $data['benefice'] >= 0 ? '+' : '' }}{{ number_format($data['benefice'], 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Tickets vendus</div><div class="cval">{{ number_format($data['ticketsCount'], 0, ',', ' ') }}</div></td>
        </tr>
    </table>
    <table style="width:100%; font-size:11px; margin-bottom:6px;">
        <tr>
            <td>Billetterie : <strong>{{ number_format($data['revenueBilletterie'], 0, ',', ' ') }} F</strong></td>
            <td>Recettes manuelles : <strong>{{ number_format($data['recettesManuelles'], 0, ',', ' ') }} F</strong></td>
        </tr>
    </table>

    {{-- Top trajets --}}
    <h2>Top trajets</h2>
    <table class="list">
        <thead><tr><th>Trajet</th><th class="right">Tickets</th><th class="right">Recette</th></tr></thead>
        <tbody>
            @forelse(array_slice($data['topTrajets'], 0, 10) as $r)
                <tr>
                    <td>{{ $r['trajet'] }}</td>
                    <td class="right">{{ $r['tickets'] }}</td>
                    <td class="right">{{ number_format($r['recette'], 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center; color:#9ca3af; padding:10px;">Aucune vente.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Ventes par agent --}}
    <h2>Ventes par agent</h2>
    <table class="list">
        <thead><tr><th>Agent</th><th class="right">Tickets</th><th class="right">Montant encaissé</th></tr></thead>
        <tbody>
            @forelse($data['parAgent'] as $r)
                <tr>
                    <td>{{ $r['agent'] }}</td>
                    <td class="right">{{ $r['tickets'] }}</td>
                    <td class="right">{{ number_format($r['montant'], 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center; color:#9ca3af; padding:10px;">—</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Dépenses par catégorie --}}
    <h2>Dépenses par catégorie</h2>
    <table class="list">
        <thead><tr><th>Catégorie</th><th class="right">Montant</th></tr></thead>
        <tbody>
            @forelse($data['depensesParCategorie'] as $r)
                <tr>
                    <td>{{ $r['categorie'] }}</td>
                    <td class="right">{{ number_format($r['montant'], 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr><td colspan="2" style="text-align:center; color:#9ca3af; padding:10px;">Aucune dépense.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Édité le {{ now()->format('d/m/Y à H:i') }} — LIPTRA</div>
</body>
</html>
