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
        .cards { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .cards td { width: 25%; padding: 8px; border: 1px solid #e5e7eb; }
        .clabel { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .cval { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .pos { color: #16a34a; } .neg { color: #dc2626; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.list th { background: #f3f4f6; color: #374151; text-align: left; padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
        table.list td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #f0f0f0; }
        .sign { margin-top: 30px; width: 100%; }
        .sign td { width: 50%; padding-top: 30px; font-size: 11px; color: #6b7280; }
        .line { border-top: 1px solid #9ca3af; width: 70%; padding-top: 3px; }
        .footer { margin-top: 18px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @php
        $isOpen = $caisse->isOuverte();
        $ecart  = $isOpen ? null : $caisse->ecart();
    @endphp

    <div class="header">
        <div class="title">Rapport de caisse</div>
        <div class="sub">
            {{ $caisse->compagnie?->name }} — Agent : {{ $caisse->user?->name ?? $caisse->user?->first_name }}
        </div>
    </div>

    <table style="width:100%; font-size:11px; margin-bottom:8px;">
        <tr>
            <td><span style="color:#6b7280;">Ouverture :</span> <strong>{{ $caisse->opened_at?->format('d/m/Y H:i') }}</strong></td>
            <td><span style="color:#6b7280;">Fermeture :</span> <strong>{{ $caisse->closed_at?->format('d/m/Y H:i') ?? 'En cours' }}</strong></td>
            <td><span style="color:#6b7280;">Statut :</span> <strong>{{ $isOpen ? 'Ouverte' : 'Fermée' }}</strong></td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="clabel">Fond initial</div><div class="cval">{{ number_format($caisse->montant_ouverture, 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Total ventes</div><div class="cval">{{ number_format($caisse->totalVentes(), 0, ',', ' ') }} F</div></td>
            <td><div class="clabel">Montant attendu</div><div class="cval">{{ number_format($caisse->calculerMontantAttendu(), 0, ',', ' ') }} F</div></td>
            <td>
                <div class="clabel">{{ $isOpen ? 'Nb. tickets' : 'Écart' }}</div>
                @if($isOpen)
                    <div class="cval">{{ $caisse->nombreTickets() }}</div>
                @else
                    <div class="cval {{ $ecart >= 0 ? 'pos' : 'neg' }}">{{ $ecart >= 0 ? '+' : '' }}{{ number_format($ecart ?? 0, 0, ',', ' ') }} F</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th>N° Ticket</th>
                <th>Client</th>
                <th>Trajet</th>
                <th>Siège</th>
                <th>Statut</th>
                <th>Créé le</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $t)
                <tr>
                    <td>{{ $t->numero_ticket }}</td>
                    <td>{{ $t->autre_personne?->name ?? $t->user?->name ?? '—' }}</td>
                    <td>{{ $t->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $t->voyageInstance?->voyage?->trajet?->arriver?->name }}</td>
                    <td>{{ $t->numero_chaise }}</td>
                    <td>{{ $t->statut?->value }}</td>
                    <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:14px;">Aucun ticket.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td><div class="line">Signature de l'agent</div></td>
            <td><div class="line">Signature du responsable</div></td>
        </tr>
    </table>

    <div class="footer">Édité le {{ now()->format('d/m/Y à H:i') }} — LIPTRA</div>
</body>
</html>
