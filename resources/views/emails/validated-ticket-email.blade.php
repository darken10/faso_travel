@extends('emails.email-layout')

@section('title', 'Embarquement confirmé — LIPTRA')
@section('preheader', 'Votre ticket ' . $ticket->numero_ticket . ' a été validé avec succès. Bon voyage !')

@section('header-bg', '#059669')

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
@endsection

@section('header-title', 'Embarquement confirmé !')
@section('header-sub', 'Votre ticket a été validé avec succès')

@section('body')
  @php
    $passengerName = $ticket->autre_personne_id
      ? ($ticket->autre_personne->nom ?? 'Passager')
      : ($ticket->user->name ?? 'Passager');

    $instance  = $ticket->voyageInstance;
    $depart    = $instance?->villeDepart()?->name ?? '—';
    $arrivee   = $instance?->villeArrive()?->name ?? '—';
    $dateVoyage = $instance?->date?->format('d/m/Y') ?? '—';
    $heureVoyage = $instance?->heure?->format('H\hi') ?? '—';
    $validatedAt = $ticket->valider_at
      ? \Carbon\Carbon::parse($ticket->valider_at)->setTimezone('Africa/Ouagadougou')->format('d/m/Y à H\hi')
      : now()->format('d/m/Y à H\hi');
  @endphp

  <p class="greeting">Bonjour {{ $passengerName }},</p>

  <p class="text">
    Votre embarquement a été <strong style="color:#059669;">confirmé</strong> par l'agent de contrôle.
    Nous vous souhaitons un excellent voyage !
  </p>

  {{-- Route --}}
  <div class="route-badge" style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px;">
    <div class="route-city" style="color:#065F46;">{{ $depart }}</div>
    <div class="route-arrow">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6EE7B7" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </div>
    <div class="route-city" style="color:#065F46;">{{ $arrivee }}</div>
  </div>

  {{-- Ticket details --}}
  <div class="info-card">
    <div class="info-row">
      <span class="info-label">N° Ticket</span>
      <span class="info-value mono" style="color:#059669;">{{ $ticket->numero_ticket }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Date de voyage</span>
      <span class="info-value">{{ $dateVoyage }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Heure de départ</span>
      <span class="info-value">{{ $heureVoyage }}</span>
    </div>
    @if($ticket->numero_chaise)
    <div class="info-row">
      <span class="info-label">Siège</span>
      <span class="info-value">Siège n°{{ $ticket->numero_chaise }}</span>
    </div>
    @endif
    <div class="info-row">
      <span class="info-label">Validé le</span>
      <span class="info-value">{{ $validatedAt }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Statut</span>
      <span class="info-value" style="color:#059669; display:flex; align-items:center; gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#059669;display:inline-block;"></span>
        Embarqué
      </span>
    </div>
  </div>

  {{-- QR code reference --}}
  @if(isset($qrImage))
  <div class="qr-section">
    <img src="{{ $qrImage }}" alt="QR Code du ticket" style="width:140px;height:140px;border:4px solid #ECFDF5;border-radius:12px;">
    <div class="qr-caption">QR code de référence — {{ $ticket->numero_ticket }}</div>
  </div>
  @endif

  <div class="alert alert-info">
    <strong>Bon à savoir :</strong> Conservez ce mail comme justificatif de votre voyage.
    En cas de problème, contactez notre support en précisant votre numéro de ticket.
  </div>
@endsection
