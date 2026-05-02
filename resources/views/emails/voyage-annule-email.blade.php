<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voyage annulé</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #dc2626; color: #ffffff; padding: 20px 24px; text-align: center; }
        .header h2 { margin: 0; font-size: 20px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: .85; }
        .content { padding: 24px; color: #374151; font-size: 14px; line-height: 1.6; }
        .info-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px 16px; margin: 16px 0; }
        .info-box li { margin-bottom: 6px; }
        .reason-box { background: #f9fafb; border-left: 4px solid #dc2626; border-radius: 0 6px 6px 0; padding: 12px 16px; margin: 16px 0; font-size: 13px; color: #4b5563; }
        .badge { display: inline-block; background: #fee2e2; color: #991b1b; font-size: 12px; font-weight: bold; padding: 3px 10px; border-radius: 20px; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Voyage annulé</h2>
        <p>Nous vous informons que votre voyage a été annulé</p>
    </div>
    <div class="content">
        <p>Bonjour <strong>{{ $ticket->user?->name }}</strong>,</p>
        <p>
            Nous sommes au regret de vous informer que le voyage pour lequel vous avez acheté un ticket
            a été <strong>annulé</strong> par la compagnie. Votre ticket a été suspendu
            (<span class="badge">En pause</span>) dans l'attente d'un remboursement ou d'une nouvelle affectation.
        </p>

        <p><strong>Détails du voyage :</strong></p>
        <div class="info-box">
            <ul style="margin:0; padding-left:18px;">
                <li><strong>Numéro de ticket :</strong> {{ $ticket->numero_ticket }}</li>
                <li><strong>Trajet :</strong>
                    {{ $ticket->voyageInstance?->villeDepart()?->name }}
                    →
                    {{ $ticket->voyageInstance?->villeArrive()?->name }}
                </li>
                <li><strong>Date prévue :</strong> {{ $ticket->voyageInstance?->date?->format('d/m/Y') }}</li>
                <li><strong>Heure :</strong> {{ $ticket->voyageInstance?->heure?->format('H:i') }}</li>
                <li><strong>Compagnie :</strong> {{ $ticket->voyageInstance?->voyage?->compagnie?->name }}</li>
            </ul>
        </div>

        @if(!empty($message))
            <p><strong>Message de la compagnie :</strong></p>
            <div class="reason-box">{{ $message }}</div>
        @endif

        <p>
            Notre équipe vous contactera prochainement pour vous proposer une solution
            (remboursement ou report sur un autre voyage). Vous pouvez également contacter
            directement la compagnie pour plus d'informations.
        </p>
        <p>Nous nous excusons pour la gêne occasionnée et vous remercions de votre compréhension.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} LIPTRA — Service de transport</p>
    </div>
</div>
</body>
</html>
