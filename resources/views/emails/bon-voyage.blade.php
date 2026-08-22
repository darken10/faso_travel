@extends('emails.email-layout')

@php
    $villeDepart = $instance?->villeDepart()?->name ?? '—';
    $villeArrivee = $instance?->villeArrive()?->name ?? '—';
    $compagnie = $instance?->voyage?->compagnie?->name;
@endphp

@section('title', 'Bon voyage — LIPTRA')
@section('preheader', 'Vous êtes à bord. Nous vous souhaitons un excellent trajet vers ' . $villeArrivee . '.')

@section('header-bg', '#0EA5E9')

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 17h14M6 17V9a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8"/>
    <circle cx="8.5" cy="19" r="1.5"/><circle cx="15.5" cy="19" r="1.5"/>
  </svg>
@endsection

@section('header-title', 'Bon voyage !')
@section('header-sub', 'Vous êtes à bord')

@section('body')
  <p class="greeting">Bonjour {{ $passager }},</p>

  <p class="text">
    Votre embarquement est confirmé et votre trajet a commencé.
    @if($compagnie)
      Toute l'équipe {{ $compagnie }} vous souhaite un excellent voyage.
    @else
      Nous vous souhaitons un excellent voyage.
    @endif
  </p>

  {{-- Trajet --}}
  <div class="route-badge" style="background:#F0F9FF; border:1px solid #BAE6FD; border-radius:12px;">
    <div class="route-city" style="color:#0369A1;">{{ $villeDepart }}</div>
    <div class="route-arrow">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7DD3FC" stroke-width="2" stroke-linecap="round">
        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
      </svg>
    </div>
    <div class="route-city" style="color:#0369A1;">{{ $villeArrivee }}</div>
  </div>

  <div class="info-card">
    <div class="info-row">
      <span class="info-label">N° de billet</span>
      <span class="info-value mono">{{ $ticket->numero_ticket }}</span>
    </div>
    @if($ticket->numero_chaise)
      <div class="info-row">
        <span class="info-label">Siège</span>
        <span class="info-value">{{ $ticket->numero_chaise }}</span>
      </div>
    @endif
    @if($arrivee)
      <div class="info-row">
        <span class="info-label">Arrivée estimée</span>
        <span class="info-value">{{ $arrivee->format('H\hi') }}</span>
      </div>
    @endif
  </div>

  <p class="text">
    À l'arrivée, n'hésitez pas à partager votre avis sur la compagnie depuis
    l'application : vos retours aident les autres voyageurs.
  </p>
@endsection
