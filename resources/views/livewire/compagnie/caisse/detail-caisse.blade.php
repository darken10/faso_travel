<div>
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('panel.compagnie.caisse') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-gray-800">Détail de la caisse</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $caisse->opened_at?->format('d/m/Y à H:i') }}
                @if($caisse->closed_at) — Fermée le {{ $caisse->closed_at?->format('d/m/Y à H:i') }} @endif
            </p>
        </div>
        @php $isOpen = $caisse->isOuverte(); @endphp
        <x-panel.badge :color="$isOpen ? 'green' : 'gray'" size="xs" class="ml-auto">{{ $isOpen ? 'Ouverte' : 'Fermée' }}</x-panel.badge>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Fond initial</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($caisse->montant_ouverture, 0, ',', ' ') }} F</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Total ventes</p>
            <p class="text-xl font-bold text-blue-600">{{ number_format($caisse->totalVentes(), 0, ',', ' ') }} F</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Montant attendu</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($caisse->calculerMontantAttendu(), 0, ',', ' ') }} F</p>
        </div>
        @if(!$isOpen)
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Écart fermeture</p>
                @php $ecart = $caisse->ecart(); @endphp
                <p class="text-xl font-bold {{ $ecart >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $ecart >= 0 ? '+' : '' }}{{ number_format($ecart ?? 0, 0, ',', ' ') }} F
                </p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Nb. tickets</p>
                <p class="text-xl font-bold text-gray-800">{{ $caisse->nombreTickets() }}</p>
            </div>
        @endif
    </div>

    {{-- Tickets table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Tickets de cette session</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">N° Ticket</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Voyage</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Siège</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Créé le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    @php
                        $sc = match($ticket->statut) {
                            \App\Enums\StatutTicket::Valider => 'green',
                            \App\Enums\StatutTicket::Payer => 'blue',
                            \App\Enums\StatutTicket::EnAttente, \App\Enums\StatutTicket::Pause => 'amber',
                            default => 'red',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-700">{{ $ticket->numero_ticket }}</td>
                        <td class="px-4 py-3 text-gray-800">
                            {{ $ticket->autrePersonne?->first_name }} {{ $ticket->autrePersonne?->last_name }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $ticket->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $ticket->voyageInstance?->voyage?->trajet?->arriver?->name }}
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-600">{{ $ticket->numero_chaise ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-panel.badge :color="$sc" size="xs">{{ $ticket->statut?->value }}</x-panel.badge>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucun ticket pour cette session</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
