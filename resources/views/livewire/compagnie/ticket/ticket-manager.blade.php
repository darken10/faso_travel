<div>

    {{-- ── Stats rapides ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-black text-gray-800 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-blue-400 uppercase tracking-wide">Payés</p>
            <p class="text-2xl font-black text-blue-600 mt-1">{{ number_format($stats['payes']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-green-400 uppercase tracking-wide">Validés</p>
            <p class="text-2xl font-black text-green-600 mt-1">{{ number_format($stats['valides']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-red-400 uppercase tracking-wide">Bloqués</p>
            <p class="text-2xl font-black text-red-500 mt-1">{{ number_format($stats['bloques']) }}</p>
        </div>
        <div class="lg:col-span-1 col-span-2 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border border-green-100 shadow-sm p-4">
            <p class="text-xs font-medium text-green-500 uppercase tracking-wide">Recettes validées</p>
            <p class="text-xl font-black text-green-700 mt-1">{{ number_format($stats['recette'], 0, ',', ' ') }} F</p>
        </div>
    </div>

    {{-- ── En-tête & bouton vente ─────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Tickets</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $tickets->total() }} résultat{{ $tickets->total() > 1 ? 's' : '' }}</p>
        </div>
        <a href="{{ route('panel.compagnie.vente-ticket') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            Vendre un ticket
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Filtres --}}
        <div class="px-4 py-3 border-b border-gray-100 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Recherche --}}
                <div class="relative lg:col-span-2">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search"
                           type="text"
                           placeholder="N° ticket, nom, téléphone..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                {{-- Filtre statut --}}
                <select wire:model.live="statutFilter"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $s)
                        <option value="{{ $s->value }}">{{ $s->value }}</option>
                    @endforeach
                </select>

                {{-- Nombre par page --}}
                <select wire:model.live="perPage"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="15">15 par page</option>
                    <option value="25">25 par page</option>
                    <option value="50">50 par page</option>
                    <option value="100">100 par page</option>
                </select>
            </div>

            {{-- Filtre dates --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-medium text-gray-500 whitespace-nowrap">Du</label>
                    <input wire:model.live="dateFrom" type="date"
                           class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-medium text-gray-500 whitespace-nowrap">Au</label>
                    <input wire:model.live="dateTo" type="date"
                           class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                @if($hasFilters)
                <button wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Réinitialiser les filtres
                </button>
                @endif
            </div>
        </div>

        {{-- Tableau --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">N° Ticket</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Client</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Voyage</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Date voyage</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Siège</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Montant</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Statut</th>
                        <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tickets as $ticket)
                        @php
                            $statutColor = match($ticket->statut) {
                                \App\Enums\StatutTicket::Valider => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
                                \App\Enums\StatutTicket::Payer => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                                \App\Enums\StatutTicket::Pause => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                                \App\Enums\StatutTicket::Bloquer, \App\Enums\StatutTicket::Annuler => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
                                default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
                            };
                            $montant = $ticket->payements->sum('montant');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="ticket-{{ $ticket->id }}">

                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-1 rounded">{{ $ticket->numero_ticket }}</span>
                            </td>

                            <td class="px-4 py-3">
                                @if($ticket->autre_personne)
                                    <p class="font-medium text-gray-800 text-sm">{{ $ticket->autre_personne->first_name }} {{ $ticket->autre_personne->last_name }}</p>
                                    <p class="text-xs text-gray-400">Via {{ $ticket->user?->first_name ?? 'inconnu' }}</p>
                                @elseif($ticket->user)
                                    <p class="font-medium text-gray-800 text-sm">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $ticket->user->phone ?? $ticket->user->email }}</p>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">
                                {{ $ticket->voyageInstance?->voyage?->trajet?->depart?->name }}
                                <span class="text-gray-400 mx-1">→</span>
                                {{ $ticket->voyageInstance?->voyage?->trajet?->arriver?->name }}
                                <br>
                                <span class="text-gray-400">{{ $ticket->voyageInstance?->heure ? \Carbon\Carbon::parse($ticket->voyageInstance->heure)->format('H:i') : '' }}</span>
                            </td>

                            <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">
                                {{ $ticket->date ? \Carbon\Carbon::parse($ticket->date)->format('d/m/Y') : '—' }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($ticket->numero_chaise)
                                    <span class="font-mono text-xs font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ $ticket->numero_chaise }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">
                                @if($montant > 0)
                                    {{ number_format($montant, 0, ',', ' ') }} F
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statutColor['bg'] }} {{ $statutColor['text'] }}">
                                    {{ $ticket->statut?->value ?? '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">

                                    @if($ticket->statut === \App\Enums\StatutTicket::Payer)
                                        <button wire:click="openConfirm({{ $ticket->id }}, 'valider')"
                                                title="Valider ce ticket"
                                                class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button wire:click="openConfirm({{ $ticket->id }}, 'bloquer')"
                                                title="Bloquer ce ticket"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    @endif

                                    @if($ticket->statut === \App\Enums\StatutTicket::Pause)
                                        <button wire:click="openConfirm({{ $ticket->id }}, 'valider')"
                                                title="Valider retour"
                                                class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button wire:click="openConfirm({{ $ticket->id }}, 'activer')"
                                                title="Réactiver le ticket"
                                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                    @endif

                                    @if($ticket->statut === \App\Enums\StatutTicket::Bloquer)
                                        <button wire:click="openConfirm({{ $ticket->id }}, 'activer')"
                                                title="Réactiver le ticket"
                                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                <p class="text-gray-400 text-sm font-medium">Aucun ticket trouvé</p>
                                @if($hasFilters)
                                    <button wire:click="resetFilters" class="mt-2 text-xs text-blue-500 hover:underline">Réinitialiser les filtres</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Affichage {{ $tickets->firstItem() }} – {{ $tickets->lastItem() }} sur {{ $tickets->total() }}
                </p>
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

    {{-- ── Modal confirmation action ticket ──────────────────────────────────── --}}
    @if($showConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4"
         x-data
         x-on:keydown.escape.window="$wire.showConfirmModal = false">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
             wire:click="$set('showConfirmModal', false)"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 animate-in fade-in zoom-in-95 duration-150">

            {{-- Icône --}}
            <div class="flex items-center justify-center mb-4">
                @if($confirmAction === 'valider')
                    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @elseif($confirmAction === 'bloquer')
                    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                @else
                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Titre & message --}}
            <h3 class="text-lg font-bold text-gray-900 text-center mb-2">{{ $confirmTitle }}</h3>
            <p class="text-sm text-gray-500 text-center mb-6 leading-relaxed">{{ $confirmMessage }}</p>

            {{-- Boutons --}}
            <div class="flex gap-3">
                <button type="button"
                        wire:click="$set('showConfirmModal', false)"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Annuler
                </button>
                <button type="button"
                        wire:click="executeConfirm"
                        wire:loading.attr="disabled"
                        wire:target="executeConfirm"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl transition-colors disabled:opacity-60 {{ $confirmButtonClass }}">
                    <span wire:loading.remove wire:target="executeConfirm">{{ $confirmButtonLabel }}</span>
                    <span wire:loading wire:target="executeConfirm" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        En cours…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
