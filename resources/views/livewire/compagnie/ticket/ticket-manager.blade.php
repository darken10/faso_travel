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
             class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Tickets</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $tickets->total() }} tickets</p>
        </div>
        <a href="{{ route('panel.compagnie.vente-ticket') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            Vendre un ticket
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="N° ticket, nom client..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <select wire:model.live="statutFilter" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Tous les statuts</option>
                @foreach($statuts as $s)
                    <option value="{{ $s->value }}">{{ $s->value }}</option>
                @endforeach
            </select>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">N° Ticket</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Voyage</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Siège</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Type</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    @php
                        $sc = match($ticket->statut) {
                            \App\Enums\StatutTicket::Valider => 'green',
                            \App\Enums\StatutTicket::Payer => 'blue',
                            \App\Enums\StatutTicket::EnAttente, \App\Enums\StatutTicket::Pause => 'amber',
                            \App\Enums\StatutTicket::Bloquer, \App\Enums\StatutTicket::Annuler,
                            \App\Enums\StatutTicket::Refuser, \App\Enums\StatutTicket::Suspendre => 'red',
                            default => 'gray',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-700">{{ $ticket->numero_ticket }}</td>
                        <td class="px-4 py-3">
                            @if($ticket->autrePersonne)
                                <span class="font-medium text-gray-800">{{ $ticket->autrePersonne->first_name }} {{ $ticket->autrePersonne->last_name }}</span>
                            @elseif($ticket->user)
                                <span class="font-medium text-gray-800">{{ $ticket->user->name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $ticket->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $ticket->voyageInstance?->voyage?->trajet?->arriver?->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $ticket->date ? \Carbon\Carbon::parse($ticket->date)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono">{{ $ticket->numero_chaise ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-panel.badge color="blue" size="xs">{{ $ticket->type?->value ?? $ticket->type }}</x-panel.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-panel.badge :color="$sc" size="xs">{{ $ticket->statut?->value ?? $ticket->statut }}</x-panel.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($ticket->statut === \App\Enums\StatutTicket::Payer)
                                    <button wire:click="valider({{ $ticket->id }})" title="Valider" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button wire:click="bloquer({{ $ticket->id }})" title="Bloquer" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </button>
                                @endif
                                @if($ticket->statut === \App\Enums\StatutTicket::Pause)
                                    <button wire:click="valider({{ $ticket->id }})" title="Valider retour" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button wire:click="activer({{ $ticket->id }})" title="Réactiver" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                @endif
                                @if($ticket->statut === \App\Enums\StatutTicket::Bloquer)
                                    <button wire:click="activer({{ $ticket->id }})" title="Réactiver" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Aucun ticket trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
