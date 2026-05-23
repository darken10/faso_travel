@extends('emails.email-layout')

@section('title', 'Réinitialisation du mot de passe — LIPTRA')
@section('preheader', 'Vous avez demandé à réinitialiser votre mot de passe LIPTRA. Le lien expire dans 60 minutes.')

@section('header-bg', '#7C3AED')

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
  </svg>
@endsection

@section('header-title', 'Réinitialisation du mot de passe')
@section('header-sub', 'Une demande a été effectuée sur votre compte')

@section('body')
  <p class="greeting">Bonjour {{ $user->name ?? $user->email }},</p>

  <p class="text">
    Nous avons reçu une demande de réinitialisation du mot de passe associé à votre compte LIPTRA.
    Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.
  </p>

  <div class="btn-wrap">
    <a href="{{ $resetUrl }}" class="btn" style="background:#7C3AED; color:#ffffff;">
      Réinitialiser mon mot de passe
    </a>
  </div>

  <div class="info-card">
    <div class="info-row">
      <span class="info-label">Compte</span>
      <span class="info-value">{{ $user->email }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Expiration du lien</span>
      <span class="info-value">{{ $expiresInMinutes }} minutes</span>
    </div>
    <div class="info-row">
      <span class="info-label">Demande effectuée le</span>
      <span class="info-value">{{ now()->setTimezone('Africa/Ouagadougou')->format('d/m/Y à H\hi') }}</span>
    </div>
  </div>

  <div class="alert alert-warning">
    <strong>⚠ Vous n'avez pas fait cette demande ?</strong><br>
    Ignorez simplement cet e-mail — votre mot de passe ne sera pas modifié.
    Si vous pensez que votre compte est compromis, contactez notre support immédiatement.
  </div>

  <p class="text" style="font-size:13px; color:#94A3B8;">
    Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
    <a href="{{ $resetUrl }}" style="color:#7C3AED; word-break:break-all;">{{ $resetUrl }}</a>
  </p>
@endsection
