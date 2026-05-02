<div>
    @php
        $statutConfig = [
            'valide'         => ['label' => 'Valide',         'bg' => 'bg-green-100',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
            'expire_bientot' => ['label' => 'Expire bientôt', 'bg' => 'bg-amber-100',  'text' => 'text-amber-700',  'dot' => 'bg-amber-500'],
            'expire'         => ['label' => 'Expiré',         'bg' => 'bg-red-100',    'text' => 'text-red-700',    'dot' => 'bg-red-500'],
        ];
        $typeLabels = [
            'App\\Models\\Compagnie\\Chauffer' => ['label' => 'Chauffeur', 'color' => 'text-blue-600',   'bg' => 'bg-blue-50'],
            'App\\Models\\Compagnie\\Care'     => ['label' => 'Véhicule',  'color' => 'text-violet-600', 'bg' => 'bg-violet-50'],
            'App\\Models\\Compagnie\\Gare'     => ['label' => 'Gare',      'color' => 'text-teal-600',   'bg' => 'bg-teal-50'],
            'App\\Models\\Finance\\Depense'    => ['label' => 'Dépense',   'color' => 'text-red-600',    'bg' => 'bg-red-50'],
            'App\\Models\\Finance\\Recette'    => ['label' => 'Recette',   'color' => 'text-green-600',  'bg' => 'bg-green-50'],
        ];
    @endphp

    {{-- En-tête ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Documents</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $documents->total() }} document(s)</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau document
        </button>
    </div>

    {{-- Filtres ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-6">
        {{-- Recherche --}}
        <div class="relative flex-1 min-w-48">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un document..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        {{-- Filtre type --}}
        <select wire:model.live="filterType"
                class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Tous les types</option>
            @foreach($entityTypes as $class => $label)
                <option value="{{ $class }}">{{ $label }}</option>
            @endforeach
        </select>
        {{-- Filtre statut --}}
        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-sm">
            @foreach([''=>'Tous', 'valide'=>'Valides', 'expire_bientot'=>'Bientôt', 'expire'=>'Expirés'] as $val => $lbl)
                <button wire:click="$set('filterStatut', '{{ $val }}')"
                        class="px-3 py-2 transition-colors {{ $filterStatut === $val ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $lbl }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Grille de cards ─────────────────────────────────────────────────── --}}
    @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-base font-medium">Aucun document trouvé</p>
            <p class="text-sm mt-1">Ajoutez votre premier document pour commencer.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($documents as $doc)
                @php
                    $statut = $doc->statut;
                    $sc     = $statutConfig[$statut] ?? $statutConfig['valide'];
                    $tc     = $typeLabels[$doc->documentable_type] ?? ['label' => $doc->documentable_type, 'color' => 'text-gray-600', 'bg' => 'bg-gray-50'];
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col overflow-hidden">

                    {{-- Icône fichier + type entité --}}
                    <div class="px-4 pt-4 pb-3 flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            @if($doc->file_icon === 'pdf')
                                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path d="M14 2v6h6M10 13a1 1 0 110 2 1 1 0 010-2zm4 0a1 1 0 110 2 1 1 0 010-2zM8 17h8"/></svg>
                                </div>
                            @elseif($doc->file_icon === 'image')
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @elseif($doc->file_icon === 'spreadsheet')
                                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tc['bg'] }} {{ $tc['color'] }}">
                                {{ $tc['label'] }}
                            </span>
                        </div>
                        {{-- Statut dot --}}
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ $sc['label'] }}
                        </span>
                    </div>

                    {{-- Contenu --}}
                    <div class="px-4 pb-3 flex-1 flex flex-col gap-1">
                        <h3 class="font-semibold text-gray-800 text-sm leading-tight line-clamp-2">{{ $doc->titre }}</h3>
                        @if($doc->description)
                            <p class="text-xs text-gray-400 line-clamp-2">{{ $doc->description }}</p>
                        @endif

                        {{-- Entité liée --}}
                        @if($doc->documentable)
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="font-medium">Lié à :</span>
                                @if($doc->documentable instanceof \App\Models\Compagnie\Chauffer)
                                    {{ $doc->documentable->fullName() }}
                                @elseif($doc->documentable instanceof \App\Models\Compagnie\Care)
                                    {{ $doc->documentable->immatrculation }}
                                @elseif($doc->documentable instanceof \App\Models\Compagnie\Gare)
                                    {{ $doc->documentable->name }}
                                @elseif($doc->documentable instanceof \App\Models\Finance\Depense)
                                    {{ $doc->documentable->libelle }}
                                @elseif($doc->documentable instanceof \App\Models\Finance\Recette)
                                    {{ $doc->documentable->libelle }}
                                @endif
                            </p>
                        @endif

                        {{-- Expiration --}}
                        @if($doc->has_expiration && $doc->date_expiration)
                            <div class="flex items-center gap-1 mt-1">
                                <svg class="w-3.5 h-3.5 {{ $statut === 'expire' ? 'text-red-400' : ($statut === 'expire_bientot' ? 'text-amber-400' : 'text-gray-300') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-xs {{ $statut === 'expire' ? 'text-red-500 font-medium' : ($statut === 'expire_bientot' ? 'text-amber-600 font-medium' : 'text-gray-400') }}">
                                    @if($statut === 'expire')
                                        Expiré le {{ $doc->date_expiration->format('d/m/Y') }}
                                    @elseif($statut === 'expire_bientot')
                                        Expire dans {{ $doc->jours_restants }}j
                                    @else
                                        Exp. {{ $doc->date_expiration->format('d/m/Y') }}
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Rappels + taille --}}
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-50">
                            <div class="flex items-center gap-1 text-xs text-gray-400">
                                @if($doc->rappels->count() > 0)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <span>{{ $doc->rappels->count() }} rappel(s)</span>
                                @endif
                                @if($doc->file_size_formatted)
                                    <span class="ml-1">· {{ $doc->file_size_formatted }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="{{ $doc->file_url }}" target="_blank"
                                   class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                                <button wire:click="openEdit({{ $doc->id }})"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $doc->id }})" wire:confirm="Supprimer ce document ?"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($documents->hasPages())
            <div class="mt-6">{{ $documents->links() }}</div>
        @endif
    @endif

    {{-- ═══ Modal Create / Edit ════════════════════════════════════════════ --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-6 overflow-y-auto"
             x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 wire:click="$set('showModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-auto">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Modifier le document' : 'Nouveau document' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="px-6 py-5 space-y-5 max-h-[80vh] overflow-y-auto">

                    {{-- Section : Informations --}}
                    <div class="space-y-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Informations</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                            <input wire:model="titre" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex : Permis de conduire">
                            @error('titre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea wire:model="description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                                      placeholder="Notes complémentaires…"></textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Section : Entité liée --}}
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Entité liée</p>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                <select wire:model.live="documentable_type"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="">-- Choisir --</option>
                                    @foreach($entityTypes as $class => $label)
                                        <option value="{{ $class }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('documentable_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Entité *</label>
                                <select wire:model="documentable_id"
                                        {{ !$documentable_type ? 'disabled' : '' }}
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <option value="">{{ $documentable_type ? '-- Sélectionner --' : 'Choisir un type d\'abord' }}</option>
                                    @foreach($this->entities as $entity)
                                        <option value="{{ $entity['id'] }}">{{ $entity['label'] }}</option>
                                    @endforeach
                                </select>
                                <span wire:loading wire:target="documentable_type" class="absolute right-8 top-7">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </span>
                                @error('documentable_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section : Fichier --}}
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Fichier</p>

                        @if($editingId && $existingFileName && !$fichier)
                            <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $existingFileName }}
                                <span class="text-gray-400 text-xs ml-auto">Laisser vide pour conserver</span>
                            </div>
                        @endif

                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-blue-300 transition-colors cursor-pointer"
                             x-data
                             @click="$refs.fileInput.click()">
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="text-sm text-gray-500">
                                @if($fichier)
                                    <span class="font-medium text-blue-600">{{ $fichier->getClientOriginalName() }}</span>
                                @else
                                    <span class="font-medium text-blue-600">Cliquer pour choisir</span> ou glisser-déposer
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-1">PDF, Word, Excel, Images · Max 10 Mo</p>
                            <input x-ref="fileInput" type="file" wire:model="fichier" class="sr-only"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.webp">
                        </div>
                        <div wire:loading wire:target="fichier" class="text-xs text-blue-500 flex items-center gap-1">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Chargement du fichier…
                        </div>
                        @error('fichier') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    {{-- Section : Expiration --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Expiration</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" wire:model.live="has_expiration" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-checked:bg-blue-600 rounded-full transition-colors"></div>
                                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                                </div>
                                <span class="text-sm text-gray-600">Ce document a une date d'expiration</span>
                            </label>
                        </div>

                        @if($has_expiration)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration *</label>
                                <input wire:model="date_expiration" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                @error('date_expiration') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    {{-- Section : Rappels --}}
                    @if($has_expiration)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Rappels</p>
                                <button type="button" wire:click="addRappel"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Ajouter un rappel
                                </button>
                            </div>

                            @if(empty($rappels))
                                <p class="text-xs text-gray-400 italic text-center py-2">Aucun rappel configuré</p>
                            @else
                                <div class="space-y-3">
                                    @foreach($rappels as $i => $rappel)
                                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 space-y-3">
                                            {{-- Délai --}}
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center gap-2 flex-1">
                                                    <input wire:model="rappels.{{ $i }}.delai_valeur"
                                                           type="number" min="0"
                                                           class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-400">
                                                    <select wire:model="rappels.{{ $i }}.delai_unite"
                                                            class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                                        <option value="jours">jours avant</option>
                                                        <option value="heures">heures avant</option>
                                                    </select>
                                                </div>
                                                <button type="button" wire:click="removeRappel({{ $i }})"
                                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>

                                            {{-- Canaux --}}
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1.5">Canaux de notification</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram'] as $canal => $canalLabel)
                                                        @php $selected = in_array($canal, $rappel['canaux'] ?? []); @endphp
                                                        <button type="button"
                                                                wire:click="toggleCanal({{ $i }}, '{{ $canal }}')"
                                                                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors {{ $selected ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300' }}">
                                                            {{ $canalLabel }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                                @error("rappels.{$i}.canaux") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-2 sticky bottom-0 bg-white pb-1">
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
