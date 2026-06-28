<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Instances de voyage</h1>
                <p class="text-sm text-gray-500">{{ $instances->total() }} instance{{ $instances->total() > 1 ? 's' : '' }} planifiée{{ $instances->total() > 1 ? 's' : '' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            {{-- Export planning Excel --}}
            <button wire:click="exportPlanning"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Planning Excel
            </button>
            {{-- Générer les instances --}}
            <button wire:click="openGenModal"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Générer les instances
            </button>
            {{-- Créer manuellement --}}
            <button wire:click="openCreate"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouvelle instance
            </button>
        </div>
    </div>

    {{-- ═══ Modal Génération ═══ --}}
    @if($showGenModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.set('showGenModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 wire:click="$set('showGenModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                {{-- Header --}}
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Générer les instances</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Planification automatique des voyages</p>
                    </div>
                    <button wire:click="$set('showGenModal', false)"
                            class="ml-auto text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Corps --}}
                <div class="px-6 py-5 space-y-5">
                    {{-- Explication --}}
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-sm text-emerald-800 space-y-1">
                        <p class="font-medium">Comment ça fonctionne ?</p>
                        <ul class="text-xs text-emerald-700 space-y-1 list-disc list-inside mt-1">
                            <li>Tous les voyages actifs de votre compagnie sont analysés</li>
                            <li>Une instance est créée pour chaque jour correspondant au calendrier du voyage</li>
                            <li>Les instances déjà existantes ne sont pas dupliquées</li>
                            <li>Véhicule et prix hérités du voyage (modifiables ensuite)</li>
                        </ul>
                    </div>

                    {{-- Nb de jours --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Générer sur les prochains
                        </label>
                        <div class="flex items-center gap-3">
                            <input wire:model="genJours" type="number" min="1" max="90"
                                   class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <span class="text-sm text-gray-600">jours</span>
                            <span class="text-xs text-gray-400">(max 90)</span>
                        </div>
                        @error('genJours') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                        {{-- Aperçu de la période --}}
                        <p class="text-xs text-gray-400 mt-2">
                            Du {{ now()->format('d/m/Y') }} au {{ now()->addDays($genJours - 1)->format('d/m/Y') }}
                        </p>
                    </div>

                    {{-- Voyages concernés --}}
                    <div class="bg-gray-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-600">Voyages concernés</span>
                        <span class="text-sm font-bold text-gray-800">
                            {{ \App\Models\Voyage\Voyage::withoutGlobalScopes()->where('compagnie_id', auth()->user()->compagnie_id)->count() }} voyage(s)
                        </span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 px-6 pb-6">
                    <button type="button" wire:click="$set('showGenModal', false)"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Annuler
                    </button>
                    <button wire:click="generateInstances"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="generateInstances">
                            <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Générer
                        </span>
                        <span wire:loading wire:target="generateInstances" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Génération en cours…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-3 overflow-x-auto">
                {{-- Recherche --}}
                <div class="relative flex-1 min-w-[160px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher une ville…" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                {{-- Période --}}
                <div class="inline-flex shrink-0 rounded-lg border border-gray-200 p-0.5 bg-gray-50">
                    @foreach (['upcoming' => 'À venir', 'past' => 'Passés', 'all' => 'Tous'] as $val => $label)
                        <button type="button" wire:click="$set('periode', '{{ $val }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition
                                {{ $periode === $val ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Plage de dates --}}
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-gray-500">Du</span>
                    <input wire:model.live="dateDebut" type="date" class="px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <span class="text-xs text-gray-500">au</span>
                    <input wire:model.live="dateFin" type="date" class="px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @if ($dateDebut || $dateFin)
                        <button type="button" wire:click="resetDateFilters" class="p-1.5 text-gray-400 hover:text-red-600" title="Réinitialiser les dates">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Trajet</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Heure</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Places</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Véhicule / Chauffeur</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($instances as $instance)
                    @php
                        $statutVal = $instance->statut?->value ?? '';
                        $isPast    = $instance->date?->isPast();
                        $rowCls    = match($statutVal) {
                            'ANNULE'  => 'bg-red-50/40',
                            'RETARDE' => 'bg-amber-50/40',
                            default   => '',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $rowCls }}">
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800">
                                {{ $instance->voyage?->trajet?->depart?->name }} → {{ $instance->voyage?->trajet?->arriver?->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                            {{ $instance->date?->format('d/m/Y') }}
                            @if($isPast)
                                <span class="ml-1 text-xs text-gray-400">(passé)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $instance->heure?->format('H:i') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $instance->nb_place ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                @if($instance->care)
                                    <span class="font-mono text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded w-fit">{{ $instance->care->immatrculation }}</span>
                                @else
                                    <span class="text-xs text-gray-300 italic">Aucun véhicule</span>
                                @endif
                                @if($instance->chauffer)
                                    <span class="text-xs text-gray-500">{{ $instance->chauffer->first_name }} {{ $instance->chauffer->last_name }}</span>
                                @else
                                    <span class="text-xs text-gray-300 italic">Aucun chauffeur</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php $sc = match($statutVal) { 'DISPONIBLE' => 'green', 'RETARDE' => 'amber', 'ANNULE' => 'red', default => 'gray' }; @endphp
                            <x-panel.badge :color="$sc" size="xs">{{ $statutVal ?: 'N/A' }}</x-panel.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Voir le détail --}}
                                <a href="{{ route('panel.compagnie.instances.show', ['instanceId' => $instance->id]) }}"
                                   title="Voir le détail"
                                   class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                {{-- Manifeste d'embarquement (PDF) --}}
                                <button wire:click="exportManifeste('{{ $instance->id }}')"
                                        title="Manifeste d'embarquement (PDF)"
                                        class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </button>
                                {{-- Affecter chauffeur/véhicule --}}
                                @if(!in_array($statutVal, ['ANNULE']))
                                    <button wire:click="openAssignModal('{{ $instance->id }}')"
                                            title="Affecter chauffeur / véhicule"
                                            class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                    {{-- Annuler / Retarder --}}
                                    <button wire:click="openAlertModal('{{ $instance->id }}')"
                                            title="Annuler ou signaler un retard"
                                            class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="openEdit('{{ $instance->id }}')"
                                        title="Modifier"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete('{{ $instance->id }}')" wire:confirm="Supprimer cette instance ?"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Aucune instance trouvée</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($instances->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $instances->links() }}</div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg my-4" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Modifier l\'instance' : 'Nouvelle instance' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Voyage *</label>
                        <select wire:model.live="voyage_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">-- Choisir un voyage --</option>
                            @foreach($voyages as $v)
                                <option value="{{ $v->id }}">{{ $v->trajet?->depart?->name }} → {{ $v->trajet?->arriver?->name }} ({{ $v->heure ? \Carbon\Carbon::parse($v->heure)->format('H:i') : '—' }})</option>
                            @endforeach
                        </select>
                        @error('voyage_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                            <input wire:model="date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
                            <input wire:model="heure" type="time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('heure') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    {{-- Valeurs dérivées (lecture seule) --}}
                    <div class="grid grid-cols-3 gap-3" x-data>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nb. de places</label>
                            <input type="text" x-bind:value="$wire.previewNbPlace || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prix aller (F)</label>
                            <input type="text" x-bind:value="$wire.previewPrix || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prix aller-retour (F)</label>
                            <input type="text" x-bind:value="$wire.previewPrixAllerRetour || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule</label>
                            <select wire:model.live="care_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Véhicule --</option>
                                @foreach($cares as $care)
                                    <option value="{{ $care->id }}">{{ $care->immatrculation }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                            <select wire:model="classe_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Classe --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chauffeur</label>
                            <select wire:model="chauffer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Chauffeur --</option>
                                @foreach($chaufers as $c)
                                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
                            <select wire:model="statut" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                @foreach($statuts as $s)
                                    <option value="{{ $s->value }}">{{ $s->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">{{ $editingId ? 'Enregistrer' : 'Créer' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══ Modale Affectation (chauffeur / véhicule) ═══ --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.set('showAssignModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 wire:click="$set('showAssignModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800">Affecter ressources</h3>
                    <button wire:click="$set('showAssignModal', false)"
                            class="ml-auto text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    {{-- Véhicule --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule</label>
                        <select wire:model.live="assignCareId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <option value="">— Aucun véhicule —</option>
                            @foreach($cares as $care)
                                <option value="{{ $care->id }}">{{ $care->immatrculation }} ({{ $care->number_place }} places)</option>
                            @endforeach
                        </select>
                        @error('assignCareId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Chauffeur --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chauffeur</label>
                        <select wire:model="assignChauffeurId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <option value="">— Aucun chauffeur —</option>
                            @foreach($chaufers as $c)
                                <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}{{ $c->matricule ? ' · '.$c->matricule : '' }}</option>
                            @endforeach
                        </select>
                        @error('assignChauffeurId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Valeurs dérivées (lecture seule) --}}
                    <div class="grid grid-cols-3 gap-3" x-data>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nb. de places</label>
                            <input type="text" x-bind:value="$wire.assignPreviewNbPlace || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prix aller (F)</label>
                            <input type="text" x-bind:value="$wire.assignPreviewPrix || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prix aller-retour (F)</label>
                            <input type="text" x-bind:value="$wire.assignPreviewPrixAllerRetour || '—'" readonly
                                   class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 px-6 pb-6">
                    <button type="button" wire:click="$set('showAssignModal', false)"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                        Annuler
                    </button>
                    <button wire:click="saveAssignment"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="saveAssignment">Enregistrer</span>
                        <span wire:loading wire:target="saveAssignment">Enregistrement…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ Modale Alerte (annulation / retard) ═══ --}}
    @if($showAlertModal)
        @php
            $alertInstance = \App\Models\Voyage\VoyageInstance::withCount([
                'tickets as tickets_actifs_count' => fn($q) => $q->whereIn('statut', ['Payer','Valider','En attente'])
            ])->find($alertingId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.set('showAlertModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                 wire:click="$set('showAlertModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Signaler un problème</h3>
                        <p class="text-xs text-gray-500">
                            {{ $alertInstance?->voyage?->trajet?->depart?->name }} → {{ $alertInstance?->voyage?->trajet?->arriver?->name }}
                            · {{ $alertInstance?->date?->format('d/m/Y') }}
                        </p>
                    </div>
                    <button wire:click="$set('showAlertModal', false)"
                            class="ml-auto text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    {{-- Type d'alerte --}}
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['ANNULE' => ['label' => 'Annuler le voyage', 'icon' => 'M6 18L18 6M6 6l12 12', 'color' => 'red'], 'RETARDE' => ['label' => 'Signaler un retard', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber']] as $val => $cfg)
                            @php $selected = $alertType === $val; @endphp
                            <label class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all
                                {{ $selected
                                    ? ($val === 'ANNULE' ? 'border-red-400 bg-red-50' : 'border-amber-400 bg-amber-50')
                                    : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="alertType" value="{{ $val }}" class="sr-only">
                                <svg class="w-6 h-6 {{ $selected ? ($val === 'ANNULE' ? 'text-red-600' : 'text-amber-600') : 'text-gray-400' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/>
                                </svg>
                                <span class="text-xs font-semibold text-center {{ $selected ? ($val === 'ANNULE' ? 'text-red-700' : 'text-amber-700') : 'text-gray-600' }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Raison --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Message aux clients <span class="text-gray-400 font-normal">(optionnel)</span>
                        </label>
                        <textarea wire:model="alertReason" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-amber-400"
                                  placeholder="{{ $alertType === 'ANNULE' ? 'Raison de l\'annulation...' : 'Raison du retard, nouvelles horaires...' }}"></textarea>
                    </div>

                    {{-- Résumé impact --}}
                    <div class="{{ $alertType === 'ANNULE' ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100' }} border rounded-xl px-4 py-3 space-y-1">
                        <p class="text-sm font-medium {{ $alertType === 'ANNULE' ? 'text-red-800' : 'text-amber-800' }}">
                            Impact : {{ $alertInstance?->tickets_actifs_count ?? 0 }} ticket(s) actif(s)
                        </p>
                        @if($alertType === 'ANNULE')
                            <p class="text-xs text-red-700">→ Tous les tickets seront mis en <strong>Pause</strong> et les clients notifiés.</p>
                        @else
                            <p class="text-xs text-amber-700">→ Les clients seront notifiés du retard. Leurs tickets restent actifs.</p>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3 px-6 pb-6">
                    <button type="button" wire:click="$set('showAlertModal', false)"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                        Annuler
                    </button>
                    <button wire:click="confirmAlert"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors
                                {{ $alertType === 'ANNULE' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-500 hover:bg-amber-600' }}">
                        <span wire:loading.remove wire:target="confirmAlert">
                            {{ $alertType === 'ANNULE' ? 'Confirmer l\'annulation' : 'Confirmer le retard' }}
                        </span>
                        <span wire:loading wire:target="confirmAlert">Traitement…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
