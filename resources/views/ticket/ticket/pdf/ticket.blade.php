{{--
    Liptra.net — Carte d'embarquement premium
    DomPDF 3.x / A4 paysage (297mm × 210mm)
    Layout : table-based uniquement pour compatibilité maximale
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Billet de Voyage — Liptra.net</title>
    <style>
        @page { margin: 0; size: A4 landscape; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #0B1630;
            width: 100%;
        }

        /* ─── helpers ─── */
        .lbl {
            font-size: 7.5px;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 1.3px;
            margin-bottom: 3px;
        }
        .val {
            font-size: 12.5px;
            font-weight: bold;
            color: #1E293B;
            line-height: 1.2;
        }
    </style>
</head>
<body>

@php
    /* ── Resolve passenger name ── */
    if ($ticket->is_my_ticket) {
        $passenger = $ticket->user?->name ?? '';
    } elseif ($ticket->autre_personne_id !== null) {
        $passenger = $ticket->autre_personne?->name ?? '';
    } elseif ($ticket->transferer_a_user_id !== null) {
        $passenger = \App\Models\User::find($ticket->transferer_a_user_id)?->name ?? '';
    } else {
        $passenger = $ticket->user?->name ?? '';
    }

    $vi       = $ticket->voyageInstance;
    $depName  = $vi->villeDepart()->name;
    $arrName  = $vi->villeArrive()->name;
    $depCode  = strtoupper(mb_substr($depName, 0, 3));
    $arrCode  = strtoupper(mb_substr($arrName, 0, 3));
    $depTime  = $vi->heure->format('H\hi');
    $rdvTime  = $ticket->heureRdv()->format('H\hi');
    $dateVoy  = $vi->date->format('d/m/Y');
    /* Force French day name regardless of app locale */
    $frDays   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $dayName  = $frDays[$vi->date->dayOfWeek];
    $prix     = number_format($ticket->prix(), 0, ',', ' ');
    $classe   = $ticket->classe()?->name ?? '—';
    $company  = strtoupper($ticket->compagnie()->name);
    $immat    = $vi->care?->immatrculation ?? '—';
    $isRound  = $ticket->type === \App\Enums\TypeTicket::AllerRetour;
    $emitted  = now()->format('d/m/Y à H\hi');
@endphp

{{-- ══════════════════════════════════════════════════
     OUTER WRAPPER — dark navy background gives "shadow"
     ══════════════════════════════════════════════════ --}}
<table width="100%" cellspacing="0" cellpadding="0"
       style="background: #0B1630; width: 100%; height: 100%;">
<tr><td style="padding: 16px 18px;">

{{-- ══════════════════════════════════════════════════
     THE TICKET CARD
     ══════════════════════════════════════════════════ --}}
<table width="100%" cellspacing="0" cellpadding="0"
       style="background: #ffffff; border-radius: 14px;">

    {{-- ── ORANGE TOP ACCENT STRIPE ── --}}
    <tr>
        <td colspan="3"
            style="height: 5px; background: #F97316; padding: 0; border-radius: 14px 14px 0 0; font-size: 0; line-height: 0;">
            &nbsp;
        </td>
    </tr>

    {{-- ═══════════════════════════════════
         HEADER ROW — dark navy
         ═══════════════════════════════════ --}}
    <tr>
        <td colspan="3" style="background: #0D1B3E; padding: 0;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Company --}}
                    <td width="38%" style="padding: 13px 24px; vertical-align: middle;">
                        <span style="color: #F97316; font-size: 14px; margin-right: 7px; vertical-align: middle;">&#9658;</span>
                        <span style="color: #ffffff; font-size: 16px; font-weight: bold;
                                     letter-spacing: 2px; text-transform: uppercase;
                                     vertical-align: middle;">{{ $company }}</span>
                    </td>
                    {{-- Center label --}}
                    <td width="24%" style="padding: 13px 8px; text-align: center; vertical-align: middle;">
                        <span style="color: rgba(255,255,255,0.35); font-size: 8px;
                                     letter-spacing: 2.5px; text-transform: uppercase;">
                            Billet de Voyage &nbsp;·&nbsp; Liptra.net
                        </span>
                    </td>
                    {{-- Badge --}}
                    <td width="38%" style="padding: 13px 24px; text-align: right; vertical-align: middle;">
                        <span style="border: 1.5px solid #F97316; color: #F97316;
                                     font-size: 8px; font-weight: bold;
                                     letter-spacing: 1.8px; padding: 4px 13px;
                                     text-transform: uppercase;">
                            CARTE D'EMBARQUEMENT
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ═══════════════════════════════════════════════════════════
         BODY — 3 columns : main-info | divider-col | qr-stub
         ═══════════════════════════════════════════════════════════ --}}
    <tr>

        {{-- ────────────────────────────
             LEFT : main content (75%)
             ──────────────────────────── --}}
        <td style="padding: 20px 26px 16px; vertical-align: top;">

            {{-- Passenger --}}
            <div class="lbl">Passager</div>
            <div style="font-size: 23px; font-weight: bold; color: #0D1B3E;
                        letter-spacing: -0.4px; text-transform: uppercase;
                        margin-bottom: 14px; line-height: 1.15;">
                {{ $passenger }}
            </div>

            {{-- Thin rule --}}
            <div style="border-top: 1px solid #E2E8F0; margin-bottom: 14px;"></div>

            {{-- ── ROUTE ── --}}
            <table width="100%" cellspacing="0" cellpadding="0"
                   style="margin-bottom: 15px;">
                <tr>
                    {{-- Departure city --}}
                    <td style="vertical-align: middle; width: 34%;">
                        <div style="font-size: 44px; font-weight: bold; color: #0D1B3E;
                                    letter-spacing: -2px; line-height: 1;">
                            {{ $depCode }}
                        </div>
                        <div style="font-size: 10px; color: #64748B;
                                    margin-top: 3px; letter-spacing: 0.2px;">
                            {{ $depName }}
                        </div>
                        <div style="font-size: 7.5px; color: #B0BEC5;
                                    margin-top: 1px; text-transform: uppercase; letter-spacing: 0.8px;">
                            Départ
                        </div>
                    </td>

                    {{-- Arrow connector --}}
                    <td style="text-align: center; vertical-align: middle; width: 32%; padding: 0 6px;">

                        {{-- Date pill --}}
                        <div style="background: #F1F5F9; border: 1px solid #E2E8F0;
                                    border-radius: 20px; padding: 3px 10px;
                                    display: inline-block; margin-bottom: 7px;">
                            <span style="font-size: 8px; color: #64748B; letter-spacing: 0.8px;
                                         text-transform: uppercase;">
                                {{ $dayName }} {{ $dateVoy }}
                            </span>
                        </div>

                        {{-- Visual line + arrow --}}
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="border-top: 2px solid #2563EB; height: 0; width: 44%;"></td>
                                <td style="text-align: center; padding: 0 3px; vertical-align: middle;">
                                    <div style="width: 22px; height: 22px; background: #F97316;
                                                border-radius: 50%; text-align: center;
                                                padding-top: 3px;">
                                        <span style="color: #fff; font-size: 13px; line-height: 1;">&#9658;</span>
                                    </div>
                                </td>
                                <td style="border-top: 2px solid #2563EB; height: 0; width: 44%;"></td>
                            </tr>
                        </table>

                        {{-- Departure time --}}
                        <div style="font-size: 14px; font-weight: bold; color: #2563EB;
                                    margin-top: 7px; letter-spacing: 0.5px;">
                            {{ $depTime }}
                        </div>
                    </td>

                    {{-- Arrival city --}}
                    <td style="vertical-align: middle; text-align: right; width: 34%;">
                        <div style="font-size: 44px; font-weight: bold; color: #0D1B3E;
                                    letter-spacing: -2px; line-height: 1; text-align: right;">
                            {{ $arrCode }}
                        </div>
                        <div style="font-size: 10px; color: #64748B; margin-top: 3px;
                                    text-align: right;">
                            {{ $arrName }}
                        </div>
                        <div style="font-size: 7.5px; color: #B0BEC5; margin-top: 1px;
                                    text-align: right; text-transform: uppercase; letter-spacing: 0.8px;">
                            Arrivée
                        </div>
                    </td>
                </tr>
            </table>

            {{-- Dashed separator --}}
            <div style="border-top: 1.5px dashed #CBD5E1; margin-bottom: 13px;"></div>

            {{-- ── DETAILS GRID ── --}}
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>

                    {{-- Siège --}}
                    <td style="vertical-align: top; padding-right: 14px;">
                        <div class="lbl">Siège</div>
                        <div style="font-size: 22px; font-weight: bold; color: #2563EB;
                                    line-height: 1.1;">
                            {{ $ticket->numero_chaise }}
                        </div>
                    </td>

                    <td style="width: 1px; background: #E2E8F0; padding: 0;">&nbsp;</td>

                    {{-- Classe --}}
                    <td style="vertical-align: top; padding: 0 14px;">
                        <div class="lbl">Classe</div>
                        <div class="val">{{ $classe }}</div>
                    </td>

                    <td style="width: 1px; background: #E2E8F0; padding: 0;">&nbsp;</td>

                    {{-- Embarquement --}}
                    <td style="vertical-align: top; padding: 0 14px;">
                        <div class="lbl">Embarquement</div>
                        <div class="val">{{ $rdvTime }}</div>
                        <div style="font-size: 8px; color: #B0BEC5; margin-top: 1px;">
                            (10 min avant départ)
                        </div>
                    </td>

                    <td style="width: 1px; background: #E2E8F0; padding: 0;">&nbsp;</td>

                    {{-- Prix --}}
                    <td style="vertical-align: top; padding: 0 14px;">
                        <div class="lbl">Prix</div>
                        <div class="val">
                            {{ $prix }}
                            <span style="font-size: 9px; font-weight: normal; color: #94A3B8;">XOF</span>
                        </div>
                    </td>

                    <td style="width: 1px; background: #E2E8F0; padding: 0;">&nbsp;</td>

                    {{-- Type --}}
                    <td style="vertical-align: top; padding: 0 14px;">
                        <div class="lbl">Type</div>
                        <div class="val">{{ $isRound ? 'Aller-Retour' : 'Aller Simple' }}</div>
                    </td>

                    <td style="width: 1px; background: #E2E8F0; padding: 0;">&nbsp;</td>

                    {{-- Immatriculation --}}
                    <td style="vertical-align: top; padding-left: 14px;">
                        <div class="lbl">Immatriculation</div>
                        <div class="val">{{ $immat }}</div>
                    </td>

                </tr>
            </table>

        </td>

        {{-- ─────────────────────────────
             DIVIDER — semi-circles cutout illusion
             ───────────────────────────── --}}
        <td style="width: 0; padding: 0; vertical-align: top;">
        </td>

        {{-- ────────────────────────────
             RIGHT : QR + stub (25%)
             ──────────────────────────── --}}
        <td style="width: 204px; vertical-align: top; background: #F8FAFC;
                   border-left: 2px dashed #CBD5E1; padding: 18px 16px 0;">

            {{-- QR Code --}}
            <div style="background: #ffffff; border: 1px solid #E2E8F0;
                        border-radius: 10px; padding: 6px;
                        text-align: center; margin-bottom: 9px;">
                <img src="{{ $qrCodePath }}" alt="QR Code"
                     style="width: 152px; height: 152px; display: block;">
            </div>

            <div style="font-size: 7.5px; color: #94A3B8; text-align: center;
                        text-transform: uppercase; letter-spacing: 1px;
                        margin-bottom: 13px;">
                Scannez pour valider
            </div>

            {{-- Siège (big) --}}
            <div style="border-top: 1px solid #E2E8F0; padding: 10px 0 8px;">
                <div style="font-size: 7.5px; color: #94A3B8; text-align: center;
                            text-transform: uppercase; letter-spacing: 1.2px;
                            margin-bottom: 2px;">
                    Siège
                </div>
                <div style="font-size: 34px; font-weight: bold; color: #2563EB;
                            text-align: center; line-height: 1;">
                    {{ $ticket->numero_chaise }}
                </div>
            </div>

            {{-- Code SMS --}}
            <div style="border-top: 1px solid #E2E8F0; padding: 9px 0 8px;">
                <div style="font-size: 7.5px; color: #94A3B8; text-align: center;
                            text-transform: uppercase; letter-spacing: 1.2px;
                            margin-bottom: 4px;">
                    Code SMS
                </div>
                <div style="font-size: 13px; font-weight: bold; color: #0D1B3E;
                            text-align: center; letter-spacing: 1.5px;">
                    {{ $ticket->code_sms }}
                </div>
            </div>

        </td>

    </tr>

    {{-- ═══════════════════════════════════
         FOOTER STRIP
         ═══════════════════════════════════ --}}
    <tr>
        <td colspan="3"
            style="background: #F1F5F9; border-top: 1px solid #E2E8F0;
                   padding: 9px 24px; border-radius: 0 0 14px 14px;">

            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Ticket number + type --}}
                    <td style="vertical-align: middle; width: 40%;">
                        <span style="font-size: 12px; font-weight: bold; color: #0D1B3E;
                                     letter-spacing: 0.8px; font-family: DejaVu Sans Mono, monospace;">
                            {{ $ticket->numero_ticket }}
                        </span>
                        <span style="font-size: 8px; color: #CBD5E1; margin: 0 8px;">|</span>
                        <span style="font-size: 8px; color: #94A3B8;">
                            {{ $isRound ? 'Aller-Retour' : 'Aller Simple' }}
                        </span>
                    </td>
                    {{-- Center: issue date --}}
                    <td style="text-align: center; vertical-align: middle; width: 30%;">
                        <span style="font-size: 8px; color: #94A3B8; letter-spacing: 0.5px;">
                            Émis le {{ $emitted }}
                        </span>
                    </td>
                    {{-- Brand --}}
                    <td style="text-align: right; vertical-align: middle; width: 30%;">
                        <span style="font-size: 10px; font-weight: bold; color: #F97316;
                                     letter-spacing: 1.5px; text-transform: uppercase;">
                            LIPTRA.NET
                        </span>
                    </td>
                </tr>
            </table>

        </td>
    </tr>

</table>
{{-- end .ticket --}}

</td></tr>
</table>
{{-- end outer --}}

</body>
</html>
