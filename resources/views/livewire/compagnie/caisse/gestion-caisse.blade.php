<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Ma Caisse</h2>
        <a href="{{ route('panel.compagnie.caisses-historique') }}" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Historique
        </a>
    </div>

    @if($caisse)
        {{-- Caisse ouverte --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Fond initial</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['montant_ouverture'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Total ventes</p>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_ventes'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Montant en caisse</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['montant_courant'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Tickets vendus</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['nombre_tickets'] ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Info caisse --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Caisse ouverte</p>
                        <p class="text-xs text-gray-500">Depuis {{ $caisse->opened_at?->format('d/m/Y à H:i') }}</p>
                    </div>
                    <x-panel.badge color="green" size="xs" class="ml-auto">Active</x-panel.badge>
                </div>
                @if($caisse->note_ouverture)
                    <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
                        <p class="text-xs text-gray-400 mb-1">Note d'ouverture</p>
                        {{ $caisse->note_ouverture }}
                    </div>
                @endif
                <a href="{{ route('panel.compagnie.caisse.detail', $caisse) }}" class="mt-4 block text-center text-sm text-blue-600 hover:underline">
                    Voir les tickets de cette session →
                </a>
            </div>

            {{-- Fermeture form --}}
            <div class="bg-white rounded-xl border border-red-100 p-5 shadow-sm">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Fermer la caisse
                </h3>
                <form wire:submit="fermerCaisse" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Montant en caisse *</label>
                        <input wire:model="montant_fermeture" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Comptez l'argent en caisse...">
                        @error('montant_fermeture') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note (facultatif)</label>
                        <textarea wire:model="note_fermeture" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-300 resize-none" placeholder="Ex: RAS, tout est en ordre..."></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg transition-colors">
                        Confirmer la fermeture
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Aucune caisse ouverte --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Aucune caisse ouverte</p>
                        <p class="text-xs text-gray-500">Ouvrez votre caisse pour commencer les ventes</p>
                    </div>
                </div>
                <form wire:submit="ouvrirCaisse" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fond de caisse (F CFA) *</label>
                        <input wire:model="montant_ouverture" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="0">
                        @error('montant_ouverture') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note (facultatif)</label>
                        <textarea wire:model="note_ouverture" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" placeholder="Ex: Fond de caisse du matin..."></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-lg transition-colors">
                        Ouvrir la caisse
                    </button>
                </form>
            </div>
            <div class="bg-gray-50 rounded-xl border border-dashed border-gray-300 p-6 flex flex-col items-center justify-center text-center gap-3">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <p class="text-sm text-gray-400">Ouvrez votre caisse pour accéder aux ventes et suivre vos encaissements en temps réel.</p>
            </div>
        </div>
    @endif
</div>
