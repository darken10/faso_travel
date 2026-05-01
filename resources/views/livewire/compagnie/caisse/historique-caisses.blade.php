<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Historique des caisses</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $caisses->total() }} sessions</p>
        </div>
        <a href="{{ route('panel.compagnie.caisse') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Ma caisse
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Caissier</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Ouverture</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Fermeture</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Fond initial</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Total ventes</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Détail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($caisses as $caisse)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $caisse->user?->name }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $caisse->opened_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $caisse->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 font-medium">{{ number_format($caisse->montant_ouverture, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-blue-600 font-semibold">
                            @if($caisse->montant_attendu)
                                {{ number_format($caisse->montant_attendu - $caisse->montant_ouverture, 0, ',', ' ') }} F
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($caisse->statut === \App\Enums\StatutCaisse::Ouverte)
                                <x-panel.badge color="green" size="xs">Ouverte</x-panel.badge>
                            @else
                                <x-panel.badge color="gray" size="xs">Fermée</x-panel.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.compagnie.caisse.detail', $caisse) }}" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                Voir
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Aucune session de caisse</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($caisses->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $caisses->links() }}</div>
        @endif
    </div>
</div>
