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
            <h2 class="text-xl font-bold text-gray-800">Voyages</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $voyages->total() }} voyages configurés</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau voyage
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher (ville départ ou arrivée)..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Trajet</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Départ/Arrivée (Gare)</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Heure</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Prix</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Jours</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($voyages as $voyage)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $voyage->trajet?->depart?->name }} → {{ $voyage->trajet?->arriver?->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $voyage->gareDepart?->name }}<br>
                            <span class="text-gray-400">→ {{ $voyage->gareArrive?->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $voyage->heure ? $voyage->heure->format('H:i') : '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format($voyage->prix ?? 0, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3">
                            @if($voyage->is_quotidient)
                                <x-panel.badge color="green" size="xs">Quotidien</x-panel.badge>
                            @elseif(is_array($voyage->days))
                                <span class="text-xs text-gray-500">{{ count($voyage->days) }} jour(s)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $voyage->statut?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openEdit({{ $voyage->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $voyage->id }})" wire:confirm="Supprimer ce voyage ?" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Aucun voyage trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($voyages->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $voyages->links() }}</div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-4" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Modifier le voyage' : 'Nouveau voyage' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    {{-- Trajet --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trajet *</label>
                        <select wire:model.live="trajet_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">-- Choisir un trajet --</option>
                            @foreach($trajets as $t)
                                <option value="{{ $t->id }}">{{ $t->depart?->name }} → {{ $t->arriver?->name }}</option>
                            @endforeach
                        </select>
                        @error('trajet_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Gares --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gare de départ *</label>
                            <select wire:model="depart_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" @if(!$trajet_id) disabled @endif>
                                <option value="">-- Gare --</option>
                                @foreach($garesDepart as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            @error('depart_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gare d'arrivée *</label>
                            <select wire:model="arrive_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" @if(!$trajet_id) disabled @endif>
                                <option value="">-- Gare --</option>
                                @foreach($garesArrive as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            @error('arrive_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Heure + Durée (read-only depuis trajet) + Statut --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heure de départ *</label>
                            <input wire:model="heure" type="time"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('heure') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée estimée</label>
                            <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-500 font-mono min-h-[38px] flex items-center">
                                {{ $temps ?: '—' }}
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Héritée du trajet</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select wire:model="statut_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Statut --</option>
                                @foreach($statuts as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Prix --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix aller *</label>
                            <input wire:model="prix" type="number" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex: 5000">
                            @error('prix') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix aller-retour</label>
                            <input wire:model="prix_aller_retour" type="number" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex: 9000">
                        </div>
                    </div>

                    {{-- Classe --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                        <select wire:model="classe_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">-- Classe (optionnel) --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Le véhicule est assigné lors de chaque exécution du voyage</p>
                    </div>

                    {{-- Calendrier --}}
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <input wire:model.live="is_quotidient" type="checkbox" id="is_quotidient" class="rounded text-blue-600">
                            <label for="is_quotidient" class="text-sm font-medium text-gray-700">Voyage quotidien</label>
                        </div>
                        @if(!$is_quotidient)
                            <div>
                                <p class="text-xs text-gray-500 mb-2">Jours de la semaine :</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($allDays as $day)
                                        <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition-colors
                                            {{ in_array($day->value, $days) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">
                                            <input type="checkbox" wire:model.live="days" value="{{ $day->value }}" class="sr-only">
                                            {{ $day->value }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
