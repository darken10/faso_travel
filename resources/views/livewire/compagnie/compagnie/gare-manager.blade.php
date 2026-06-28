<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Gares</h1>
                <p class="text-sm text-gray-500">{{ $gares->total() }} gare{{ $gares->total() > 1 ? 's' : '' }} enregistrée{{ $gares->total() > 1 ? 's' : '' }}</p>
            </div>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvelle gare
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text"
                       placeholder="Rechercher une gare..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Ville</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Coordonnées</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Défaut</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Documents</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($gares as $gare)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $gare->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $gare->ville?->name }}</td>
                        <td class="px-4 py-3">{{ $gare->statut?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">
                            {{ $gare->lat && $gare->lng ? $gare->lat . ', ' . $gare->lng : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($gare->is_default)
                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="openDocPanel({{ $gare->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-indigo-100 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $gare->documents_count ?: 'Docs' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openEdit({{ $gare->id }})"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @if(!$gare->is_default)
                                    <button wire:click="delete({{ $gare->id }})" wire:confirm="Supprimer cette gare ?"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Aucune gare trouvée</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($gares->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $gares->links() }}</div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto"
             x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl my-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Modifier la gare' : 'Nouvelle gare' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="px-6 py-5 space-y-4">

                    {{-- Nom + Statut --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la gare *</label>
                            <input wire:model="name" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex : Gare de Ouaga">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select wire:model="statut_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Aucun --</option>
                                @foreach($statuts as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Sélection géographique en cascade --}}
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Localisation</p>

                        <div class="grid grid-cols-2 gap-3">
                            {{-- Pays --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pays *</label>
                                <select wire:model.live="pays_id"
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
                                <select wire:model.live="region_id"
                                        {{ !$pays_id ? 'disabled' : '' }}
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    <option value="">{{ $pays_id ? '-- Région --' : 'Sélectionner un pays' }}</option>
                                    @foreach($regions as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <span wire:loading wire:target="pays_id" class="absolute right-8 top-7">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        {{-- Ville (combobox searchable) — même pattern que TrajetManager --}}
                        <div wire:key="gare-ville-{{ $region_id ?? 0 }}"
                             x-data='{
                                 q: {{ json_encode($villes->firstWhere("id", $ville_id)?->name ?? "", JSON_HEX_TAG | JSON_HEX_APOS) }},
                                 open: false,
                                 opts: {{ json_encode($villes->values(), JSON_HEX_TAG | JSON_HEX_APOS) }},
                                 get filtered() {
                                     const s = this.q.toLowerCase();
                                     return s ? this.opts.filter(o => o.name.toLowerCase().includes(s)) : this.opts;
                                 },
                                 pick(id, name) { $wire.set("ville_id", id); this.q = name; this.open = false; },
                                 clear() { $wire.set("ville_id", null); this.q = ""; this.open = false; }
                             }'
                             @click.outside="open = false"
                             class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ville *</label>
                            <div class="relative" wire:loading.class="opacity-50 pointer-events-none" wire:target="region_id">
                                <input type="text"
                                       x-model="q"
                                       @focus="if(opts.length) open = true"
                                       @input="open = true"
                                       placeholder="{{ $villes->isEmpty() ? 'Sélectionner une région' : 'Rechercher une ville...' }}"
                                       {{ $villes->isEmpty() ? 'disabled' : '' }}
                                       class="w-full px-3 py-2 pr-7 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
                                <button type="button" x-show="q" @click="clear()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <span wire:loading wire:target="region_id" class="absolute right-2 top-1/2 -translate-y-1/2">
                                    <svg class="animate-spin w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </div>
                            <div x-show="open"
                                 class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-44 overflow-y-auto">
                                <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">Aucune ville trouvée</p>
                                <template x-for="o in filtered" :key="o.id">
                                    <button type="button" @click="pick(o.id, o.name)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 transition-colors"
                                            x-text="o.name"></button>
                                </template>
                            </div>
                            @error('ville_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Carte + Coordonnées --}}
                    <div x-data="{ locating: false, geoError: '' }" class="space-y-2">

                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Position sur la carte</p>
                            <button type="button" :disabled="locating"
                                    @click="
                                        if (!navigator.geolocation) { geoError = 'Géolocalisation non supportée.'; return; }
                                        locating = true; geoError = '';
                                        navigator.geolocation.getCurrentPosition(
                                            function(p) { locating = false; window.gareMapPick(p.coords.latitude, p.coords.longitude, true); },
                                            function(e) { locating = false; geoError = e.code === 1 ? 'Permission refusée.' : 'Position indisponible.'; },
                                            { enableHighAccuracy: true, timeout: 10000 }
                                        );
                                    "
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-wait">
                                <template x-if="!locating">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </template>
                                <template x-if="locating">
                                    <svg class="animate-spin w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </template>
                                <span x-text="locating ? 'Localisation...' : 'Ma position'"></span>
                            </button>
                        </div>

                        <p class="text-xs text-gray-400">Cliquer sur la carte pour placer le marqueur</p>

                        <div id="gare-map-container" wire:ignore
                             class="h-56 w-full rounded-lg border border-gray-200 cursor-crosshair"></div>

                        <p x-show="geoError" x-text="geoError" class="text-xs text-red-500 mt-1"></p>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Latitude *</label>
                                <input wire:model="lat" type="number" step="any"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-400"
                                       placeholder="12.3456">
                                @error('lat') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Longitude *</label>
                                <input wire:model="lng" type="number" step="any"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-400"
                                       placeholder="-1.5678">
                                @error('lng') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
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

@script
<script>
    window._gareMap    = null;
    window._gareMarker = null;

    window.gareMapPick = function(lat, lng, panTo) {
        if (!window._gareMap) return;
        if (window._gareMarker) window._gareMap.removeLayer(window._gareMarker);
        window._gareMarker = L.marker([lat, lng]).addTo(window._gareMap);
        if (panTo) window._gareMap.setView([lat, lng], 15);
        $wire.lat = parseFloat(lat).toFixed(6);
        $wire.lng = parseFloat(lng).toFixed(6);
    };

    $wire.on('gare-modal-opened', function(params) {
        var data = Array.isArray(params) ? params[0] : params;
        var lat  = (data && data.lat != null) ? data.lat : null;
        var lng  = (data && data.lng != null) ? data.lng : null;

        setTimeout(function() {
            var el = document.getElementById('gare-map-container');
            if (!el) return;

            if (window._gareMap) {
                window._gareMap.remove();
                window._gareMap    = null;
                window._gareMarker = null;
            }

            var hasCoords = lat !== null && lng !== null;
            var center    = hasCoords ? [lat, lng] : [12.3714277, -1.5196603];

            window._gareMap = L.map(el).setView(center, hasCoords ? 13 : 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(window._gareMap);

            if (hasCoords) {
                window._gareMarker = L.marker([lat, lng]).addTo(window._gareMap);
            }

            window._gareMap.on('click', function(e) {
                window.gareMapPick(e.latlng.lat, e.latlng.lng, false);
            });

            setTimeout(function() {
                if (window._gareMap) window._gareMap.invalidateSize();
            }, 100);
        }, 200);
    });
</script>
@endscript
