{{--
    Liptra.net — Billet de Voyage (Boarding Pass)
    Format : 240 mm × 105 mm  –  ratio 2.28:1, clairement compact
    DomPDF 3.x — layout 100 % table-based, aucun Flexbox/Grid
    240 mm ≈ 907 px  |  105 mm ≈ 397 px  (@ 96 dpi)
    Card : 397 − 2×6 = 385 px  →  Header 48 + Body 232 + Details 73 + Etkt 32 = 385
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Billet de Voyage — Liptra.net</title>
<style>
    @page { margin: 0; size: 240mm 105mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        background: #C8CDD8;
        width: 100%;
        height: 397px;
    }
</style>
</head>
<body>

@php
    if ($ticket->is_my_ticket) {
        $passenger = $ticket->user?->name ?? '';
    } elseif ($ticket->autre_personne_id !== null) {
        $passenger = $ticket->autre_personne?->name ?? '';
    } elseif ($ticket->transferer_a_user_id !== null) {
        $passenger = \App\Models\User::find($ticket->transferer_a_user_id)?->name ?? '';
    } else {
        $passenger = $ticket->user?->name ?? '';
    }

    $vi      = $ticket->voyageInstance;
    $depName = $vi->villeDepart()->name;
    $arrName = $vi->villeArrive()->name;
    $depCode = mb_strtoupper(mb_substr($depName, 0, 3), 'UTF-8');
    $arrCode = mb_strtoupper(mb_substr($arrName, 0, 3), 'UTF-8');
    $depTime = $vi->heure->format('H\hi');
    $rdvTime = $ticket->heureRdv()->format('H\hi');
    $dateVoy = $vi->date->format('d/m/Y');
    $frDays  = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $dayName = $frDays[$vi->date->dayOfWeek];
    $prix    = number_format($ticket->prix(), 0, ',', ' ');
    $classe  = $ticket->classe()?->name ?? '—';
    $company = strtoupper($ticket->compagnie()->name);
    $immat   = $vi->care?->immatrculation ?? '—';
    $isRound = $ticket->type === \App\Enums\TypeTicket::AllerRetour;
    $emitted = now()->format('d/m/Y à H\hi');
@endphp

{{-- ══════════════════════════════════
     FOND  397 px × 100 %
     ══════════════════════════════════ --}}
<table width="100%" cellspacing="0" cellpadding="0"
       style="background:#C8CDD8; height:397px; width:100%;">
<tr>
<td style="padding:6px 8px; vertical-align:top;">

{{-- ══════════════════════════════════════════════════════════════
     CARTE  385 px
     Header 48 · Corps 232 · Détails 73 · Etkt 32 = 385
     ══════════════════════════════════════════════════════════════ --}}
<table width="100%" cellspacing="0" cellpadding="0"
       style="background:#FFFFFF; border-collapse:collapse;">

{{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     HEADER  48 px — bleu #1A4FBF
     ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
<tr>

    {{-- Logo + titre gauche --}}
    <td style="background:#1A4FBF; height:48px; padding:0 14px 0 18px;
               vertical-align:middle;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align:middle;">
                <div style="color:#FFFFFF; font-size:17px; font-weight:bold;
                            letter-spacing:0.8px; line-height:1.2;">
                    &#9992;&nbsp;{{ $company }}
                </div>
                <div style="color:rgba(255,255,255,0.50); font-size:7px;
                            letter-spacing:2.5px; margin-top:2px; text-transform:uppercase;">
                    LIPTRA.NET
                </div>
            </td>
            <td style="text-align:right; vertical-align:middle; width:36%;">
                <div style="color:#FFFFFF; font-size:12px; font-weight:bold;
                            letter-spacing:2px; text-transform:uppercase; line-height:1.3;">
                    BILLET DE VOYAGE
                </div>
                <div style="color:rgba(255,255,255,0.55); font-size:7px;
                            letter-spacing:2px; margin-top:2px; text-transform:uppercase;">
                    {{ $classe }}
                </div>
            </td>
        </tr>
        </table>
    </td>

    {{-- Séparateur tirets --}}
    <td style="background:#1A4FBF; width:2px; height:48px; padding:0;
               border-left:2px dashed rgba(255,255,255,0.30);
               font-size:0; line-height:0;">&nbsp;</td>

    {{-- En-tête stub --}}
    <td style="background:#1A4FBF; width:27%; height:48px; padding:0 12px;
               text-align:center; vertical-align:middle;">
        <div style="color:#FFFFFF; font-size:11px; font-weight:bold;
                    letter-spacing:2px; text-transform:uppercase;">BILLET DE VOYAGE</div>
        <div style="color:rgba(255,255,255,0.55); font-size:7px;
                    letter-spacing:2px; margin-top:2px; text-transform:uppercase;">{{ $classe }}</div>
    </td>

</tr>

{{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     CORPS  232 px
     ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
<tr>

    {{-- ─── Section principale : FROM | TO | QR ─── --}}
    <td style="height:232px; vertical-align:middle; padding:0;">
    <table width="100%" cellspacing="0" cellpadding="0">
    <tr>

        {{-- FROM --}}
        <td style="width:37%; padding:0 4px 0 18px; vertical-align:middle;">
            <div style="font-size:9px; color:#6B7280; font-weight:bold;
                        margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">
                From:
            </div>
            <div style="font-size:64px; font-weight:bold; color:#1A4FBF;
                        letter-spacing:-3px; line-height:0.88;">{{ $depCode }}</div>
            <div style="font-size:15px; font-weight:bold; color:#1A4FBF;
                        margin-top:3px; margin-bottom:6px; line-height:1.1;">{{ $depName }}</div>

            {{-- Ligne flèche --}}
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:6px;">
            <tr>
                <td style="border-top:1.5px solid #1A4FBF; font-size:0; line-height:0;">&nbsp;</td>
                <td style="font-size:12px; color:#1A4FBF; vertical-align:middle;
                           width:1%; white-space:nowrap; padding:0 0 0 2px; line-height:1;">&#9658;</td>
            </tr>
            </table>

            <div style="font-size:9px; color:#4B5563; text-transform:uppercase;
                        letter-spacing:0.3px; margin-bottom:2px;">{{ $dayName }} {{ $dateVoy }}</div>
            <div style="font-size:9px; color:#374151;">
                Départ :&nbsp;<span style="font-weight:bold; font-size:10px;">{{ $depTime }}</span>
            </div>
        </td>

        {{-- TO --}}
        <td style="width:38%; padding:0 4px 0 8px; vertical-align:middle;">
            <div style="font-size:9px; color:#6B7280; font-weight:bold;
                        margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">
                &#192;:
            </div>
            <div style="font-size:64px; font-weight:bold; color:#1A4FBF;
                        letter-spacing:-3px; line-height:0.88;">{{ $arrCode }}</div>
            <div style="font-size:15px; font-weight:bold; color:#1A4FBF;
                        margin-top:3px; margin-bottom:6px; line-height:1.1;">{{ $arrName }}</div>
            {{-- Espace = hauteur de la ligne flèche --}}
            <div style="height:15px; margin-bottom:6px;">&nbsp;</div>
            <div style="font-size:9px; color:#4B5563; text-transform:uppercase;
                        letter-spacing:0.3px; margin-bottom:2px;">{{ $dayName }} {{ $dateVoy }}</div>
            <div style="font-size:9px; color:#374151;">
                Emb. :&nbsp;<span style="font-weight:bold; font-size:10px;">{{ $rdvTime }}</span>
            </div>
        </td>

        {{-- QR code --}}
        <td style="width:25%; padding:0 16px 0 4px; vertical-align:middle;">
            <img src="{{ $qrCodePath }}" alt="QR Code"
                 style="width:108px; height:108px; display:block; margin-left:auto;">
        </td>

    </tr>
    </table>
    </td>

    {{-- Séparateur tirets principal --}}
    <td style="width:2px; height:232px; padding:0;
               border-left:2px dashed #B0BBD0;
               font-size:0; line-height:0;">&nbsp;</td>

    {{-- ─── STUB ─── --}}
    <td style="width:27%; height:232px; vertical-align:top;
               padding:9px 13px 7px;">

        {{-- From/To compact --}}
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:42%; vertical-align:top;">
                <div style="font-size:7.5px; color:#6B7280; font-weight:bold;
                            margin-bottom:2px; text-transform:uppercase;">From:</div>
                <div style="font-size:24px; font-weight:bold; color:#1A4FBF;
                            letter-spacing:-1px; line-height:1;">{{ $depCode }}</div>
                <div style="font-size:9px; font-weight:bold; color:#1A4FBF;
                            margin-top:2px; line-height:1.2;">{{ $depName }}</div>
                <div style="font-size:7.5px; color:#4B5563; margin-top:4px;
                            text-transform:uppercase; letter-spacing:0.2px;">{{ $dateVoy }}</div>
                <div style="font-size:9px; font-weight:bold; color:#111827;
                            margin-top:1px;">{{ $depTime }}</div>
            </td>
            {{-- Mini flèche centrale --}}
            <td style="width:16%; text-align:center; vertical-align:middle; padding-bottom:20px;">
                <table cellspacing="0" cellpadding="0" align="center">
                <tr>
                    <td style="border-top:1.5px solid #1A4FBF; width:14px;
                               font-size:0; line-height:0;">&nbsp;</td>
                    <td style="font-size:8px; color:#1A4FBF; vertical-align:middle;
                               padding:0 0 0 1px; line-height:1;">&#9658;</td>
                </tr>
                </table>
            </td>
            <td style="width:42%; vertical-align:top;">
                <div style="font-size:7.5px; color:#6B7280; font-weight:bold;
                            margin-bottom:2px; text-transform:uppercase;">To:</div>
                <div style="font-size:24px; font-weight:bold; color:#1A4FBF;
                            letter-spacing:-1px; line-height:1;">{{ $arrCode }}</div>
                <div style="font-size:9px; font-weight:bold; color:#1A4FBF;
                            margin-top:2px; line-height:1.2;">{{ $arrName }}</div>
                <div style="font-size:7.5px; color:#4B5563; margin-top:4px;
                            text-transform:uppercase; letter-spacing:0.2px;">{{ $dateVoy }}</div>
                <div style="font-size:9px; font-weight:bold; color:#111827;
                            margin-top:1px;">{{ $rdvTime }}</div>
            </td>
        </tr>
        </table>

        {{-- Infos passager --}}
        <table width="100%" cellspacing="0" cellpadding="0"
               style="margin-top:8px; border-top:1px solid #E5E7EB;">
            <tr>
                <td style="padding:5px 3px 3px 0; vertical-align:top;" width="56%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Passenger</div>
                    <div style="font-size:9px; font-weight:bold; color:#1F2937;
                                text-transform:uppercase; line-height:1.2;">{{ $passenger }}</div>
                </td>
                <td style="padding:5px 0 3px; vertical-align:top;" width="44%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Classe</div>
                    <div style="font-size:9px; font-weight:bold; color:#1F2937;">{{ $classe }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:3px 3px 3px 0; vertical-align:top;" width="56%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Seat</div>
                    <div style="font-size:13px; font-weight:bold; color:#1F2937;">{{ $ticket->numero_chaise }}</div>
                </td>
                <td style="padding:3px 0; vertical-align:top;" width="44%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Embarq.</div>
                    <div style="font-size:13px; font-weight:bold; color:#1F2937;">{{ $rdvTime }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:3px 3px 0 0; vertical-align:top;" width="56%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Type</div>
                    <div style="font-size:8px; font-weight:bold; color:#1F2937;">
                        {{ $isRound ? 'Aller-Retour' : 'Aller Simple' }}
                    </div>
                </td>
                <td style="padding:3px 0 0; vertical-align:top;" width="44%">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Code SMS</div>
                    <div style="font-size:8px; font-weight:bold; color:#1F2937;
                                letter-spacing:1px;">{{ $ticket->code_sms }}</div>
                </td>
            </tr>
        </table>

        {{-- Véhicule + QR stub --}}
        <table width="100%" cellspacing="0" cellpadding="0"
               style="margin-top:6px; border-top:1px solid #E5E7EB;">
            <tr>
                <td style="padding-top:5px; vertical-align:bottom;">
                    <div style="font-size:7px; color:#9CA3AF; margin-bottom:1px; text-transform:uppercase;">Véhicule</div>
                    <div style="font-size:8.5px; font-weight:bold; color:#1F2937;
                                margin-bottom:5px;">{{ $immat }}</div>
                    <span style="font-size:7.5px; color:#1A4FBF;
                                 font-family:DejaVu Sans Mono, monospace; letter-spacing:0.3px;">
                        Etkt: {{ $ticket->numero_ticket }}
                    </span>
                </td>
                <td style="padding-top:5px; text-align:right; vertical-align:bottom;">
                    <img src="{{ $qrCodePath }}" alt="QR"
                         style="width:60px; height:60px; display:block; margin-left:auto;">
                </td>
            </tr>
        </table>

    </td>

</tr>

{{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     BANDE DÉTAILS  73 px
     ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
<tr>
    <td colspan="3"
        style="height:73px; background:#EEF0F7;
               border-top:1.5px solid #D0D5E3;
               vertical-align:middle; padding:0;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            {{-- Passager --}}
            <td style="padding:0 0 0 18px; vertical-align:middle;
                        border-right:1px solid #D5D9E6;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Passager</div>
                <div style="font-size:12px; font-weight:bold; color:#111827;
                            text-transform:uppercase;">{{ $passenger }}</div>
            </td>
            {{-- Siège --}}
            <td style="padding:0 0 0 12px; vertical-align:middle;
                        border-right:1px solid #D5D9E6; width:9%;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Seat</div>
                <div style="font-size:17px; font-weight:bold; color:#111827;">
                    {{ $ticket->numero_chaise }}
                </div>
            </td>
            {{-- Classe --}}
            <td style="padding:0 0 0 12px; vertical-align:middle;
                        border-right:1px solid #D5D9E6; width:13%;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Classe</div>
                <div style="font-size:12px; font-weight:bold; color:#111827;">{{ $classe }}</div>
            </td>
            {{-- Embarquement --}}
            <td style="padding:0 0 0 12px; vertical-align:middle;
                        border-right:1px solid #D5D9E6; width:12%;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Embarq.</div>
                <div style="font-size:12px; font-weight:bold; color:#111827;">{{ $rdvTime }}</div>
            </td>
            {{-- Véhicule --}}
            <td style="padding:0 0 0 12px; vertical-align:middle;
                        border-right:1px solid #D5D9E6; width:12%;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Véhicule</div>
                <div style="font-size:12px; font-weight:bold; color:#111827;">{{ $immat }}</div>
            </td>
            {{-- Tarif --}}
            <td style="padding:0 14px 0 12px; vertical-align:middle; width:13%;">
                <div style="font-size:8px; color:#6B7280; margin-bottom:4px;
                            text-transform:uppercase; letter-spacing:0.5px;">Tarif</div>
                <div style="font-size:12px; font-weight:bold; color:#111827;">
                    {{ $prix }}&nbsp;<span style="font-size:8px; font-weight:normal; color:#9CA3AF;">XOF</span>
                </div>
            </td>
        </tr>
        </table>
    </td>
</tr>

{{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     LIGNE ÉTKT  32 px
     ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
<tr>
    <td colspan="3"
        style="height:32px; border-top:1px solid #D5D9E6;
               padding:0 18px; vertical-align:middle; background:#F5F7FC;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align:middle;">
                <span style="font-size:9px; color:#1A4FBF; letter-spacing:0.5px;
                             font-family:DejaVu Sans Mono, monospace;">
                    Etkt : {{ $ticket->numero_ticket }}
                </span>
                <span style="color:#D1D5DB; font-size:10px; margin:0 8px;">|</span>
                <span style="font-size:8.5px; color:#9CA3AF;">
                    Émis le {{ $emitted }}
                </span>
            </td>
            <td style="text-align:right; vertical-align:middle;">
                <span style="font-size:9.5px; font-weight:bold; color:#1A4FBF;
                             letter-spacing:2.5px; text-transform:uppercase;">
                    LIPTRA.NET
                </span>
            </td>
        </tr>
        </table>
    </td>
</tr>

</table>{{-- /card --}}
</td>
</tr>
</table>{{-- /page wrapper --}}

</body>
</html>
