<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Trajets</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $trajets->total() }} trajets définis</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau trajet
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text"
                       placeholder="Rechercher par ville départ ou arrivée..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Départ</th>
                    <th class="px-2 py-3 text-gray-300">→</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Arrivée</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Distance</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Durée</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($trajets as $trajet)
                    @php
                        $tempsLabel = '—';
                        if ($trajet->temps) {
                            $parts = array_pad(explode(':', $trajet->temps), 2, '0');
                            $h = (int) $parts[0];
                            $m = (int) $parts[1];
                            $tempsLabel = $h > 0
                                ? $h . 'h' . str_pad($m, 2, '0', STR_PAD_LEFT)
                                : $m . 'min';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $trajet->depart?->name }}</td>
                        <td class="px-2 py-3 text-gray-300 text-center">→</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $trajet->arriver?->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $trajet->distance ? $trajet->distance . ' km' : '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $tempsLabel }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openEdit({{ $trajet->id }})"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $trajet->id }})" wire:confirm="Supprimer ce trajet ?"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucun trajet trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($trajets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $trajets->links() }}</div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 overflow-y-auto"
             x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Modifier le trajet' : 'Nouveau trajet' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="px-6 py-5 space-y-5">

                    {{-- Sélection géographique : deux colonnes --}}
                    <div class="grid grid-cols-2 gap-5">

                        {{-- ── DÉPART ── --}}
                        <div class="space-y-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Départ</p>

                            {{-- Pays --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pays *</label>
                                <select wire:model.live="depart_pays_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="">-- Pays --</option>
                                    @foreach($pays as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Région --}}
                            <div class="relative">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Région *</label>
                                <select wire:model.live="depart_region_id"
                                        {{ !$depart_pays_id ? 'disabled' : '' }}
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <option value="">{{ $depart_pays_id ? '-- Région --' : 'Sélectionner un pays' }}</option>
                                    @foreach($departRegions as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <span wire:loading wire:target="depart_pays_id"
                                      class="absolute right-8 top-7">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </div>

                            {{-- Ville (combobox searchable) --}}
                            <div wire:key="depart-ville-{{ $depart_region_id ?? 0 }}"
                                 x-data='{
                                     q: {{ json_encode($departVilles->firstWhere("id", $depart_id)?->name ?? "", JSON_HEX_TAG | JSON_HEX_APOS) }},
                                     open: false,
                                     opts: {{ json_encode($departVilles->values(), JSON_HEX_TAG | JSON_HEX_APOS) }},
                                     get filtered() {
                                         const s = this.q.toLowerCase();
                                         return s ? this.opts.filter(o => o.name.toLowerCase().includes(s)) : this.opts;
                                     },
                                     pick(id, name) { $wire.set("depart_id", id); this.q = name; this.open = false; },
                                     clear() { $wire.set("depart_id", null); this.q = ""; this.open = false; }
                                 }'
                                 @click.outside="open = false"
                                 class="relative">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Ville *</label>
                                <div class="relative" wire:loading.class="opacity-50 pointer-events-none" wire:target="depart_region_id">
                                    <input type="text"
                                           x-model="q"
                                           @focus="if(opts.length) open = true"
                                           @input="open = true"
                                           placeholder="{{ $departVilles->isEmpty() ? 'Sélectionner une région' : 'Rechercher une ville...' }}"
                                           {{ $departVilles->isEmpty() ? 'disabled' : '' }}
                                           class="w-full px-3 py-2 pr-7 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <button type="button" x-show="q" @click="clear()"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    </button>
                                    <span wire:loading wire:target="depart_region_id"
                                          class="absolute right-2 top-1/2 -translate-y-1/2">
                                        <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div x-show="open"
                                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                    <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">Aucune ville trouvée</p>
                                    <template x-for="o in filtered" :key="o.id">
                                        <button type="button" @click="pick(o.id, o.name)"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 transition-colors"
                                                x-text="o.name"></button>
                                    </template>
                                </div>
                                @error('depart_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- ── DESTINATION ── --}}
                        <div class="space-y-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Destination</p>

                            {{-- Pays --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pays *</label>
                                <select wire:model.live="arriver_pays_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="">-- Pays --</option>
                                    @foreach($pays as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Région --}}
                            <div class="relative">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Région *</label>
                                <select wire:model.live="arriver_region_id"
                                        {{ !$arriver_pays_id ? 'disabled' : '' }}
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <option value="">{{ $arriver_pays_id ? '-- Région --' : 'Sélectionner un pays' }}</option>
                                    @foreach($arriverRegions as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <span wire:loading wire:target="arriver_pays_id"
                                      class="absolute right-8 top-7">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </div>

                            {{-- Ville (combobox searchable) --}}
                            <div wire:key="arriver-ville-{{ $arriver_region_id ?? 0 }}"
                                 x-data='{
                                     q: {{ json_encode($arriverVilles->firstWhere("id", $arriver_id)?->name ?? "", JSON_HEX_TAG | JSON_HEX_APOS) }},
                                     open: false,
                                     opts: {{ json_encode($arriverVilles->values(), JSON_HEX_TAG | JSON_HEX_APOS) }},
                                     get filtered() {
                                         const s = this.q.toLowerCase();
                                         return s ? this.opts.filter(o => o.name.toLowerCase().includes(s)) : this.opts;
                                     },
                                     pick(id, name) { $wire.set("arriver_id", id); this.q = name; this.open = false; },
                                     clear() { $wire.set("arriver_id", null); this.q = ""; this.open = false; }
                                 }'
                                 @click.outside="open = false"
                                 class="relative">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Ville *</label>
                                <div class="relative" wire:loading.class="opacity-50 pointer-events-none" wire:target="arriver_region_id">
                                    <input type="text"
                                           x-model="q"
                                           @focus="if(opts.length) open = true"
                                           @input="open = true"
                                           placeholder="{{ $arriverVilles->isEmpty() ? 'Sélectionner une région' : 'Rechercher une ville...' }}"
                                           {{ $arriverVilles->isEmpty() ? 'disabled' : '' }}
                                           class="w-full px-3 py-2 pr-7 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <button type="button" x-show="q" @click="clear()"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    </button>
                                    <span wire:loading wire:target="arriver_region_id"
                                          class="absolute right-2 top-1/2 -translate-y-1/2">
                                        <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div x-show="open"
                                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                    <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">Aucune ville trouvée</p>
                                    <template x-for="o in filtered" :key="o.id">
                                        <button type="button" @click="pick(o.id, o.name)"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 transition-colors"
                                                x-text="o.name"></button>
                                    </template>
                                </div>
                                @error('arriver_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Distance + Durée --}}
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Distance (km)</label>
                            <input wire:model="distance" type="number" step="0.1" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex : 350">
                            @error('distance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée estimée</label>
                            <input wire:model="temps" type="time"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('temps') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-1">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            Annuler
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Enregistrer' : 'Créer' }}</span>
                            <span wire:loading wire:target="save">Enregistrement…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
