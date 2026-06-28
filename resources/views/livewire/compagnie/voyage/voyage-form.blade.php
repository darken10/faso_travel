<div class="max-w-4xl mx-auto px-4 py-6">

    {{-- En-tête --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('panel.compagnie.voyages') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-800">{{ $editingId ? 'Modifier le voyage' : 'Nouveau voyage' }}</h1>
            <p class="text-sm text-gray-500">Configurez l'itinéraire, les tarifs, la validité et l'affectation par défaut.</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- Itinéraire --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Itinéraire</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trajet *</label>
                <select wire:model.live="trajet_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Sélectionner un trajet</option>
                    @foreach ($trajets as $t)
                        <option value="{{ $t->id }}">{{ $t->depart?->name }} → {{ $t->arriver?->name }}</option>
                    @endforeach
                </select>
                @error('trajet_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gare de départ *</label>
                    <select wire:model="depart_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Sélectionner</option>
                        @foreach ($garesDepart as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('depart_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gare d'arrivée *</label>
                    <select wire:model="arrive_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Sélectionner</option>
                        @foreach ($garesArrive as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('arrive_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de départ *</label>
                    <input wire:model="heure" type="time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('heure') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durée estimée</label>
                    <input value="{{ $temps }}" type="time" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500">
                    <span class="text-xs text-gray-400">Héritée du trajet</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select wire:model="statut_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">—</option>
                        @foreach ($statuts as $s)
                            <option value="{{ $s->id }}">{{ $s->name ?? $s->libelle ?? $s->id }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Tarifs & capacité --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Tarifs & capacité</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix aller *</label>
                    <input wire:model="prix" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('prix') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix aller-retour</label>
                    <input wire:model="prix_aller_retour" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('prix_aller_retour') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de places par défaut *</label>
                    <input wire:model="nb_pace" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <span class="text-xs text-gray-400">Utilisé pour les instances générées automatiquement</span>
                    @error('nb_pace') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                    <select wire:model="classe_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">—</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Affectation par défaut --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Affectation par défaut</h2>
            <p class="text-xs text-gray-500 -mt-2">Ce véhicule et ce chauffeur sont automatiquement affectés aux instances générées.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule</label>
                    <select wire:model="care_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Aucun —</option>
                        @foreach ($cares as $car)
                            <option value="{{ $car->id }}">{{ $car->immatrculation }} ({{ $car->number_place }} places)</option>
                        @endforeach
                    </select>
                    @error('care_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chauffeur</label>
                    <select wire:model="chauffer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Aucun —</option>
                        @foreach ($chauffeurs as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->first_name }} {{ $ch->last_name }}</option>
                        @endforeach
                    </select>
                    @error('chauffer_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Validité & récurrence --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Validité & récurrence</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'entrée en vigueur *</label>
                    <input wire:model="date_debut" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('date_debut') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input wire:model="date_fin" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <span class="text-xs text-gray-400">Laisser vide = validité illimitée</span>
                    @error('date_fin') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model.live="is_quotidient" type="checkbox" class="w-4 h-4 rounded text-blue-600">
                <span class="text-sm text-gray-700">Voyage quotidien (tous les jours)</span>
            </label>

            @unless ($is_quotidient)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jours de circulation</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($allDays as $day)
                            @continue($day->value === \App\Enums\JoursSemain::ToutLesJours->value)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-200 rounded-lg cursor-pointer text-sm
                                {{ in_array($day->value, $days) ? 'bg-blue-50 border-blue-400 text-blue-700' : 'text-gray-600' }}">
                                <input type="checkbox" wire:model.live="days" value="{{ $day->value }}" class="w-4 h-4 rounded text-blue-600">
                                {{ $day->value }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endunless
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.compagnie.voyages') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Annuler</a>
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm">
                {{ $editingId ? 'Enregistrer' : 'Créer le voyage' }}
            </button>
        </div>
    </form>
</div>
