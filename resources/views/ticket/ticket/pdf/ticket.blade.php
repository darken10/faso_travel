{{--
    Liptra.net — Billet de voyage
    Format 240 mm × 105 mm (907 px × 397 px @ 96 dpi), défini par PdfService::PAPER.
    Hauteurs : en-tête 58 + corps 300 + pied 37 = 395 px (marge de 2 px sous la page).
    DomPDF 3.x : layout 100 % table, aucun flex/grid, aucune ombre.
    Les largeurs de colonnes sont en pourcentage : DomPDF n'implémente pas
    table-layout:fixed et retomberait sur un dimensionnement par le contenu.
    Toutes les valeurs d'affichage viennent de PdfService::viewData().
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Billet de voyage — {{ $ticket->numero_ticket }}</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; background: #F1F5F9; }

    .eyebrow    { font-size: 7px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.1px; }
    .eyebrow-on { font-size: 7px; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 1.1px; }
    .value      { font-size: 12px; font-weight: bold; color: #1E293B; }
    .mono       { font-family: "DejaVu Sans Mono", monospace; }
    .rule       { border-top: 1px solid #E2E8F0; }
</style>
</head>
<body>

<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">

{{-- ══════════════ EN-TÊTE · 58 px ══════════════ --}}
<tr>
    <td style="height:56px; background:#0D1B3E; padding:0 18px; vertical-align:middle;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:34px; vertical-align:middle;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" style="width:30px; height:30px;">
                @else
                    <table cellspacing="0" cellpadding="0" style="background:#2563EB; width:30px; height:30px;">
                    <tr><td style="text-align:center; vertical-align:middle; color:#FFFFFF; font-size:14px; font-weight:bold;">{{ mb_substr($company, 0, 1) }}</td></tr>
                    </table>
                @endif
            </td>
            <td style="vertical-align:middle; padding-left:10px;">
                <div style="color:#FFFFFF; font-size:14px; font-weight:bold; letter-spacing:0.3px; line-height:1.15;">{{ $company }}</div>
                <div class="eyebrow-on" style="margin-top:2px;">Billet de voyage · Classe {{ $classe }}</div>
            </td>
            <td style="width:22%; text-align:right; vertical-align:middle;">
                <table cellspacing="0" cellpadding="0" align="right">
                <tr><td style="background:#2563EB; padding:5px 10px;">
                    <span style="color:#FFFFFF; font-size:8.5px; font-weight:bold; letter-spacing:1.2px; text-transform:uppercase;">{{ $isRoundTrip ? 'Aller-retour' : 'Aller simple' }}</span>
                </td></tr>
                </table>
            </td>
        </tr>
        </table>
    </td>

    <td style="width:2px; background:#0D1B3E; border-left:2px dashed rgba(255,255,255,0.28); font-size:0; line-height:0;">&nbsp;</td>

    <td width="26%" style="height:56px; background:#0D1B3E; padding:0 15px; vertical-align:middle;">
        <div class="eyebrow-on">Souche · à conserver</div>
        <div class="mono" style="color:#FFFFFF; font-size:11px; font-weight:bold; letter-spacing:0.6px; margin-top:3px;">{{ $ticket->numero_ticket }}</div>
    </td>
</tr>

{{-- ══════════════ CORPS · 300 px ══════════════ --}}
<tr>
    {{-- ─────────── Volet principal ─────────── --}}
    <td style="vertical-align:top; padding:0; background:#FFFFFF;">
    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">

        {{-- Trajet + QR · 188 px --}}
        <tr>
            <td style="height:156px; vertical-align:middle; padding:0 0 0 18px;">
            <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                {{-- Départ --}}
                <td style="width:38%; vertical-align:top;">
                    <div class="eyebrow">Départ</div>
                    <div style="font-size:40px; font-weight:bold; color:#0D1B3E; letter-spacing:-1.5px; line-height:1.05; margin-top:2px;">{{ $depCode }}</div>
                    <div style="font-size:13px; font-weight:bold; color:#2563EB; line-height:1.2; margin-top:1px;">{{ $depCity }}</div>
                    @if ($depStation)
                        <div style="font-size:7.5px; color:#64748B; line-height:1.35; margin-top:4px;">{{ $depStation }}</div>
                    @endif
                </td>

                {{-- Liaison --}}
                <td style="width:24%; vertical-align:middle; padding:0 8px 28px;">
                    <div style="text-align:center; font-size:8px; color:#64748B; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:6px;">{{ $dateLabel }}</div>
                    <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="border-top:1.5px solid #CBD5E1; font-size:0; line-height:0;">&nbsp;</td>
                        <td style="width:1%; white-space:nowrap; padding:0 4px; font-size:9px; color:#2563EB; line-height:1;">&#9679;</td>
                        <td style="border-top:1.5px solid #CBD5E1; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    </table>
                    @if ($duration)
                        <div style="text-align:center; font-size:8.5px; font-weight:bold; color:#94A3B8; margin-top:5px;">{{ $duration }}</div>
                    @endif
                </td>

                {{-- Arrivée --}}
                <td style="width:38%; vertical-align:top; text-align:right; padding-right:12px;">
                    <div class="eyebrow">Arrivée</div>
                    <div style="font-size:40px; font-weight:bold; color:#0D1B3E; letter-spacing:-1.5px; line-height:1.05; margin-top:2px;">{{ $arrCode }}</div>
                    <div style="font-size:13px; font-weight:bold; color:#2563EB; line-height:1.2; margin-top:1px;">{{ $arrCity }}</div>
                    @if ($arrStation)
                        <div style="font-size:7.5px; color:#64748B; line-height:1.35; margin-top:4px;">{{ $arrStation }}</div>
                    @endif
                </td>
            </tr>
            </table>
            </td>

            {{-- QR principal --}}
            <td style="width:24%; height:156px; vertical-align:middle; padding:0 18px 0 14px;">
                <img src="{{ $qrCodePath }}" alt="" style="width:104px; height:104px; display:block; margin-left:auto;">
                <div class="eyebrow" style="text-align:right; margin-top:6px;">Scan à l'embarquement</div>
            </td>
        </tr>

        {{-- Bandeau d'informations · 112 px --}}
        <tr>
            <td colspan="2" style="height:96px; vertical-align:middle; padding:0 18px 14px;">
            <table width="100%" cellspacing="0" cellpadding="0"
                   style="background:#F8FAFC; border:1px solid #E2E8F0; border-collapse:collapse;">
            <tr>
                <td width="27%" style="padding:12px; vertical-align:middle;">
                    <div class="eyebrow">Passager</div>
                    <div class="value" style="margin-top:4px; line-height:1.2;">{{ $passenger }}</div>
                </td>
                <td width="10%" style="padding:12px 8px; vertical-align:middle; border-left:1px solid #E2E8F0; text-align:center;">
                    <div class="eyebrow">Siège</div>
                    <div style="font-size:21px; font-weight:bold; color:#F97316; line-height:1.1; margin-top:1px;">{{ $ticket->numero_chaise }}</div>
                </td>
                <td width="14%" style="padding:12px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Départ</div>
                    <div class="value" style="margin-top:4px;">{{ $depTime }}</div>
                </td>
                <td width="17%" style="padding:12px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Embarquement</div>
                    <div class="value" style="margin-top:4px;">{{ $boardTime }}</div>
                </td>
                <td width="14%" style="padding:12px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Arrivée est.</div>
                    <div class="value" style="margin-top:4px;">{{ $arrTime ?? '—' }}</div>
                </td>
                <td width="18%" style="padding:12px; vertical-align:middle; border-left:1px solid #E2E8F0; text-align:right;">
                    <div class="eyebrow">Tarif</div>
                    <div style="font-size:14px; font-weight:bold; color:#0D1B3E; margin-top:3px;">
                        {{ $price }}<span style="font-size:8px; font-weight:normal; color:#94A3B8;"> XOF</span>
                    </div>
                    @if ($reduction)
                        <div style="font-size:7px; color:#059669; margin-top:1px;">Remise {{ $reduction }} XOF</div>
                    @endif
                </td>
            </tr>
            </table>
            </td>
        </tr>

    </table>
    </td>

    {{-- Perforation --}}
    <td style="width:2px; border-left:2px dashed #CBD5E1; font-size:0; line-height:0; padding:0;">&nbsp;</td>

    {{-- ─────────── Souche ─────────── --}}
    <td width="26%" style="vertical-align:top; padding:15px; background:#FCFDFE;">

        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:17px; font-weight:bold; color:#0D1B3E; letter-spacing:-0.4px; line-height:1.1;">
                    {{ $depCode }} <span style="color:#94A3B8; font-size:12px;">&#8594;</span> {{ $arrCode }}
                </div>
                <div style="font-size:8px; color:#64748B; margin-top:3px;">{{ $dateLabel }}</div>
            </td>
            <td style="width:56px; text-align:right; vertical-align:top;">
                <img src="{{ $qrCodePath }}" alt="" style="width:52px; height:52px; display:block; margin-left:auto;">
            </td>
        </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="0" class="rule" style="margin-top:11px;">
        <tr>
            <td style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Passager</div>
                <div style="font-size:10px; font-weight:bold; color:#1E293B; line-height:1.25; margin-top:3px;">{{ $passenger }}</div>
            </td>
        </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top:10px;">
        <tr>
            <td width="32%" style="vertical-align:top;">
                <div class="eyebrow">Siège</div>
                <div style="font-size:15px; font-weight:bold; color:#F97316; margin-top:2px;">{{ $ticket->numero_chaise }}</div>
            </td>
            <td width="34%" style="vertical-align:top;">
                <div class="eyebrow">Départ</div>
                <div style="font-size:11px; font-weight:bold; color:#1E293B; margin-top:4px;">{{ $depTime }}</div>
            </td>
            <td width="34%" style="vertical-align:top;">
                <div class="eyebrow">Embarq.</div>
                <div style="font-size:11px; font-weight:bold; color:#1E293B; margin-top:4px;">{{ $boardTime }}</div>
            </td>
        </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="0" class="rule" style="margin-top:11px;">
        <tr>
            <td width="50%" style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Classe</div>
                <div style="font-size:9.5px; font-weight:bold; color:#1E293B; margin-top:3px;">{{ $classe }}</div>
            </td>
            <td width="50%" style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Véhicule</div>
                <div style="font-size:9.5px; font-weight:bold; color:#1E293B; margin-top:3px;">{{ $vehicle }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Code SMS</div>
                <div class="mono" style="font-size:12px; font-weight:bold; color:#0D1B3E; letter-spacing:1.6px; margin-top:3px;">{{ $ticket->code_sms }}</div>
            </td>
        </tr>
        </table>

    </td>
</tr>

{{-- ══════════════ PIED · 37 px ══════════════ --}}
<tr>
    <td colspan="3" style="height:36px; background:#F1F5F9; border-top:1px solid #E2E8F0; padding:0 18px; vertical-align:middle;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align:middle;">
                <span class="mono" style="font-size:8px; color:#2563EB; letter-spacing:0.3px;">{{ $ticket->numero_ticket }}</span>
                <span style="color:#CBD5E1; font-size:9px; margin:0 6px;">|</span>
                <span style="font-size:7.5px; color:#94A3B8;">Émis le {{ $emittedAt }}</span>
                <span style="color:#CBD5E1; font-size:9px; margin:0 6px;">|</span>
                <span style="font-size:7.5px; color:#94A3B8;">Présentez-vous à la gare 10 min avant l'embarquement · Pièce d'identité exigée</span>
            </td>
            <td style="width:90px; text-align:right; vertical-align:middle;">
                <span style="font-size:8.5px; font-weight:bold; color:#0D1B3E; letter-spacing:2.2px;">LIPTRA.NET</span>
            </td>
        </tr>
        </table>
    </td>
</tr>

</table>

</body>
</html>
