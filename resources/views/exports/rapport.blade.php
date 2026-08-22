@php
    /**
     * Rapport d'activité — document PDF.
     *
     * Typographie : Helvetica est une police « core PDF », présente dans tout
     * lecteur et JAMAIS embarquée dans le fichier. Elle fait passer le document
     * de ~860 Ko à quelques kilo-octets. En contrepartie elle est limitée au jeu
     * Windows-1252 : pas de flèche « → » ni de signe moins « − » ici, on utilise
     * le tiret demi-cadratin « – », qui en fait partie.
     */
    $f = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $logo = $logo ?? null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport d'activité</title>
    <style>
        @page { margin: 26px 32px 40px; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 13.5px;
            color: #1f2937;
            margin: 0;
        }

        /* ── En-tête ───────────────────────────────────────────────── */
        .head { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .head td { vertical-align: middle; padding: 0; }
        .logo { width: 54px; }
        .logo img { width: 46px; height: auto; }
        .brand { font-size: 22px; font-weight: bold; color: #111827; }
        .period { color: #6b7280; font-size: 12px; margin-top: 2px; }
        .doctype {
            text-align: right; font-size: 10px; color: #6b7280;
            text-transform: uppercase; letter-spacing: 1.4px;
        }
        .doctype strong { display: block; font-size: 15px; color: #1d4ed8; letter-spacing: 0; margin-top: 2px; }
        .rule { height: 2px; background: #1d4ed8; margin: 8px 0 14px; font-size: 0; line-height: 0; }

        /* ── Indicateurs ───────────────────────────────────────────── */
        .kpi { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin-bottom: 12px; }
        .kpi td { width: 25%; background: #f8fafc; border: 1px solid #e5e7eb; padding: 11px 12px; }
        .kpi .k { font-size: 10.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi .v { font-size: 19px; font-weight: bold; color: #111827; margin-top: 3px; }
        .pos { color: #15803d; } .neg { color: #b91c1c; } .warn { color: #b45309; }

        /* ── Ligne de détail chiffré ───────────────────────────────── */
        .inline { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11.5px; color: #4b5563; }
        .inline td { padding: 2px 0; }

        /* ── Sections ──────────────────────────────────────────────── */
        h2 {
            font-size: 14px; color: #111827; margin: 18px 0 7px;
            padding-bottom: 3px; border-bottom: 1px solid #d1d5db;
            text-transform: uppercase; letter-spacing: 0.6px;
        }
        h2 .count { color: #6b7280; font-weight: normal; letter-spacing: 0; text-transform: none; }
        .note { font-size: 11px; color: #6b7280; margin: 0 0 5px; }

        table.list { width: 100%; border-collapse: collapse; }
        table.list th {
            background: #f3f4f6; color: #374151; text-align: left;
            padding: 7px 8px; font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.4px; border-bottom: 1px solid #d1d5db;
        }
        table.list td { padding: 7px 8px; font-size: 12.5px; border-bottom: 1px solid #f1f5f9; }
        table.list tfoot td { border-top: 1px solid #d1d5db; border-bottom: none; font-weight: bold; }
        .right { text-align: right; }
        .empty { text-align: center; color: #9ca3af; padding: 12px; font-size: 12.5px; }
        .mono { font-family: Courier, monospace; font-size: 11.5px; }

        .footer {
            position: fixed; bottom: -22px; left: 0; right: 0;
            text-align: center; color: #9ca3af; font-size: 10px;
        }
    </style>
</head>
<body>

    {{-- ── En-tête ──────────────────────────────────────────────────── --}}
    <table class="head">
        <tr>
            @if($logo)
                <td class="logo"><img src="{{ $logo }}" alt=""></td>
            @endif
            <td>
                <div class="brand">{{ $compagnie?->name ?? 'LIPTRA' }}</div>
                <div class="period">
                    Du {{ $data['start']->format('d/m/Y') }} au {{ $data['end']->format('d/m/Y') }}
                </div>
            </td>
            <td class="doctype">
                Rapport d'activité
                <strong>{{ $periodLabel ?? '' }}</strong>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- ── Indicateurs clés ─────────────────────────────────────────── --}}
    <table class="kpi">
        <tr>
            <td>
                <div class="k">Recettes totales</div>
                <div class="v pos">{{ $f($data['totalRecettes']) }} F</div>
            </td>
            <td>
                <div class="k">Dépenses</div>
                <div class="v neg">{{ $f($data['totalDepenses']) }} F</div>
            </td>
            <td>
                <div class="k">Bénéfice net</div>
                <div class="v {{ $data['benefice'] >= 0 ? 'pos' : 'neg' }}">
                    {{ $data['benefice'] >= 0 ? '+' : '' }}{{ $f($data['benefice']) }} F
                </div>
            </td>
            <td>
                <div class="k">Tickets vendus</div>
                <div class="v">{{ $f($data['ticketsCount']) }}</div>
            </td>
        </tr>
    </table>

    <table class="inline">
        <tr>
            <td>Billetterie (net) : <strong>{{ $f($data['revenueBilletterie']) }} F</strong></td>
            <td>Recettes manuelles : <strong>{{ $f($data['recettesManuelles']) }} F</strong></td>
            <td class="right">
                Embarqués : <strong>{{ $f($data['embarquesCount']) }}</strong>
                &nbsp;&middot;&nbsp;
                Absents au départ :
                <strong class="{{ $data['pausesAutoCount'] > 0 ? 'warn' : '' }}">{{ $f($data['pausesAutoCount']) }}</strong>
            </td>
        </tr>
        @if(($data['reductionsPromo'] ?? 0) > 0)
            <tr>
                <td>
                    Réductions promo : <strong>-{{ $f($data['reductionsPromo']) }} F</strong>
                    ({{ $data['ticketsAvecPromo'] }} ticket{{ $data['ticketsAvecPromo'] > 1 ? 's' : '' }})
                </td>
                <td colspan="2">Recette brute (avant remise) : <strong>{{ $f($data['revenueBrut']) }} F</strong></td>
            </tr>
        @endif
    </table>

    {{-- ── Top trajets ──────────────────────────────────────────────── --}}
    <h2>Top trajets</h2>
    <table class="list">
        <thead><tr><th>Trajet</th><th class="right">Tickets</th><th class="right">Recette</th></tr></thead>
        <tbody>
            @forelse(array_slice($data['topTrajets'], 0, 10) as $r)
                <tr>
                    <td>{{ $r['trajet'] }}</td>
                    <td class="right">{{ $r['tickets'] }}</td>
                    <td class="right">{{ $f($r['recette']) }} F</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Aucune vente sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Ventes par agent ─────────────────────────────────────────── --}}
    <h2>Ventes par agent</h2>
    <table class="list">
        <thead><tr><th>Agent</th><th class="right">Tickets</th><th class="right">Montant encaissé</th></tr></thead>
        <tbody>
            @forelse($data['parAgent'] as $r)
                <tr>
                    <td>{{ $r['agent'] }}</td>
                    <td class="right">{{ $r['tickets'] }}</td>
                    <td class="right">{{ $f($r['montant']) }} F</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Aucune vente sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Embarquements ────────────────────────────────────────────── --}}
    <h2>Tickets embarqués <span class="count">({{ $data['embarquesCount'] }})</span></h2>
    <table class="list">
        <thead>
            <tr>
                <th>N&deg; ticket</th><th>Passager</th><th>Trajet</th>
                <th>Embarqué le</th><th>Validé par</th><th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['embarques'] as $r)
                <tr>
                    <td class="mono">{{ $r['numero'] }}</td>
                    <td>{{ $r['passager'] }}</td>
                    <td>{{ $r['trajet'] }}</td>
                    <td>{{ $r['embarque_le'] }}</td>
                    <td>{{ $r['valide_par'] }}</td>
                    <td class="right">{{ $f($r['montant']) }} F</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucun embarquement sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Absents au départ ────────────────────────────────────────── --}}
    <h2>Tickets mis en pause automatiquement <span class="count">({{ $data['pausesAutoCount'] }})</span></h2>
    <p class="note">
        Billets payés dont le voyage est parti sans qu'ils soient scannés. Ils restent
        utilisables : le voyageur peut les reporter sur un autre départ.
    </p>
    <table class="list">
        <thead>
            <tr>
                <th>N&deg; ticket</th><th>Passager</th><th>Trajet</th>
                <th>Départ prévu</th><th>Mis en pause le</th><th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['pausesAuto'] as $r)
                <tr>
                    <td class="mono">{{ $r['numero'] }}</td>
                    <td>{{ $r['passager'] }}</td>
                    <td>{{ $r['trajet'] }}</td>
                    <td>{{ $r['depart_le'] }}</td>
                    <td>{{ $r['pause_le'] }}</td>
                    <td class="right">{{ $f($r['montant']) }} F</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucun ticket en attente de report.</td></tr>
            @endforelse
        </tbody>
        @if($data['pausesAutoCount'] > 0)
            <tfoot>
                <tr>
                    <td colspan="5" class="right">Montant immobilisé</td>
                    <td class="right warn">{{ $f($data['pausesAutoMontant']) }} F</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- ── Dépenses ─────────────────────────────────────────────────── --}}
    <h2>Dépenses par catégorie</h2>
    <table class="list">
        <thead><tr><th>Catégorie</th><th class="right">Montant</th></tr></thead>
        <tbody>
            @forelse($data['depensesParCategorie'] as $r)
                <tr>
                    <td>{{ $r['categorie'] }}</td>
                    <td class="right">{{ $f($r['montant']) }} F</td>
                </tr>
            @empty
                <tr><td colspan="2" class="empty">Aucune dépense sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $compagnie?->name }} &nbsp;&middot;&nbsp; Édité le {{ now()->format('d/m/Y à H:i') }} &nbsp;&middot;&nbsp; LIPTRA
    </div>
</body>
</html>
