<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @php
        // Les statuts en base sont : Activer · Désactiver · Pause · Bloquer.
        $statutColors = [
            'Activer'    => ['badge' => 'green', 'dot' => 'bg-green-500'],
            'Désactiver' => ['badge' => 'gray',  'dot' => 'bg-gray-400'],
            'Pause'      => ['badge' => 'amber', 'dot' => 'bg-amber-500'],
            'Bloquer'    => ['badge' => 'red',   'dot' => 'bg-red-500'],
        ];
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Compagnies</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $compagnies->total() }} compagnie(s) enregistrée(s)</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvelle compagnie
        </button>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher par nom ou sigle…"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>
            <div>
                <select wire:model.live="statutFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Grille de cartes --}}
    @if($compagnies->count())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($compagnies as $compagnie)
                @php
                    $statutName = $compagnie->statut?->name;
                    $colors = $statutColors[$statutName] ?? ['badge' => 'gray', 'dot' => 'bg-gray-400'];
                @endphp
                <div wire:key="compagnie-{{ $compagnie->id }}"
                     class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col">

                    {{-- En-tête : identité --}}
                    <div class="p-4 flex items-start gap-3">
                        @if($compagnie->logo_uri)
                            <img src="{{ Storage::url($compagnie->logo_uri) }}" alt="{{ $compagnie->name }}"
                                 class="w-12 h-12 rounded-xl object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold flex-shrink-0">
                                {{ strtoupper(substr($compagnie->name, 0, 2)) }}
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-semibold text-gray-800 truncate" title="{{ $compagnie->name }}">{{ $compagnie->name }}</p>
                                <x-panel.badge :color="$colors['badge']" class="flex-shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $colors['dot'] }} mr-1.5"></span>
                                    {{ $statutName ?? 'Sans statut' }}
                                </x-panel.badge>
                            </div>
                            <p class="text-xs font-mono uppercase text-gray-400 mt-0.5">{{ $compagnie->sigle }}</p>
                            @if($compagnie->slogant)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $compagnie->slogant }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Chiffres clés --}}
                    <div class="px-4 grid grid-cols-3 gap-2 text-center">
                        <div class="bg-gray-50 rounded-lg py-2">
                            <p class="text-base font-bold text-gray-800 leading-none">{{ $compagnie->voyages_count }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">Voyages</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <p class="text-base font-bold text-gray-800 leading-none">{{ $compagnie->gares_count }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">Gares</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <p class="text-base font-bold text-gray-800 leading-none">{{ $compagnie->users_count }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">Équipe</p>
                        </div>
                    </div>

                    <div class="px-4 pt-3">
                        <p class="text-xs text-gray-500 truncate">
                            <span class="text-gray-400">Responsable :</span> {{ $compagnie->user?->name ?? '—' }}
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-auto p-4 pt-3 flex items-center justify-between gap-2">
                        {{-- Changement de statut --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full {{ $colors['dot'] }}"></span>
                                Statut
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.origin.bottom.left
                                 class="absolute bottom-full left-0 mb-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-20">
                                @foreach($statuts as $s)
                                    <button type="button"
                                            wire:click="changeStatut({{ $compagnie->id }}, {{ $s->id }})"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center gap-2 hover:bg-gray-50 transition-colors {{ $compagnie->statut_id === $s->id ? 'font-semibold text-gray-900 bg-gray-50' : 'text-gray-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ ($statutColors[$s->name] ?? ['dot' => 'bg-gray-400'])['dot'] }}"></span>
                                        {{ $s->name }}
                                        @if($compagnie->statut_id === $s->id)
                                            <svg class="w-3.5 h-3.5 ml-auto text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <a href="{{ route('panel.admin.settings', ['compagnie' => $compagnie->id]) }}"
                               title="Paramètres de la compagnie"
                               class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <button wire:click="openEdit({{ $compagnie->id }})" title="Modifier"
                                    class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="delete({{ $compagnie->id }})" wire:confirm="Supprimer « {{ $compagnie->name }} » ?" title="Supprimer"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($compagnies->hasPages())
            <div class="mt-6">{{ $compagnies->links() }}</div>
        @endif
    @else
        <div class="bg-white rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 text-sm">Aucune compagnie ne correspond à votre recherche.</p>
        </div>
    @endif


    {{-- Modale de création / édition --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.closeModal()">

            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 wire:click="closeModal"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[92vh]"
                 x-trap.noscroll="true"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                {{-- En-tête --}}
                <div class="flex items-start gap-3 px-6 py-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-semibold text-gray-800">
                            {{ $editingId ? 'Modifier la compagnie' : 'Nouvelle compagnie' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $editingId ? 'Mettez à jour l\'identité et le statut de la compagnie.' : 'Renseignez l\'identité de la compagnie à référencer.' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="flex flex-col min-h-0 flex-1">

                    <div class="px-6 py-5 space-y-6 overflow-y-auto">

                        {{-- Identité visuelle --}}
                        <section class="space-y-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Identité</p>

                            <x-form.image-upload
                                model="logo"
                                :file="$logo"
                                :existing="$existingLogo"
                                :fallback="strtoupper(substr($name ?: '?', 0, 2))"
                                label="Logo de la compagnie"
                                hint="Carré de préférence · PNG, JPG, WEBP ou SVG · 2 Mo maximum"
                                accent="amber"
                                remove-action="removeLogo"/>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label for="cm-name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nom <span class="text-red-500">*</span>
                                    </label>
                                    <input id="cm-name" wire:model="name" type="text" placeholder="Ex. Société de Transport Africain"
                                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 @error('name') border-red-300 @else border-gray-300 @enderror">
                                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cm-sigle" class="block text-sm font-medium text-gray-700 mb-1">
                                        Sigle <span class="text-red-500">*</span>
                                    </label>
                                    <input id="cm-sigle" wire:model="sigle" type="text" placeholder="Ex. STAF"
                                           class="w-full px-3 py-2 border rounded-lg text-sm uppercase font-mono focus:outline-none focus:ring-2 focus:ring-amber-400 @error('sigle') border-red-300 @else border-gray-300 @enderror">
                                    @error('sigle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        {{-- Présentation --}}
                        <section class="space-y-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Présentation</p>

                            <div>
                                <label for="cm-slogant" class="block text-sm font-medium text-gray-700 mb-1">Slogan</label>
                                <input id="cm-slogant" wire:model="slogant" type="text" placeholder="Voyagez en toute sérénité"
                                       class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 @error('slogant') border-red-300 @else border-gray-300 @enderror">
                                @error('slogant') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div x-data="{ len: ($wire.description || '').length }">
                                <div class="flex items-center justify-between mb-1">
                                    <label for="cm-description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <span class="text-xs text-gray-400"><span x-text="len"></span>/2000</span>
                                </div>
                                <textarea id="cm-description" wire:model="description" rows="4"
                                          x-on:input="len = $event.target.value.length"
                                          placeholder="Activités, zones desservies, spécificités de la compagnie…"
                                          class="w-full px-3 py-2 border rounded-lg text-sm resize-y focus:outline-none focus:ring-2 focus:ring-amber-400 @error('description') border-red-300 @else border-gray-300 @enderror"></textarea>
                                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </section>

                        {{-- Statut --}}
                        <section class="space-y-3">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Statut</p>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach($statuts as $s)
                                    @php $dot = ($statutColors[$s->name] ?? ['dot' => 'bg-gray-400'])['dot']; @endphp
                                    <label class="flex items-center gap-2 px-3 py-2.5 border rounded-lg cursor-pointer text-sm transition-colors
                                                  {{ (int) $statut_id === $s->id ? 'border-amber-400 bg-amber-50 text-gray-900 font-medium' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model.live="statut_id" value="{{ $s->id }}" class="sr-only">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dot }}"></span>
                                        <span class="truncate">{{ $s->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('statut_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </section>
                    </div>

                    {{-- Pied --}}
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/70 rounded-b-2xl flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg transition-colors">
                            Annuler
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save,logo"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ $editingId ? 'Enregistrer' : 'Créer la compagnie' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
