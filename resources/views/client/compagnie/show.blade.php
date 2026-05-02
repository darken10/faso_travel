@extends('layout')

@section('title', $compagnie->name)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- ── Header compagnie ───────────────────────────────────────────── --}}
    <div class="card mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            @if($compagnie->logo)
                <div class="w-20 h-20 rounded-2xl bg-surface-50 dark:bg-surface-800 flex items-center justify-center flex-shrink-0 overflow-hidden border border-surface-100 dark:border-surface-700">
                    <img src="{{ asset('storage/' . $compagnie->logo) }}" alt="{{ $compagnie->name }}" class="h-16 object-contain">
                </div>
            @else
                <div class="w-20 h-20 rounded-2xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-10 h-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>
            @endif

            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $compagnie->name }}</h1>
                @if($compagnie->slogant)
                    <p class="text-sm italic text-surface-500 dark:text-surface-400 mt-0.5">« {{ $compagnie->slogant }} »</p>
                @endif
                <div class="flex items-center gap-1 mt-2">
                    @php $note = round($compagnie->avis_avg_note ?? 3); @endphp
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= $note ? 'text-accent-500' : 'text-surface-200 dark:text-surface-600' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                        </svg>
                    @endfor
                    <span class="ml-1 text-sm text-surface-500 dark:text-surface-400">({{ $compagnie->avis_count ?? 0 }} avis)</span>
                </div>
            </div>
        </div>

        @if($compagnie->description)
            <div class="mt-4 pt-4 border-t border-surface-100 dark:border-surface-700">
                <p class="text-sm text-surface-600 dark:text-surface-300 leading-relaxed">{{ $compagnie->description }}</p>
            </div>
        @endif
    </div>

    {{-- ── Instances de voyage ─────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
            Voyages à venir
            @if($voyageInstances->count())
                <span class="ml-2 text-sm font-normal text-surface-500">({{ $voyageInstances->count() }})</span>
            @endif
        </h2>
        <a href="{{ route('voyage.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
            Voir tous les voyages →
        </a>
    </div>

    @if($voyageInstances->count())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ($voyageInstances as $voyageInstance)
                @php
                    $prix        = $voyageInstance->getPrix(\App\Enums\TypeTicket::AllerSimple);
                    $prixRetour  = $voyageInstance->getPrix(\App\Enums\TypeTicket::AllerRetour);
                    $depart      = $voyageInstance->villeDepart();
                    $arrivee     = $voyageInstance->villeArrive();
                    $gareD       = $voyageInstance->gareDepart();
                    $gareA       = $voyageInstance->gareArrive();
                    $heureDepart = \Carbon\Carbon::parse($voyageInstance->heure);
                    $temps       = $voyageInstance->voyage->temps ? \Carbon\Carbon::parse($voyageInstance->voyage->temps) : null;
                    $heureArrivee = $temps
                        ? $heureDepart->copy()->addHours($temps->hour)->addMinutes($temps->minute)
                        : $heureDepart->copy()->addHours(2);
                    $durationH   = $temps ? $temps->hour : 2;
                    $durationM   = $temps ? $temps->minute : 0;
                    $seatsLeft   = $voyageInstance->availableSeats();
                @endphp

                <div class="card group hover:shadow-elevated hover:-translate-y-0.5 transition-all duration-300">

                    {{-- Compagnie + Date --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            @if($compagnie->logo)
                                <img src="{{ asset('storage/'.$compagnie->logo) }}" alt="{{ $compagnie->name }}"
                                     class="w-8 h-8 rounded-lg object-contain bg-surface-50 border border-surface-100 dark:border-surface-700">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                                    </svg>
                                </div>
                            @endif
                            <span class="font-semibold text-surface-900 dark:text-white">{{ $compagnie->name }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-surface-500 dark:text-surface-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                            {{ $voyageInstance->date->format('d M Y') }}
                        </div>
                    </div>

                    {{-- Route --}}
                    <div class="flex items-center gap-3 mb-5">
                        <div class="flex-1 min-w-0">
                            <p class="text-xl font-bold text-surface-900 dark:text-white leading-tight">{{ $heureDepart->format('H:i') }}</p>
                            <p class="font-medium text-surface-700 dark:text-surface-300 truncate">{{ $depart->name }}</p>
                            @if($gareD)
                                <p class="text-xs text-surface-400 dark:text-surface-500 truncate">{{ $gareD->name }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col items-center flex-shrink-0 px-1">
                            <span class="text-xs text-surface-400 dark:text-surface-500 mb-1">
                                {{ ($durationH > 0 || $durationM > 0) ? ($durationH > 0 ? $durationH.'h' : '') . ($durationM > 0 ? $durationM.'min' : '') : '—' }}
                            </span>
                            <div class="flex items-center gap-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-primary-500"></div>
                                <div class="w-12 h-px bg-surface-300 dark:bg-surface-600"></div>
                                <svg class="w-3.5 h-3.5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                                <div class="w-12 h-px bg-surface-300 dark:bg-surface-600"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-accent-500"></div>
                            </div>
                            <span class="text-xs text-surface-400 dark:text-surface-500 mt-1">Direct</span>
                        </div>

                        <div class="flex-1 min-w-0 text-right">
                            <p class="text-xl font-bold text-surface-900 dark:text-white leading-tight">{{ $heureArrivee->format('H:i') }}</p>
                            <p class="font-medium text-surface-700 dark:text-surface-300 truncate">{{ $arrivee->name }}</p>
                            @if($gareA)
                                <p class="text-xs text-surface-400 dark:text-surface-500 truncate">{{ $gareA->name }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Places --}}
                    <div class="mb-4">
                        @if($seatsLeft <= 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                Complet
                            </span>
                        @elseif($seatsLeft <= 5)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $seatsLeft }} place{{ $seatsLeft > 1 ? 's' : '' }} restante{{ $seatsLeft > 1 ? 's' : '' }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ $seatsLeft }} places disponibles
                            </span>
                        @endif
                    </div>

                    {{-- Prix + CTA --}}
                    <div class="flex items-end justify-between pt-4 border-t border-surface-100 dark:border-surface-700">
                        <div>
                            <p class="text-xs text-surface-400 dark:text-surface-500 mb-0.5">Aller simple</p>
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($prix, 0, ',', ' ') }}
                                <span class="text-sm font-normal text-surface-500">XOF</span>
                            </p>
                            @if($prixRetour > 0)
                                <p class="text-xs text-surface-400 dark:text-surface-500">
                                    Aller-retour : {{ number_format($prixRetour, 0, ',', ' ') }} XOF
                                </p>
                            @endif
                        </div>

                        @if($seatsLeft > 0)
                            <a href="{{ route('voyage.instance.show', $voyageInstance->id) }}" class="btn btn-primary btn-sm">
                                Réserver
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        @else
                            <span class="btn btn-ghost btn-sm opacity-50 cursor-not-allowed">Complet</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="card text-center py-14">
            <div class="w-16 h-16 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-surface-900 dark:text-white mb-1">Aucun voyage à venir</h3>
            <p class="text-sm text-surface-500 dark:text-surface-400 mb-5">
                {{ $compagnie->name }} n'a pas de voyages programmés pour le moment.
            </p>
            <a href="{{ route('voyage.index') }}" class="btn btn-ghost btn-sm">
                Voir tous les voyages disponibles
            </a>
        </div>
    @endif

</div>
@endsection
