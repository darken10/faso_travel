@extends('layout')

@section('title', 'Payer par Orange Money')

@section('content')
<div class="max-w-lg mx-auto">

    {{-- Header --}}
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange-100 dark:bg-orange-500/20 mb-4 shadow-sm">
            <img src="{{ asset('images/choix_payement_logo/Orange-Money-logo.jpg') }}" class="w-12 h-12 object-contain rounded-xl" alt="Orange Money">
        </div>
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white">Paiement Orange Money</h1>
        <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Suivez les étapes ci-dessous pour finaliser votre achat</p>
    </div>

    {{-- Erreurs --}}
    @if ($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20">
            <div class="flex gap-2">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-red-700 dark:text-red-400">Veuillez corriger les erreurs suivantes :</p>
                    <ul class="mt-1 text-sm text-red-600 dark:text-red-400 space-y-0.5 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20">
            <div class="flex gap-2 items-center">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Récapitulatif ticket --}}
    @php
        $vi = $ticket->voyageInstance;
        $depart = $vi->villeDepart();
        $arrivee = $vi->villeArrive();
        $prix = $vi->getPrix($ticket->type);
        $heureDepart = \Carbon\Carbon::parse($vi->heure);
    @endphp
    <div class="card mb-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-surface-400 dark:text-surface-500 uppercase tracking-wider">Récapitulatif</span>
            <span class="badge badge-primary">{{ $ticket->type === \App\Enums\TypeTicket::AllerRetour ? 'Aller-retour' : 'Aller simple' }}</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-surface-900 dark:text-white truncate">{{ $depart->name }}</p>
                <p class="text-xs text-surface-400 dark:text-surface-500">{{ $heureDepart->format('H:i') }}</p>
            </div>
            <div class="flex flex-col items-center flex-shrink-0 px-2">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
                <span class="text-xs text-surface-400 dark:text-surface-500 mt-0.5">{{ $vi->date->format('d M Y') }}</span>
            </div>
            <div class="flex-1 min-w-0 text-right">
                <p class="font-semibold text-surface-900 dark:text-white truncate">{{ $arrivee->name }}</p>
                <p class="text-xs text-surface-400 dark:text-surface-500">Siège N° {{ $ticket->numero_chaise }}</p>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-surface-100 dark:border-surface-700 flex justify-between items-center">
            <span class="text-sm text-surface-500 dark:text-surface-400">Montant à payer</span>
            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">
                {{ number_format($prix, 0, ',', ' ') }} <span class="text-sm font-normal text-surface-400">XOF</span>
            </span>
        </div>
    </div>

    {{-- Étapes --}}
    <div class="card mb-4 space-y-4" x-data="{ copied: false }">
        <h2 class="text-sm font-semibold text-surface-700 dark:text-surface-300">Comment payer ?</h2>

        {{-- Étape 1 --}}
        <div class="flex gap-3">
            <div class="flex-shrink-0 w-7 h-7 rounded-full bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                <span class="text-xs font-bold text-orange-600 dark:text-orange-400">1</span>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-surface-800 dark:text-surface-200">Composez ce code sur votre téléphone</p>
                <p class="text-xs text-surface-500 dark:text-surface-400 mb-2">Vous recevrez un code OTP par SMS</p>
                <div class="flex items-center gap-2 p-3 rounded-xl bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20">
                    <code class="flex-1 text-base font-bold text-orange-700 dark:text-orange-400 tracking-wider">
                        *146*4*6*{{ $prix }}#
                    </code>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('*146*4*6*{{ $prix }}#'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 p-1.5 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-500/20 transition-colors"
                        title="Copier"
                    >
                        <svg x-show="!copied" class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                        </svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </div>
                <p x-show="copied" x-cloak class="text-xs text-green-600 dark:text-green-400 mt-1">Copié !</p>
            </div>
        </div>

        {{-- Séparateur --}}
        <div class="flex items-center gap-2">
            <div class="flex-1 h-px bg-surface-100 dark:bg-surface-700"></div>
            <span class="text-xs text-surface-400">puis</span>
            <div class="flex-1 h-px bg-surface-100 dark:bg-surface-700"></div>
        </div>

        {{-- Étape 2 --}}
        <div class="flex gap-3">
            <div class="flex-shrink-0 w-7 h-7 rounded-full bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                <span class="text-xs font-bold text-orange-600 dark:text-orange-400">2</span>
            </div>
            <p class="text-sm font-medium text-surface-800 dark:text-surface-200 self-center">Saisissez votre numéro et le code OTP reçu</p>
        </div>
    </div>

    {{-- Formulaire --}}
    <div class="card" x-data="{ paying: false }">
        <form
            action="{{ route('payement.orange.payer', $ticket) }}"
            method="POST"
            @submit="paying = true"
        >
            @csrf

            {{-- Numéro --}}
            <div class="mb-4">
                <label for="numero" class="input-label">
                    Numéro Orange Money
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </div>
                    <input
                        id="numero"
                        name="numero"
                        type="tel"
                        value="{{ old('numero') }}"
                        placeholder="07 XX XX XX"
                        class="input pl-9 @error('numero') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        required
                    />
                </div>
                @error('numero')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- OTP --}}
            <div class="mb-6">
                <label for="otp" class="input-label">
                    Code OTP reçu par SMS
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <input
                        id="otp"
                        name="otp"
                        type="tel"
                        value="{{ old('otp') }}"
                        placeholder="6 chiffres"
                        maxlength="6"
                        class="input pl-9 tracking-[0.4em] text-center font-mono text-lg @error('otp') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        required
                    />
                </div>
                @error('otp')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-surface-400 dark:text-surface-500">Le code OTP contient exactement 6 chiffres</p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <a href="{{ route('ticket.goto-payment', $ticket) }}" class="btn btn-ghost flex-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </a>
                <button
                    type="submit"
                    :disabled="paying"
                    class="btn btn-primary flex-1 bg-orange-500 hover:bg-orange-600 active:bg-orange-700 focus:ring-orange-300"
                >
                    <svg x-show="!paying" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <svg x-show="paying" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-show="!paying">Confirmer le paiement</span>
                    <span x-show="paying" x-cloak>Traitement en cours…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Sécurité --}}
    <div class="flex items-center justify-center gap-2 mt-4 text-xs text-surface-400 dark:text-surface-500">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
        </svg>
        Paiement sécurisé — Vos données sont protégées
    </div>

</div>
@endsection
