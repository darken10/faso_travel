{{--
    Liptra.net — Billet de voyage
    Format 240 mm × 101 mm, défini par PdfService::PAPER et calé sur la hauteur
    réelle du contenu (le reliquat se fond dans le gris appliqué sur <body>).
    DomPDF 3.x : layout 100 % table, aucun flex/grid, aucune ombre.
    Les largeurs de colonnes sont en pourcentage : DomPDF n'implémente pas
    table-layout:fixed et retomberait sur un dimensionnement par le contenu.
    Les arrondis sont portés par des <div> : border-radius n'est pas fiable
    sur les cellules de tableau.
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
    .city       { font-size: 25px; font-weight: bold; color: #0D1B3E; letter-spacing: -0.6px; line-height: 1.12; }
    .station    { font-size: 7.5px; color: #64748B; line-height: 1.35; margin-top: 5px; }
    .mono       { font-family: "DejaVu Sans Mono", monospace; }
    .rule       { border-top: 1px solid #E2E8F0; }
</style>
</head>
<body>

<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">

{{-- ══════════════ EN-TÊTE ══════════════ --}}
<tr>
    <td style="height:56px; background:#0D1B3E; padding:0 18px; vertical-align:middle;">
        <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:34px; vertical-align:middle;">
                @if ($logo)
                    <div style="width:30px; height:30px; border-radius:9px; background:#FFFFFF; padding:2px;">
                        <img src="{{ $logo }}" alt="" style="width:26px; height:26px;">
                    </div>
                @else
                    <div style="width:30px; height:30px; border-radius:9px; background:#2563EB; text-align:center; font-size:14px; line-height:30px;">
                        <span style="color:#FFFFFF; font-size:14px; font-weight:bold; line-height:1;">{{ mb_substr($company, 0, 1) }}</span>
                    </div>
                @endif
            </td>
            <td style="vertical-align:middle; padding-left:10px;">
                <div style="color:#FFFFFF; font-size:14px; font-weight:bold; letter-spacing:0.3px; line-height:1.15;">{{ $company }}</div>
                <div class="eyebrow-on" style="margin-top:2px;">Billet de voyage · Classe {{ $classe }}</div>
            </td>
            <td style="width:24%; text-align:right; vertical-align:middle;">
                {{-- Chip discret plutôt qu'un aplat bleu : #17285D est le rendu opaque de
                     rgba(37,99,235,.18) sur le navy, DomPDF étant peu fiable en rgba. --}}
                <div style="display:inline-block; background:#17285D; border:1px solid #3B82F6; border-radius:12px; padding:6px 13px; font-size:8.5px; line-height:1;">
                    <span style="color:#93C5FD; font-size:8.5px; font-weight:bold; letter-spacing:1.2px; text-transform:uppercase;">{{ $isRoundTrip ? 'Aller-retour' : 'Aller simple' }}</span>
                </div>
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

{{-- ══════════════ CORPS ══════════════ --}}
<tr>
    {{-- ─────────── Volet principal ─────────── --}}
    <td style="vertical-align:top; padding:0; background:#FFFFFF;">
    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">

        {{-- Trajet + QR --}}
        <tr>
            <td style="height:190px; vertical-align:middle; padding:0 0 0 18px;">

                <div style="display:inline-block; background:#EFF6FF; border-radius:11px; padding:6px 13px; margin-bottom:13px; font-size:8px; line-height:1;">
                    <span style="font-size:8px; font-weight:bold; color:#2563EB; text-transform:uppercase; letter-spacing:1px;">{{ $dateLabel }}</span>
                </div>

                <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Départ --}}
                    <td style="width:34%; vertical-align:top;">
                        <div class="eyebrow">Départ</div>
                        <div class="city" style="margin-top:3px;">{{ $depCity }}</div>
                        @if ($depStation)
                            <div class="station">{{ $depStation }}</div>
                        @endif
                    </td>

                    {{-- Flèche + durée --}}
                    <td style="width:28%; vertical-align:middle; padding:0 10px 16px;">
                        {{-- Une ligne + un glyphe séparés ne s'alignent pas verticalement
                             dans DomPDF : la flèche est donc un seul caractère. --}}
                        @if ($duration)
                            <div style="text-align:center; font-size:9.5px; font-weight:bold; color:#2563EB; margin-bottom:2px;">{{ $duration }}</div>
                        @endif
                        <div style="text-align:center; font-size:27px; color:#CBD5E1; line-height:1;">&#8594;</div>
                    </td>

                    {{-- Arrivée --}}
                    <td style="width:38%; vertical-align:top; text-align:right; padding-right:12px;">
                        <div class="eyebrow">Arrivée</div>
                        <div class="city" style="margin-top:3px;">{{ $arrCity }}</div>
                        @if ($arrStation)
                            <div class="station">{{ $arrStation }}</div>
                        @endif
                    </td>
                </tr>
                </table>
            </td>

            {{-- QR principal --}}
            <td style="width:24%; height:190px; vertical-align:middle; padding:0 18px 0 14px;">
                <div style="border:1px solid #E2E8F0; border-radius:14px; padding:8px; width:118px; margin-left:auto;">
                    <img src="{{ $qrCodePath }}" alt="" style="width:100px; height:100px; display:block;">
                </div>
                <div class="eyebrow" style="text-align:right; margin-top:7px;">Scan à l'embarquement</div>
            </td>
        </tr>

        {{-- Bandeau d'informations --}}
        <tr>
            <td colspan="2" style="height:106px; vertical-align:middle; padding:0 18px 16px;">
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px; padding:2px;">
            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
            <tr>
                <td width="27%" style="padding:11px 12px; vertical-align:middle;">
                    <div class="eyebrow">Passager</div>
                    <div class="value" style="margin-top:4px; line-height:1.2;">{{ $passenger }}</div>
                </td>
                <td width="11%" style="padding:11px 6px; vertical-align:middle; border-left:1px solid #E2E8F0; text-align:center;">
                    <div class="eyebrow">Siège</div>
                    <div style="display:inline-block; background:#FFF7ED; border-radius:9px; padding:5px 11px; margin-top:3px; font-size:18px; line-height:1;">
                        <span style="font-size:18px; font-weight:bold; color:#F97316; line-height:1;">{{ $ticket->numero_chaise }}</span>
                    </div>
                </td>
                <td width="13%" style="padding:11px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Départ</div>
                    <div class="value" style="margin-top:4px;">{{ $depTime }}</div>
                </td>
                <td width="17%" style="padding:11px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Embarquement</div>
                    <div class="value" style="margin-top:4px;">{{ $boardTime }}</div>
                </td>
                <td width="14%" style="padding:11px 9px; vertical-align:middle; border-left:1px solid #E2E8F0;">
                    <div class="eyebrow">Arrivée est.</div>
                    <div class="value" style="margin-top:4px;">{{ $arrTime ?? '—' }}</div>
                </td>
                <td width="18%" style="padding:11px 12px; vertical-align:middle; border-left:1px solid #E2E8F0; text-align:right;">
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
            </div>
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
            <td style="vertical-align:middle;">
                <div style="font-size:12.5px; font-weight:bold; color:#0D1B3E; line-height:1.25;">{{ $depCity }}</div>
                <div style="font-size:10px; color:#2563EB; line-height:1.3;">&#8595;</div>
                <div style="font-size:12.5px; font-weight:bold; color:#0D1B3E; line-height:1.25;">{{ $arrCity }}</div>
            </td>
            <td style="width:62px; text-align:right; vertical-align:middle;">
                <div style="border:1px solid #E2E8F0; border-radius:10px; padding:5px; width:60px; margin-left:auto;">
                    <img src="{{ $qrCodePath }}" alt="" style="width:48px; height:48px; display:block;">
                </div>
            </td>
        </tr>
        </table>

        <div style="font-size:8px; color:#64748B; margin-top:8px;">{{ $dateLabel }}</div>

        <table width="100%" cellspacing="0" cellpadding="0" class="rule" style="margin-top:8px;">
        <tr>
            <td style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Passager</div>
                <div style="font-size:10px; font-weight:bold; color:#1E293B; line-height:1.25; margin-top:3px;">{{ $passenger }}</div>
            </td>
        </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top:9px;">
        <tr>
            <td width="32%" style="vertical-align:top;">
                <div class="eyebrow">Siège</div>
                <div style="display:inline-block; background:#FFF7ED; border-radius:8px; padding:4px 9px; margin-top:2px; font-size:13px; line-height:1;">
                    <span style="font-size:13px; font-weight:bold; color:#F97316; line-height:1;">{{ $ticket->numero_chaise }}</span>
                </div>
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

        <table width="100%" cellspacing="0" cellpadding="0" class="rule" style="margin-top:8px;">
        <tr>
            <td width="52%" style="padding:9px 6px 0 0; vertical-align:top;">
                <div class="eyebrow">Classe</div>
                <div style="font-size:9.5px; font-weight:bold; color:#1E293B; line-height:1.25; margin-top:3px;">{{ $classe }}</div>
            </td>
            <td width="48%" style="padding:9px 0 0; vertical-align:top;">
                <div class="eyebrow">Véhicule</div>
                <div style="font-size:9.5px; font-weight:bold; color:#1E293B; line-height:1.25; margin-top:3px;">{{ $vehicle }}</div>
            </td>
        </tr>
        </table>

        <div style="background:#F1F5F9; border-radius:11px; padding:7px 11px; margin-top:9px;">
            <div class="eyebrow">Code SMS</div>
            <div class="mono" style="font-size:12px; font-weight:bold; color:#0D1B3E; letter-spacing:1.6px; margin-top:2px;">{{ $ticket->code_sms }}</div>
        </div>

    </td>
</tr>

{{-- ══════════════ PIED ══════════════ --}}
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
