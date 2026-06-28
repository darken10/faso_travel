<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; margin:0;">
    <div style="max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden;">
        <div style="background:#2563eb; color:#fff; padding:18px 24px;">
            <h2 style="margin:0; font-size:18px;">Rapport d'activité — {{ $periodLabel }}</h2>
            <p style="margin:4px 0 0; font-size:12px; opacity:.85;">{{ $compagnie->name }}</p>
        </div>
        <div style="padding:24px;">
            <p style="font-size:14px; color:#374151;">Bonjour,</p>
            <p style="font-size:14px; color:#374151;">Voici la synthèse d'activité pour la période. Le rapport détaillé est en pièce jointe (PDF).</p>

            <table style="width:100%; border-collapse:collapse; margin:16px 0;">
                <tr>
                    <td style="padding:10px; border:1px solid #e5e7eb;">
                        <div style="font-size:10px; color:#6b7280; text-transform:uppercase;">Recettes</div>
                        <div style="font-size:16px; font-weight:bold; color:#16a34a;">{{ number_format($data['totalRecettes'], 0, ',', ' ') }} F</div>
                    </td>
                    <td style="padding:10px; border:1px solid #e5e7eb;">
                        <div style="font-size:10px; color:#6b7280; text-transform:uppercase;">Dépenses</div>
                        <div style="font-size:16px; font-weight:bold; color:#dc2626;">{{ number_format($data['totalDepenses'], 0, ',', ' ') }} F</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px; border:1px solid #e5e7eb;">
                        <div style="font-size:10px; color:#6b7280; text-transform:uppercase;">Bénéfice net</div>
                        <div style="font-size:16px; font-weight:bold; color:{{ $data['benefice'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ $data['benefice'] >= 0 ? '+' : '' }}{{ number_format($data['benefice'], 0, ',', ' ') }} F</div>
                    </td>
                    <td style="padding:10px; border:1px solid #e5e7eb;">
                        <div style="font-size:10px; color:#6b7280; text-transform:uppercase;">Tickets vendus</div>
                        <div style="font-size:16px; font-weight:bold; color:#111827;">{{ number_format($data['ticketsCount'], 0, ',', ' ') }}</div>
                    </td>
                </tr>
            </table>

            <p style="font-size:12px; color:#9ca3af;">LIPTRA — rapport généré automatiquement.</p>
        </div>
    </div>
</body>
</html>
