<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Véhicules (Cares)</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cares->total() }} véhicules enregistrés</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau véhicule
        </button>
    </div>

    {{-- Search --}}
    <div class="relative mb-5">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Rechercher par immatriculation ou numéro..."
               class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white shadow-sm">
    </div>

    {{-- Cards grid --}}
    @if($cares->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="font-medium">Aucun véhicule trouvé</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($cares as $care)
                @php
                    $statusConfig = match($care->statut?->value ?? '') {
                        'Disponible' => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'dot' => 'bg-green-500',  'band' => 'bg-green-500'],
                        'Occuper'    => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'dot' => 'bg-blue-500',   'band' => 'bg-blue-500'],
                        'En Panne'   => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'dot' => 'bg-red-500',    'band' => 'bg-red-500'],
                        default      => ['bg' => 'bg-gray-100',   'text' => 'text-gray-600',   'dot' => 'bg-gray-400',   'band' => 'bg-gray-300'],
                    };
                @endphp

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden group">

                    {{-- Vehicle photo --}}
                    <div class="relative h-36 bg-gray-50">
                        @if($care->image_uri)
                            <img src="{{ Storage::url($care->image_uri) }}"
                                 alt="{{ $care->immatrculation }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs text-gray-300">Pas de photo</span>
                            </div>
                        @endif

                        {{-- Status badge overlay --}}
                        <div class="absolute top-2.5 right-2.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold backdrop-blur-sm
                                         {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                {{ $care->statut?->value }}
                            </span>
                        </div>
                    </div>

                    {{-- Status band --}}
                    <div class="h-1 {{ $statusConfig['band'] }} opacity-60"></div>

                    <div class="p-4">
                        {{-- Plate number + bus number --}}
                        <div class="flex items-center gap-2 mb-3">
                            @if($care->numero)
                                <div class="bg-blue-600 rounded-lg px-3 py-1.5 text-center flex-shrink-0">
                                    <p class="text-xs text-blue-200 leading-none mb-0.5">Bus</p>
                                    <p class="font-bold text-white text-sm tracking-wider">{{ $care->numero }}</p>
                                </div>
                            @endif
                            <div class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-center">
                                <p class="text-xs text-gray-400 leading-none mb-0.5">Immatriculation</p>
                                <p class="font-mono font-bold text-gray-800 text-sm tracking-wider">{{ $care->immatrculation }}</p>
                            </div>
                        </div>

                        {{-- Stats row --}}
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="bg-indigo-50 rounded-xl p-2.5 text-center">
                                <p class="text-lg font-bold text-indigo-700">{{ $care->number_place }}</p>
                                <p class="text-xs text-indigo-400 font-medium">places</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-2.5 text-center">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ $care->etat ?? '—' }}</p>
                                <p class="text-xs text-slate-400 font-medium">état</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-3 border-t border-gray-50">
                            {{-- Documents --}}
                            <button wire:click="openDocPanel({{ $care->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-indigo-100 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ $care->documents_count ?: 'Docs' }}
                            </button>

                            <div class="flex-1"></div>

                            {{-- Voir --}}
                            <a href="{{ route('panel.compagnie.cares.show', ['careId' => $care->id]) }}" title="Voir"
                               class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>

                            {{-- Edit --}}
                            <button wire:click="openEdit({{ $care->id }})"
                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            {{-- Delete --}}
                            <button wire:click="delete({{ $care->id }})" wire:confirm="Supprimer ce véhicule ?"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($cares->hasPages())
            <div class="mt-6">{{ $cares->links() }}</div>
        @endif
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Modifier le véhicule' : 'Nouveau véhicule' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-4" enctype="multipart/form-data">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N° de bus</label>
                            <input wire:model="numero" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: 56, 12A">
                            @error('numero') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Immatriculation *</label>
                            <input wire:model="immatrculation" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: BF-1234-AB">
                            @error('immatrculation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nb. de places *</label>
                            <input wire:model="number_place" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: 60">
                            @error('number_place') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
                            <select wire:model="statut" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Statut --</option>
                                @foreach($statuts as $s)
                                    <option value="{{ $s->value }}">{{ $s->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">État</label>
                            <input wire:model="etat" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Bon état...">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                        <input wire:model="image" type="file" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div wire:loading wire:target="image" class="text-xs text-gray-400 mt-1">Chargement...</div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">{{ $editingId ? 'Enregistrer' : 'Créer' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
