<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Paramètres compagnies</h2>
            <p class="text-sm text-gray-500 mt-0.5">Configurez le fonctionnement de chaque compagnie de la plateforme</p>
        </div>

        @if($compagnie && ! $readOnly)
            <button type="button" wire:click="resetAll"
                    wire:confirm="Réinitialiser TOUS les paramètres de {{ $compagnie->name }} ? Cette action est irréversible."
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 border border-red-200 hover:bg-red-50 rounded-lg transition-colors self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Tout réinitialiser
            </button>
        @endif
    </div>

    {{-- Sélecteur de compagnie --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="admin-settings-search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher une compagnie</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="admin-settings-search" wire:model.live.debounce.300ms="search" type="text" placeholder="Nom ou sigle…"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>
            <div>
                <label for="admin-settings-compagnie" class="block text-sm font-medium text-gray-700 mb-1">Compagnie configurée</label>
                <select id="admin-settings-compagnie" wire:model.live="selectedCompagnieId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">-- Choisir une compagnie --</option>
                    @foreach($compagnies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}@if($c->sigle) ({{ $c->sigle }})@endif</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($compagnie)
            <p class="text-xs text-gray-500 mt-3">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 align-middle mr-1"></span>
                {{ count($customizedKeys) }} paramètre(s) personnalisé(s) pour cette compagnie — les autres suivent les valeurs par défaut de la plateforme.
            </p>
        @endif
    </div>

    @if($compagnie)
        @include('livewire.partials.settings-form', ['accent' => 'amber'])
    @else
        <div class="bg-white rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 text-sm">Sélectionnez une compagnie pour accéder à son paramétrage.</p>
        </div>
    @endif
</div>
