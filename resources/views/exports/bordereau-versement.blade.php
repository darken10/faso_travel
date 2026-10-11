<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 22px 0 8px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; width: 38%; }
        .amount { font-size: 16px; font-weight: bold; color: #047857; }
        .signatures td { height: 60px; width: 33%; vertical-align: top; }
    </style>
</head>
<body>
    <h1>Bordereau de versement de caisse</h1>
    <p class="muted">N° {{ $bordereau->numero }} · Émis le {{ $bordereau->emitted_at?->format('d/m/Y à H:i') }}</p>
    <table>
        <tr><th>Caissier / agent de vente</th><td>{{ $bordereau->emetteur?->name }}</td></tr>
        <tr><th>Session de caisse</th><td>Ouverte le {{ $bordereau->caisse?->opened_at?->format('d/m/Y H:i') }} · clôturée le {{ $bordereau->caisse?->closed_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Montant attendu</th><td>{{ number_format($bordereau->montant_attendu, 0, ',', ' ') }} F CFA</td></tr>
        <tr><th>Montant déclaré</th><td class="amount">{{ number_format($bordereau->montant_declare, 0, ',', ' ') }} F CFA</td></tr>
        <tr><th>Statut</th><td>{{ $bordereau->statut->label() }}</td></tr>
        @if($bordereau->montant_recu !== null)<tr><th>Montant reçu par la finance</th><td>{{ number_format($bordereau->montant_recu, 0, ',', ' ') }} F CFA</td></tr>@endif
        @if($bordereau->montant_comptabilise !== null)<tr><th>Montant comptabilisé</th><td>{{ number_format($bordereau->montant_comptabilise, 0, ',', ' ') }} F CFA</td></tr>@endif
        @if($bordereau->reference_comptable)<tr><th>Référence comptable</th><td>{{ $bordereau->reference_comptable }}</td></tr>@endif
    </table>
    <h2>Chaîne de responsabilité</h2>
    <table>
        <tr><th>Émis par</th><td>{{ $bordereau->emetteur?->name }} · {{ $bordereau->emitted_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Reçu par la finance</th><td>{{ $bordereau->recepteurFinance?->name ?? '—' }} @if($bordereau->received_at) · {{ $bordereau->received_at->format('d/m/Y H:i') }} @endif</td></tr>
        <tr><th>Transmis par</th><td>{{ $bordereau->transmetteur?->name ?? '—' }} @if($bordereau->transmitted_at) · {{ $bordereau->transmitted_at->format('d/m/Y H:i') }} @endif</td></tr>
        <tr><th>Comptabilisé par</th><td>{{ $bordereau->comptable?->name ?? '—' }} @if($bordereau->accounted_at) · {{ $bordereau->accounted_at->format('d/m/Y H:i') }} @endif</td></tr>
    </table>
    <h2>Signatures</h2>
    <table class="signatures"><tr><td>Agent de vente</td><td>Finance / DSF</td><td>Comptabilité</td></tr></table>
</body>
</html>
