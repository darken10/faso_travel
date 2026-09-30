@extends('emails.email-layout')

@section('title', 'Nouvelle réponse de ' . $compagnie . ' — LIPTRA')
@section('preheader', $compagnie . ' a répondu à votre message.')

@section('header-bg', '#2563EB')

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
@endsection

@section('header-title', 'Vous avez une réponse')
@section('header-sub', $compagnie)

@section('body')
  <p class="greeting">Bonjour {{ $user->name ?? $user->email }},</p>

  <p class="text">
    <strong>{{ $compagnie }}</strong> a répondu à votre message :
  </p>

  {{-- Le texte vient d'un membre de la compagnie : toujours échappé, seuls les retours à la ligne sont conservés. --}}
  <div class="info-card" style="border-left:4px solid #2563EB;">
    <p class="text" style="margin:0; white-space:pre-line;">{!! nl2br(e($texte)) !!}</p>
  </div>

  <p class="text">
    Ouvrez l'application LIPTRA, rubrique <strong>Messages</strong>, pour poursuivre la conversation.
  </p>

  <p class="text" style="font-size:13px; color:#94A3B8;">
    Vous recevez cet e-mail car vous avez écrit à {{ $compagnie }} depuis LIPTRA.
    Merci de ne pas répondre directement à cet e-mail : la réponse se fait dans l'application.
  </p>
@endsection
