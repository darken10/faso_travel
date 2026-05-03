<div>
    {{-- Hero --}}
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-primary-50 dark:bg-primary-900/20 rounded-full mb-4">
            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Recherche de voyages</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-surface-900 dark:text-white mb-3">
            Trouvez et réservez votre voyage
        </h1>
        <p class="text-surface-500 dark:text-surface-400 max-w-2xl mx-auto">
            Comparez les horaires et les prix de toutes les compagnies de transport. Payez en ligne en toute sécurité.
        </p>
    </div>

    {{-- Search Form --}}
    <div class="card max-w-5xl mx-auto mb-10">

        {{-- Ticket type toggle --}}
        <div class="flex gap-2 mb-6">
            <button
                wire:click="$set('typeTicket', 'aller-simple')"
                class="{{ $typeTicket === 'aller-simple' ? 'btn btn-primary' : 'btn btn-ghost' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
                Aller simple
            </button>
            <button
                wire:click="$set('typeTicket', 'aller-retour')"
                class="{{ $typeTicket === 'aller-retour' ? 'btn btn-primary' : 'btn btn-ghost' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5" />
                </svg>
                Aller-retour
            </button>
        </div>

        {{-- Search fields --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

            {{-- Départ --}}
            <div>
                <label class="input-label">Ville de départ</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </div>
                    <input
                        wire:model.lazy="villeDepart"
                        type="text"
                        placeholder="Ex: Ouagadougou"
                        class="input pl-9"
                    />
                </div>
            </div>

            {{-- Destination --}}
            <div>
                <label class="input-label">Destination</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 9.776M21 3l-1.664 9.776M4.664 12.776A2 2 0 006.648 15h10.704a2 2 0 001.984-2.224L18 5H6L4.664 12.776z" />
                        </svg>
                    </div>
                    <input
                        wire:model.lazy="villeArrivee"
                        type="text"
                        placeholder="Ex: Bobo-Dioulasso"
                        class="input pl-9"
                    />
                </div>
            </div>

            {{-- Date --}}
            <div>
                <label class="input-label">Date de départ</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <input wire:model="date" type="date" class="input pl-9" min="{{ now()->toDateString() }}" />
                </div>
            </div>

            {{-- Compagnie --}}
            <div>
                <label class="input-label">Compagnie</label>
                <select wire:model="compagnie" class="input">
                    <option value="">Toutes les compagnies</option>
                    @foreach($allCompagnies as $comp)
                        <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between" x-data="{ searching: false, resetting: false }">
            <button
                class="btn btn-ghost text-sm"
                :disabled="resetting"
                @click.prevent="resetting = true; $wire.resetFilters().finally(() => { resetting = false })"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Réinitialiser
            </button>

            <button
                class="btn btn-primary"
                :disabled="searching"
                @click.prevent="searching = true; $wire.search().finally(() => { searching = false })"
            >
                <svg x-show="!searching" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <svg x-show="searching" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Rechercher
            </button>
        </div>
    </div>

    {{-- Results header --}}
    <div class="max-w-6xl mx-auto mb-4 flex items-center justify-between px-1" wire:loading.class="opacity-50" wire:target="search,resetFilters">
        @if(count($voyageInstances) > 0)
            <p class="text-sm text-surface-500 dark:text-surface-400">
                <span class="font-semibold text-surface-800 dark:text-white">{{ count($voyageInstances) }}</span>
                {{ Str::plural('voyage', count($voyageInstances)) }} disponible{{ count($voyageInstances) > 1 ? 's' : '' }}
                @if($typeTicket === 'aller-retour')
                    — prix aller-retour
                @endif
            </p>
        @else
            <div></div>
        @endif
    </div>

    {{-- Results --}}
    <div wire:loading.class="opacity-40 pointer-events-none" wire:target="search,resetFilters" class="transition-opacity duration-200">
        @if(count($voyageInstances) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-6xl mx-auto">
                @foreach ($voyageInstances as $voyageInstance)
                    @php
                        $prix = $voyageInstance->getPrix($ticketType);
                        $prixRetour = $voyageInstance->getPrix(\App\Enums\TypeTicket::AllerRetour);
                        $depart = $voyageInstance->villeDepart();
                        $arrivee = $voyageInstance->villeArrive();
                        $gareD = $voyageInstance->gareDepart();
                        $gareA = $voyageInstance->gareArrive();
                        $heureDepart = \Carbon\Carbon::parse($voyageInstance->heure);
                        $temps = $voyageInstance->voyage->temps ? \Carbon\Carbon::parse($voyageInstance->voyage->temps) : null;
                        $heureArrivee = $temps
                            ? $heureDepart->copy()->addHours($temps->hour)->addMinutes($temps->minute)
                            : $heureDepart->copy()->addHours(2);
                        $durationH = $temps ? $temps->hour : 2;
                        $durationM = $temps ? $temps->minute : 0;
                        $seatsLeft = $voyageInstance->availableSeats();
                    @endphp
                    <div class="card group hover:shadow-elevated hover:-translate-y-0.5 transition-all duration-300">

                        {{-- Company + Date --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-surface-900 dark:text-white">{{ $voyageInstance->voyage->compagnie->name }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-surface-500 dark:text-surface-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                {{ $voyageInstance->date->format('d M Y') }}
                            </div>
                        </div>

                        {{-- Route --}}
                        <div class="flex items-center gap-3 mb-5">
                            {{-- Départ --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-xl font-bold text-surface-900 dark:text-white leading-tight">{{ $heureDepart->format('H:i') }}</p>
                                <p class="font-medium text-surface-700 dark:text-surface-300 truncate">{{ $depart->name }}</p>
                                @if($gareD)
                                    <p class="text-xs text-surface-400 dark:text-surface-500 truncate">{{ $gareD->name }}</p>
                                @endif
                            </div>

                            {{-- Duration connector --}}
                            <div class="flex flex-col items-center flex-shrink-0 px-1">
                                <span class="text-xs text-surface-400 dark:text-surface-500 mb-1">
                                    {{ ($durationH > 0 || $durationM > 0) ? ($durationH > 0 ? $durationH.'h' : '') . ($durationM > 0 ? $durationM.'min' : '') : '—' }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary-500"></div>
                                    <div class="w-12 h-px bg-surface-300 dark:bg-surface-600"></div>
                                    <svg class="w-3.5 h-3.5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                    <div class="w-12 h-px bg-surface-300 dark:bg-surface-600"></div>
                                    <div class="w-1.5 h-1.5 rounded-full bg-accent-500"></div>
                                </div>
                                <span class="text-xs text-surface-400 dark:text-surface-500 mt-1">Direct</span>
                            </div>

                            {{-- Arrivée --}}
                            <div class="flex-1 min-w-0 text-right">
                                <p class="text-xl font-bold text-surface-900 dark:text-white leading-tight">{{ $heureArrivee->format('H:i') }}</p>
                                <p class="font-medium text-surface-700 dark:text-surface-300 truncate">{{ $arrivee->name }}</p>
                                @if($gareA)
                                    <p class="text-xs text-surface-400 dark:text-surface-500 truncate">{{ $gareA->name }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Seats badge --}}
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

                        {{-- Footer: price + CTA --}}
                        <div class="flex items-end justify-between pt-4 border-t border-surface-100 dark:border-surface-700">
                            <div>
                                <p class="text-xs text-surface-400 dark:text-surface-500 mb-0.5">
                                    {{ $typeTicket === 'aller-retour' ? 'Aller-retour' : 'Aller simple' }}
                                </p>
                                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ number_format($prix, 0, ',', ' ') }}
                                    <span class="text-sm font-normal text-surface-500">XOF</span>
                                </p>
                                @if($typeTicket === 'aller-simple' && $prixRetour > 0)
                                    <p class="text-xs text-surface-400 dark:text-surface-500">
                                        Aller-retour : {{ number_format($prixRetour, 0, ',', ' ') }} XOF
                                    </p>
                                @endif
                            </div>

                            @if($seatsLeft > 0)
                                <a href="{{ route('voyage.instance.show', $voyageInstance->id) }}" class="btn btn-primary btn-sm">
                                    Réserver
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
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
            {{-- Empty state --}}
            <div class="card text-center py-16 max-w-md mx-auto">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
                    <svg class="w-10 h-10 text-surface-400 dark:text-surface-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-2">Aucun voyage disponible</h3>
                <p class="text-surface-500 dark:text-surface-400 mb-6">
                    Aucun voyage ne correspond à vos critères de recherche. Essayez d'autres dates ou destinations.
                </p>
                <button wire:click="resetFilters" class="btn btn-ghost">
                    Effacer les filtres
                </button>
            </div>
        @endif
    </div>
</div>
