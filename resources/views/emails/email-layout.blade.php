<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@yield('title', 'LIPTRA')</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', Arial, Helvetica, sans-serif; background-color: #EEF2F7; -webkit-text-size-adjust: 100%; }
    a { text-decoration: none; }
    img { border: 0; display: block; }
    table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }

    .preheader { display: none !important; max-height: 0; overflow: hidden; mso-hide: all; }

    .wrapper { width: 100%; background-color: #EEF2F7; padding: 32px 16px; }
    .card    { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }

    /* ── Header ── */
    .email-header { padding: 40px 40px 32px; text-align: center; }
    .brand-name   { font-size: 13px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; opacity: .85; margin-bottom: 20px; }
    .header-icon  { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .header-title { font-size: 22px; font-weight: 700; line-height: 1.3; }
    .header-sub   { font-size: 14px; margin-top: 6px; opacity: .8; }

    /* ── Divider ── */
    .divider { height: 1px; background: #E8ECF0; margin: 0 40px; }

    /* ── Content ── */
    .email-body { padding: 32px 40px; }
    .greeting   { font-size: 16px; color: #1E293B; font-weight: 600; margin-bottom: 12px; }
    .text       { font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 16px; }

    /* ── Info card (ticket details) ── */
    .info-card    { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; margin: 24px 0; }
    .info-row     { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid #E2E8F0; }
    .info-row:last-child { border-bottom: none; }
    .info-label   { font-size: 12px; color: #94A3B8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .info-value   { font-size: 14px; color: #1E293B; font-weight: 600; text-align: right; }
    .info-value.mono { font-family: 'Courier New', monospace; font-size: 15px; letter-spacing: 1px; }

    /* ── Route badge ── */
    .route-badge  { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; margin: 20px 0; }
    .route-city   { font-size: 18px; font-weight: 700; color: #1E293B; }
    .route-arrow  { font-size: 20px; color: #94A3B8; }

    /* ── CTA Button ── */
    .btn-wrap   { text-align: center; margin: 28px 0; }
    .btn        { display: inline-block; padding: 15px 36px; border-radius: 10px; font-size: 15px; font-weight: 700; letter-spacing: .3px; }

    /* ── Alert box ── */
    .alert { border-radius: 10px; padding: 16px 20px; margin: 20px 0; font-size: 13px; line-height: 1.6; }
    .alert-warning { background: #FFFBEB; border: 1px solid #FCD34D; color: #92400E; }
    .alert-info    { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; }

    /* ── QR section ── */
    .qr-section { text-align: center; padding: 20px 0; }
    .qr-section img { margin: 0 auto; width: 140px; height: 140px; border-radius: 8px; }
    .qr-caption { font-size: 11px; color: #94A3B8; margin-top: 8px; }

    /* ── Footer ── */
    .email-footer { background: #1E293B; padding: 28px 40px; text-align: center; }
    .footer-brand { font-size: 18px; font-weight: 800; color: #ffffff; letter-spacing: 1px; margin-bottom: 8px; }
    .footer-text  { font-size: 12px; color: #94A3B8; line-height: 1.8; }
    .footer-text a { color: #CBD5E1; }
    .footer-copy  { font-size: 11px; color: #64748B; margin-top: 16px; }

    @media (max-width: 600px) {
      .email-header, .email-body { padding: 24px 24px; }
      .divider { margin: 0 24px; }
      .header-title { font-size: 19px; }
      .route-badge { flex-direction: column; gap: 4px; }
    }
  </style>
</head>
<body>

{{-- Preheader hidden preview text --}}
<div class="preheader">@yield('preheader', 'LIPTRA — Votre plateforme de voyages')</div>

<div class="wrapper">
  <div class="card">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="email-header" style="background: @yield('header-bg', '#1D4ED8'); color: #ffffff;">
      <div class="brand-name" style="color: rgba(255,255,255,.75);">LIPTRA</div>
      <div class="header-icon" style="background: rgba(255,255,255,.2);">
        @yield('header-icon')
      </div>
      <div class="header-title">@yield('header-title')</div>
      @hasSection('header-sub')
        <div class="header-sub">@yield('header-sub')</div>
      @endif
    </div>

    <div class="divider"></div>

    {{-- ── Body ──────────────────────────────────────────────────────────── --}}
    <div class="email-body">
      @yield('body')
    </div>

    {{-- ── Footer ─────────────────────────────────────────────────────────── --}}
    <div class="email-footer">
      <div class="footer-brand">LIPTRA</div>
      <div class="footer-text">
        La plateforme de réservation et de gestion de voyages<br>
        <a href="mailto:support@liptra.net">support@liptra.net</a>
      </div>
      <div class="footer-copy">
        &copy; {{ date('Y') }} LIPTRA. Tous droits réservés.
      </div>
    </div>

  </div>
</div>
</body>
</html>
