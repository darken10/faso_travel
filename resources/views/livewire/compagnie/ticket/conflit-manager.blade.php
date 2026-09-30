<div>
    {{-- ── En-tête ────────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Conflits d'embarquement</h1>
                <p class="text-sm text-gray-500">Validations faites hors ligne puis refusées à la synchronisation</p>
            </div>
        </div>
    </div>

    {{-- ── Compteurs ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-amber-500 uppercase tracking-wide">À traiter</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($ouverts) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-green-500 uppercase tracking-wide">Traités</p>
            <p class="text-2xl font-black text-green-600 mt-1">{{ number_format($traites) }}</p>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mb-4 text-sm text-amber-800">
        Dans chaque cas, le passager est <strong>déjà monté dans le bus</strong> : l'agent l'a embarqué sans connexion, et le serveur a
        refusé la validation en la rejouant. Constatez l'incident, puis classez-le ou régularisez la situation du passager.
    </div>

    {{-- ── Filtres ────────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <select wire:model.live="etat" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="ouverts">À traiter</option>
            <option value="traites">Traités</option>
            <option value="tous">Tous</option>
        </select>
        <select wire:model.live="motif" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Tous les motifs</option>
            @foreach($motifs as $m)
                <option value="{{ $m->value }}">{{ $m->label() }}</option>
            @endforeach
        </select>
        <input wire:model.live="dateFrom" type="date" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
        <input wire:model.live="dateTo" type="date" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
        <button wire:click="resetFilters" class="text-sm border border-gray-200 rounded-lg px-3 py-2 text-gray-600 hover:bg-gray-50">Réinitialiser</button>
    </div>

    {{-- ── Liste ──────────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Embarqué le</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Passager</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Voyage</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Agent</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Motif du refus</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">État</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($conflits as $c)
                    @php
                        $ticket  = $c->ticket;
                        $nom     = $ticket ? ($ticket->autre_personne_id ? $ticket->autre_personne?->name : $ticket->user?->name) : null;
                        $trajet  = $ticket?->voyageInstance?->voyage?->trajet;
                    @endphp
                    <tr wire:key="conflit-{{ $c->id }}" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $c->client_created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if($ticket)
                                <p class="font-medium text-gray-800">{{ $nom ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $ticket->numero_ticket }}@if($ticket->numero_chaise) · siège {{ $ticket->numero_chaise }}@endif</p>
                            @else
                                <p class="font-medium text-gray-500">Ticket inconnu</p>
                                <p class="text-xs text-gray-400">QR non reconnu par le serveur</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($trajet)
                                {{ $trajet->depart?->name }} → {{ $trajet->arriver?->name }}
                                <p class="text-xs text-gray-400">{{ $ticket->voyageInstance->date?->format('d/m/Y') }}</p>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $c->agent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-md text-xs font-semibold {{ $c->error_code === \App\Enums\SyncErrorCode::AlreadyValidated ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $c->error_code?->label() ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($c->resolved_at)
                                <span class="inline-flex px-2 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700">{{ $c->resolution?->label() }}</span>
                                <p class="text-xs text-gray-400 mt-1">{{ $c->resolvedBy?->name }} · {{ $c->resolved_at->format('d/m/Y') }}</p>
                                @if($c->resolution_note)
                                    <p class="text-xs text-gray-500 mt-1 max-w-xs">{{ $c->resolution_note }}</p>
                                @endif
                            @else
                                <span class="inline-flex px-2 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800">À traiter</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="openResolve({{ $c->id }})" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                {{ $c->resolved_at ? 'Modifier' : 'Traiter' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            @if($etat === 'ouverts')
                                Aucun conflit à traiter.
                            @else
                                Aucun conflit pour ces critères.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">{{ $conflits->links() }}</div>
    </div>

    {{-- ── Traitement ─────────────────────────────────────────────────────────── --}}
    @if($showResolveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeResolve">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Traiter ce conflit</h2>

                <div class="space-y-2 mb-4">
                    @foreach($issues as $issue)
                        <label class="flex items-center gap-3 border rounded-lg px-3 py-2.5 cursor-pointer {{ $resolution === $issue->value ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="resolution" value="{{ $issue->value }}" class="text-blue-600">
                            <span class="text-sm font-medium text-gray-700">{{ $issue->label() }}</span>
                        </label>
                    @endforeach
                    @error('resolution') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="block text-sm font-medium text-gray-600 mb-1">Note (facultatif)</label>
                <textarea wire:model="note" rows="3" maxlength="1000"
                          class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                          placeholder="Ce qui a été constaté, décision prise..."></textarea>
                @error('note') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <div class="flex justify-end gap-2 mt-5">
                    <button wire:click="closeResolve" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 rounded-lg">Annuler</button>
                    <button wire:click="resolve" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Enregistrer</button>
                </div>
            </div>
        </div>
    @endif
</div>
