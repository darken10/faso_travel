@extends('emails.email-layout')

@php
    use App\Enums\RappelDepart;

    $villeDepart = $instance?->villeDepart()?->name ?? '—';
    $villeArrivee = $instance?->villeArrive()?->name ?? '—';
    $gare = $instance?->gareDepart()?->name;
    $heure = $depart?->format('H\hi') ?? '--h--';
    $date = $depart?->translatedFormat('l j F Y') ?? '—';
    $couleur = $palier->couleur();

    $accroche = match ($palier) {
        RappelDepart::Veille       => "Votre voyage a lieu demain. Voici un rappel des informations utiles.",
        RappelDepart::AvantDepart  => "Votre départ approche. Pensez à rejoindre la gare dès maintenant.",
        RappelDepart::Embarquement => "L'embarquement commence. Présentez votre QR code à l'agent de contrôle.",
    };

    $conseil = match ($palier) {
        RappelDepart::Veille       => "Préparez votre pièce d'identité et votre billet. Nous vous conseillons d'arriver 30 minutes avant le départ.",
        RappelDepart::AvantDepart  => "Munissez-vous de votre billet et de votre pièce d'identité.",
        RappelDepart::Embarquement => "Votre billet est disponible dans l'application, rubrique « Mes tickets ».",
    };
@endphp

@section('title', $palier->label() . ' — LIPTRA')
@section('preheader', $villeDepart . ' vers ' . $villeArrivee . ' — départ le ' . $date . ' à ' . $heure)

@section('header-bg', $couleur)

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"/>
    <polyline points="12 6 12 12 16 14"/>
  </svg>
@endsection

@section('header-title', $palier->label())
@section('header-sub', 'Départ à ' . $heure)

@section('body')
  <p class="greeting">Bonjour {{ $passager }},</p>

  <p class="text">{{ $accroche }}</p>

  {{-- Trajet --}}
  <div class="route-badge" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px;">
    <div class="route-city" style="color:{{ $couleur }};">{{ $villeDepart }}</div>
    <div class="route-arrow">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round">
        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
      </svg>
    </div>
    <div class="route-city" style="color:{{ $couleur }};">{{ $villeArrivee }}</div>
  </div>

  {{-- Détails --}}
  <div class="info-card">
    <div class="info-row">
      <span class="info-label">Date</span>
      <span class="info-value">{{ ucfirst($date) }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Heure de départ</span>
      <span class="info-value">{{ $heure }}</span>
    </div>
    @if($gare)
      <div class="info-row">
        <span class="info-label">Gare</span>
        <span class="info-value">{{ $gare }}</span>
      </div>
    @endif
    @if($ticket->numero_chaise)
      <div class="info-row">
        <span class="info-label">Siège</span>
        <span class="info-value">{{ $ticket->numero_chaise }}</span>
      </div>
    @endif
    <div class="info-row">
      <span class="info-label">N° de billet</span>
      <span class="info-value mono">{{ $ticket->numero_ticket }}</span>
    </div>
  </div>

  <p class="text">{{ $conseil }}</p>
@endsection
