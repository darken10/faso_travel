<div>
    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Chauffeurs</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $chauffeurs->total() }} chauffeur(s) enregistré(s)</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text"
                       placeholder="Nom, matricule, téléphone..."
                       class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 w-56">
            </div>
            <button wire:click="openCreate"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouveau chauffeur
            </button>
        </div>
    </div>

    {{-- Grille de cards --}}
    @if($chauffeurs->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-base font-medium">Aucun chauffeur trouvé</p>
            <p class="text-sm mt-1">Ajoutez votre premier chauffeur pour commencer.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($chauffeurs as $c)
                @php
                    $statutColors = [
                        'actif'    => 'bg-green-100 text-green-700',
                        'inactif'  => 'bg-gray-100 text-gray-500',
                        'suspendu' => 'bg-red-100 text-red-600',
                    ];
                    $dotColors = [
                        'actif'    => 'bg-green-500',
                        'inactif'  => 'bg-gray-400',
                        'suspendu' => 'bg-red-500',
                    ];
                    $initials = strtoupper(substr($c->first_name, 0, 1) . substr($c->last_name, 0, 1));
                    $avatarColors = ['bg-blue-500', 'bg-violet-500', 'bg-amber-500', 'bg-teal-500', 'bg-rose-500', 'bg-indigo-500'];
                    $avatarColor  = $avatarColors[crc32($c->id) % count($avatarColors)];
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col">

                    {{-- Bandeau coloré + photo --}}
                    <div class="relative h-20 bg-gradient-to-br from-blue-50 to-indigo-100 flex-shrink-0">
                        <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                            @if($c->photo)
                                <img src="{{ Storage::url($c->photo) }}"
                                     alt="{{ $c->fullName() }}"
                                     class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-md">
                            @else
                                <div class="w-16 h-16 rounded-full {{ $avatarColor }} border-4 border-white shadow-md flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ $initials }}</span>
                                </div>
                            @endif
                            {{-- Indicateur statut --}}
                            <span class="absolute bottom-0.5 right-0.5 w-4 h-4 rounded-full border-2 border-white {{ $dotColors[$c->statut] ?? 'bg-gray-400' }}"></span>
                        </div>
                    </div>

                    {{-- Contenu --}}
                    <div class="pt-10 pb-4 px-4 flex flex-col flex-1">
                        {{-- Nom --}}
                        <div class="text-center mb-3">
                            <h3 class="font-semibold text-gray-800 text-base leading-tight">{{ $c->fullName() }}</h3>
                            @if($c->matricule)
                                <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-gray-500 text-xs font-mono rounded-full">{{ $c->matricule }}</span>
                            @endif
                        </div>

                        {{-- Infos --}}
                        <div class="space-y-2 text-sm text-gray-500 flex-1">
                            @if($c->telephone)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>{{ $c->telephone }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>{{ $c->genre }}</span>
                            </div>
                            @if($c->date_naissance)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ $c->date_naissance->format('d/m/Y') }} · {{ $c->date_naissance->age }} ans</span>
                                </div>
                            @endif
                        </div>

                        {{-- Documents --}}
                        <button wire:click="openDocPanel('{{ $c->id }}')"
                                class="mt-3 w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-indigo-100 text-indigo-600 bg-indigo-50/60 hover:bg-indigo-100 transition-colors text-xs font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Documents
                            @if($c->documents_count > 0)
                                <span class="ml-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] font-bold">{{ $c->documents_count }}</span>
                            @endif
                        </button>

                        {{-- Statut + Actions --}}
                        <div class="mt-3 pt-3 border-t border-gray-50 flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $statutColors[$c->statut] ?? 'bg-gray-100 text-gray-500' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$c->statut] ?? 'bg-gray-400' }}"></span>
                                {{ ucfirst($c->statut) }}
                            </span>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('panel.compagnie.chauffeurs.show', ['chauffeurId' => $c->id]) }}" title="Voir"
                                   class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button wire:click="openEdit('{{ $c->id }}')"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete('{{ $c->id }}')" wire:confirm="Supprimer ce chauffeur ?"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($chauffeurs->hasPages())
            <div class="mt-6">{{ $chauffeurs->links() }}</div>
        @endif
    @endif

    {{-- ═══ Modal Create / Edit ═══ --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto"
             x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 wire:click="$set('showModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg my-auto">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Modifier le chauffeur' : 'Nouveau chauffeur' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="px-6 py-5 space-y-5">

                    {{-- Photo upload --}}
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative">
                            @if($photo)
                                <img src="{{ $photo->temporaryUrl() }}"
                                     class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg ring-2 ring-blue-100">
                            @elseif($existingPhoto)
                                <img src="{{ Storage::url($existingPhoto) }}"
                                     class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg ring-2 ring-blue-100">
                            @else
                                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 border-4 border-white shadow-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">
                                        {{ $first_name ? strtoupper(substr($first_name, 0, 1)) : '?' }}
                                    </span>
                                </div>
                            @endif

                            <label class="absolute bottom-0 right-0 bg-white border border-gray-200 rounded-full p-1.5 cursor-pointer shadow hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                            </label>
                        </div>
                        <div wire:loading wire:target="photo" class="text-xs text-blue-500 flex items-center gap-1">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Chargement…
                        </div>
                        @error('photo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400">JPG, PNG · Max 2 Mo</p>
                    </div>

                    {{-- Nom / Prénom --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                            <input wire:model="first_name" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Oumar">
                            @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input wire:model="last_name" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Traoré">
                            @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Matricule / Téléphone --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Matricule</label>
                            <input wire:model="matricule" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="CHF-0001">
                            @error('matricule') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input wire:model="telephone" type="tel"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="+226 70 00 00 00">
                            @error('telephone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Date naissance / Genre --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance *</label>
                            <input wire:model="date_naissance" type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('date_naissance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Genre *</label>
                            <select wire:model="genre"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Genre --</option>
                                <option value="Homme">Homme</option>
                                <option value="Femme">Femme</option>
                            </select>
                            @error('genre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Statut --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut *</label>
                        <div class="flex gap-3">
                            @foreach(['actif' => ['label' => 'Actif', 'color' => 'green'], 'inactif' => ['label' => 'Inactif', 'color' => 'gray'], 'suspendu' => ['label' => 'Suspendu', 'color' => 'red']] as $val => $cfg)
                                @php
                                    $selected = $statut === $val;
                                    $base = 'flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium cursor-pointer transition-colors';
                                    $cls  = $selected
                                        ? "border-{$cfg['color']}-400 bg-{$cfg['color']}-50 text-{$cfg['color']}-700"
                                        : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300';
                                @endphp
                                <label class="{{ $base }} {{ $cls }}">
                                    <input type="radio" wire:model.live="statut" value="{{ $val }}" class="sr-only">
                                    <span class="w-2 h-2 rounded-full
                                        {{ $val === 'actif' ? 'bg-green-500' : ($val === 'suspendu' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                                    {{ $cfg['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @error('statut') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-1">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            Annuler
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Enregistrer' : 'Ajouter' }}</span>
                            <span wire:loading wire:target="save">Enregistrement…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
