<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'Achat - Ticket de Voyage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: #007bff;
            color: #ffffff;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            text-align: left;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            background: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .center{
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Confirmation d'Achat - Ticket de Voyage</h2>
    </div>
    <div class="content">
        <p>Bonjour <strong>{{$ticket->is_my_ticket ? ($ticket->user?->name ?? 'Client') : ($ticket->autre_personne?->name ?? $ticket->user?->name ?? 'Client')}}</strong>,</p>
        <p>Nous avons le plaisir de vous confirmer que votre achat de ticket a été effectué avec succès. Vous trouverez ci-dessous les détails de votre voyage :</p>
        <p><strong>Détails du Voyage :</strong></p>
        <ul>
            <li><strong>Numéro du ticket :</strong> {{$ticket->numero_ticket}}</li>
            <li><strong>Départ :</strong> {{$ticket->voyageInstance->villeDepart()->name}}</li>
            <li><strong>Destination :</strong> {{$ticket->voyageInstance->villeArrive()->name}}</li>
            <li><strong>Date et Heure :</strong> {{$ticket->voyageInstance->date->format("d/m/y")}} a {{$ticket->voyageInstance->heure->format("H\h i")}}</li>
            <li><strong>Siège :</strong> Chaise n°{{$ticket->numero_chaise }}</li>
            <li><strong>Prix :</strong> {{$ticket->prix()}} XOF</li>
        </ul>
        <div class=" center">
            <img src="{{ $qrImage }}" alt="Code QR" style="width:150px;height:150px;display:block;">
        </div>
        <div class=" center">
            {{$ticket->code_sms}}
        </div>

        <p>Vous trouverez en pièce jointe votre ticket en format PDF.</p>
        <p>Vous pouvez également visualiser votre ticket en cliquant sur le bouton ci-dessous :</p>
        <p class="center"><a href="{{route('ticket.show-ticket',$ticket)}}" class="button">voir mon ticket</a></p>
        <p>Bon voyage et merci d'avoir choisi notre service !</p>
    </div>
    <div class="footer">
        <p>&copy; 2025 Votre Liptra SARL. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>

