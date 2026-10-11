<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Bordereaux de versement</h1>
            <p class="text-sm text-gray-500 mt-0.5">Remise des caisses : agent, finance, puis comptabilité.</p>
        </div>
        <a href="{{ route('panel.compagnie.caisse') }}" class="text-sm text-blue-600 hover:underline">Ma caisse</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">N° bordereau</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Caissier</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Émis le</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Montant déclaré</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bordereaux as $bordereau)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-700">{{ $bordereau->numero }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $bordereau->emetteur?->name ?? $bordereau->caisse?->user?->name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $bordereau->emitted_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format($bordereau->montant_declare, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3"><x-panel.badge :color="$bordereau->statut->color()" size="xs">{{ $bordereau->statut->label() }}</x-panel.badge></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @if(auth()->user()->peutOuObserve('caisse.bordereau.export', $bordereau))
                                    <button wire:click="exportPdf({{ $bordereau->id }})" class="text-xs text-gray-600 hover:text-gray-900">PDF</button>
                                @endif
                                @if($bordereau->statut === \App\Enums\StatutBordereauVersement::Emis && auth()->user()->peutOuObserve('caisse.bordereau.receive', $bordereau))
                                    <button wire:click="ouvrirAction('reception', {{ $bordereau->id }})" class="px-2.5 py-1 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Réceptionner</button>
                                @endif
                                @if($bordereau->statut === \App\Enums\StatutBordereauVersement::RecuFinance && auth()->user()->peutOuObserve('caisse.bordereau.transfer', $bordereau))
                                    <button wire:click="ouvrirAction('transmission', {{ $bordereau->id }})" class="px-2.5 py-1 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-lg">Transmettre</button>
                                @endif
                                @if($bordereau->statut === \App\Enums\StatutBordereauVersement::TransmisComptabilite && auth()->user()->peutOuObserve('caisse.bordereau.account', $bordereau))
                                    <button wire:click="ouvrirAction('comptabilisation', {{ $bordereau->id }})" class="px-2.5 py-1 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Comptabiliser</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucun bordereau de versement.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($bordereaux->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $bordereaux->links() }}</div>
        @endif
    </div>

    @if($actionEnCours)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.annulerAction()">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="annulerAction"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg my-10">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">{{ match($actionEnCours) { 'reception' => 'Prendre en charge le versement', 'transmission' => 'Transmettre à la comptabilité', 'comptabilisation' => 'Comptabiliser le versement' } }}</h2>
                    <button wire:click="annulerAction" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form wire:submit="confirmerAction" class="px-6 py-5 space-y-4">
                    @if($actionEnCours === 'reception' || $actionEnCours === 'comptabilisation')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant compté (F CFA) *</label>
                            <input wire:model="montant" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('montant') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    @if($actionEnCours === 'transmission')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Référence comptable</label>
                            <input wire:model="reference" type="text" maxlength="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex. BC-2026-001">
                            @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                        <textarea wire:model="note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" placeholder="Obligatoire en cas d'écart constaté."></textarea>
                        @error('note') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('action') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="annulerAction" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
