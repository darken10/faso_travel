<div class="space-y-6">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Codes promo</h1>
                <p class="text-sm text-gray-500">{{ $promos->total() }} code{{ $promos->total() > 1 ? 's' : '' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportPdf" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Rapport PDF
            </button>
            <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouveau code
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un code…" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Code</th>
                    <th class="px-4 py-3 text-left font-medium">Réduction</th>
                    <th class="px-4 py-3 text-left font-medium">Validité</th>
                    <th class="px-4 py-3 text-left font-medium">Utilisations</th>
                    <th class="px-4 py-3 text-left font-medium">Statut</th>
                    <th class="px-4 py-3 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($promos as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $p->code }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($p->type === 'pourcentage') -{{ $p->valeur }}% @else -{{ number_format($p->valeur, 0, ',', ' ') }} F @endif
                            @if($p->min_montant) <span class="text-xs text-gray-400">(min {{ number_format($p->min_montant, 0, ',', ' ') }} F)</span> @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $p->date_debut?->format('d/m/Y') ?? '—' }} → {{ $p->date_fin?->format('d/m/Y') ?? '∞' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $p->used_count }}{{ $p->usage_limit ? ' / ' . $p->usage_limit : '' }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $p->id }})" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $p->active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $p->active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ $p->active ? 'Actif' : 'Inactif' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('panel.compagnie.promos.show', ['promoId' => $p->id]) }}" title="Voir les bénéficiaires" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button wire:click="openEdit({{ $p->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $p->id }})" wire:confirm="Supprimer ce code promo ?" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Aucun code promo.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($promos->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $promos->links() }}</div>
        @endif
    </div>

    {{-- Modale --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4">{{ $editingId ? 'Modifier le code' : 'Nouveau code promo' }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                        <input wire:model="code" type="text" placeholder="Ex: NOEL25" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase">
                        @error('code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                            <select wire:model.live="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="pourcentage">Pourcentage (%)</option>
                                <option value="montant">Montant fixe (F)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valeur *</label>
                            <input wire:model="valeur" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="{{ $type === 'pourcentage' ? '25' : '1000' }}">
                            @error('valeur') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Début</label>
                            <input wire:model="date_debut" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                            <input wire:model="date_fin" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('date_fin') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Limite d'utilisations</label>
                            <input wire:model="usage_limit" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Illimité">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant min. (F)</label>
                            <input wire:model="min_montant" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Aucun">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input wire:model="active" type="checkbox" class="w-4 h-4 rounded text-blue-600">
                        <span class="text-sm text-gray-700">Actif</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm text-gray-600">Annuler</button>
                    <button wire:click="save" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ $editingId ? 'Enregistrer' : 'Créer' }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
