@extends('layout')

@section('title', 'Mon profil')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl overflow-hidden shadow-card border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800">

        {{-- Bannière --}}
        <div class="h-28 bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 relative">
            <div class="absolute inset-0 opacity-20"
                 style="background-image: url(\"data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='white' fill-opacity='1' fill-rule='evenodd'%3E%3Ccircle cx='20' cy='20' r='1'/%3E%3C/g%3E%3C/svg%3E\");"></div>
        </div>

        <div class="px-6 pb-6">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-12">

                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    <img src="{{ auth()->user()->profile_photo_url }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-24 h-24 rounded-2xl object-cover ring-4 ring-white dark:ring-surface-800 shadow-lg">
                </div>

                {{-- Infos + actions --}}
                <div class="flex-1 min-w-0 sm:pb-1">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <h1 class="text-xl font-bold text-surface-900 dark:text-white truncate">
                                {{ auth()->user()->name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1">
                                @if(auth()->user()->numero)
                                    <span class="inline-flex items-center gap-1.5 text-sm text-surface-500 dark:text-surface-400">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        +226 {{ auth()->user()->numero }}
                                    </span>
                                @endif
                                @if(auth()->user()->email)
                                    <span class="inline-flex items-center gap-1.5 text-sm text-surface-500 dark:text-surface-400">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ auth()->user()->email }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Badge rôle --}}
                        <span class="inline-flex self-start items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-xs font-semibold text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Voyageur
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Informations personnelles ──────────────────────────────────── --}}
    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-card border border-surface-200 dark:border-surface-700 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-100 dark:border-surface-700">
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900 dark:text-white">Informations personnelles</h2>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Nom, email et photo de profil</p>
                </div>
            </div>
            <div class="p-6">
                @livewire('profile.update-profile-information-form')
            </div>
        </div>
    @endif

    {{-- ── Sécurité ────────────────────────────────────────────────────── --}}
    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-card border border-surface-200 dark:border-surface-700 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-100 dark:border-surface-700">
                <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900 dark:text-white">Sécurité du compte</h2>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Modifier votre mot de passe</p>
                </div>
            </div>
            <div class="p-6">
                @livewire('profile.update-password-form')
            </div>
        </div>
    @endif

    {{-- ── Sessions ─────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-card border border-surface-200 dark:border-surface-700 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-100 dark:border-surface-700">
            <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900 dark:text-white">Sessions actives</h2>
                <p class="text-xs text-surface-500 dark:text-surface-400">Gérer les connexions sur d'autres appareils</p>
            </div>
        </div>
        <div class="p-6">
            @livewire('profile.logout-other-browser-sessions-form')
        </div>
    </div>

    {{-- ── Double authentification ────────────────────────────────────── --}}
    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-card border border-surface-200 dark:border-surface-700 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-100 dark:border-surface-700">
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900 dark:text-white">Double authentification</h2>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Sécurité supplémentaire via authenticator</p>
                </div>
            </div>
            <div class="p-6">
                @livewire('profile.two-factor-authentication-form')
            </div>
        </div>
    @endif

    {{-- ── Zone de danger ──────────────────────────────────────────────── --}}
    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-card border border-red-100 dark:border-red-900/40 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-red-100 dark:border-red-900/40">
                <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-red-600 dark:text-red-400">Zone de danger</h2>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Suppression définitive du compte</p>
                </div>
            </div>
            <div class="p-6">
                @livewire('profile.delete-user-form')
            </div>
        </div>
    @endif

</div>
@endsection
