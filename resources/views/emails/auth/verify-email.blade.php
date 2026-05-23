@extends('emails.email-layout')

@section('title', 'Vérifiez votre adresse e-mail — LIPTRA')
@section('preheader', 'Confirmez votre adresse e-mail pour activer votre compte LIPTRA et accéder à tous les services.')

@section('header-bg', '#0891B2')

@section('header-icon')
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
    <polyline points="22,6 12,13 2,6"/>
  </svg>
@endsection

@section('header-title', 'Vérifiez votre adresse e-mail')
@section('header-sub', 'Une dernière étape pour activer votre compte')

@section('body')
  <p class="greeting">Bonjour {{ $user->name ?? $user->email }},</p>

  <p class="text">
    Bienvenue sur <strong>LIPTRA</strong> ! Pour finaliser la création de votre compte et accéder
    à tous nos services de réservation, veuillez confirmer votre adresse e-mail.
  </p>

  <div class="btn-wrap">
    <a href="{{ $verificationUrl }}" class="btn" style="background:#0891B2; color:#ffffff;">
      Vérifier mon adresse e-mail
    </a>
  </div>

  <div class="info-card">
    <div class="info-row">
      <span class="info-label">Adresse e-mail</span>
      <span class="info-value">{{ $user->email }}</span>
    </div>
    <div class="info-row">
      <span class="info-label">Date d'inscription</span>
      <span class="info-value">{{ now()->setTimezone('Africa/Ouagadougou')->format('d/m/Y à H\hi') }}</span>
    </div>
  </div>

  <div class="alert alert-info">
    <strong>Pourquoi cette vérification ?</strong><br>
    La vérification de votre e-mail nous permet de sécuriser votre compte et de vous envoyer
    les confirmations de réservation, billets de voyage et informations importantes.
  </div>

  <div class="alert alert-warning">
    <strong>⚠ Vous n'avez pas créé de compte LIPTRA ?</strong><br>
    Ignorez cet e-mail. Aucune action n'est requise et aucun compte ne sera activé.
  </div>

  <p class="text" style="font-size:13px; color:#94A3B8;">
    Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
    <a href="{{ $verificationUrl }}" style="color:#0891B2; word-break:break-all;">{{ $verificationUrl }}</a>
  </p>
@endsection
