{{-- Rafraîchissement automatique : le panneau n'a pas de WebSocket. Le brouillon en cours de saisie est conservé. --}}
<div wire:poll.10s>
    {{-- ── En-tête ────────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Messages</h1>
                <p class="text-sm text-gray-500">
                    @if($totalNonLus > 0)
                        {{ $totalNonLus }} conversation{{ $totalNonLus > 1 ? 's' : '' }} en attente de réponse
                    @else
                        Conversations avec vos clients
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-3" style="min-height: 32rem;">

        {{-- ── Liste des conversations ────────────────────────────────────────── --}}
        <div class="border-r border-gray-100 flex flex-col {{ $selected ? 'hidden lg:flex' : 'flex' }}">
            <div class="p-3 border-b border-gray-100 space-y-2">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un client…"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <div class="flex gap-2">
                    @foreach(['tous' => 'Toutes', 'non_lus' => 'Non lues'] as $valeur => $libelle)
                        <button wire:click="$set('filtre', '{{ $valeur }}')"
                                class="px-3 py-1 rounded-full text-xs font-semibold {{ $filtre === $valeur ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-50" style="max-height: 34rem;">
                @forelse($conversations as $c)
                    @php $nonLu = $c->unread_count_agent > 0; @endphp
                    <button wire:key="conv-{{ $c->id }}" wire:click="select('{{ $c->id }}')"
                            class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors {{ $selected?->id === $c->id ? 'bg-blue-50' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm truncate {{ $nonLu ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }}">{{ $c->client?->name ?? 'Client supprimé' }}</p>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $c->last_message_at?->format('d/m H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="text-xs truncate {{ $nonLu ? 'text-gray-700' : 'text-gray-400' }}">{{ $c->last_message ?? 'Aucun message' }}</p>
                            @if($nonLu)
                                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-blue-600 text-white text-xs font-bold">{{ $c->unread_count_agent }}</span>
                            @endif
                        </div>
                    </button>
                @empty
                    <p class="px-4 py-12 text-center text-sm text-gray-400">
                        {{ $search !== '' || $filtre === 'non_lus' ? 'Aucune conversation pour ce filtre.' : 'Aucun message de vos clients pour le moment.' }}
                    </p>
                @endforelse

                @if($aPlus)
                    <button wire:click="loadMore" class="w-full py-3 text-sm font-semibold text-blue-600 hover:bg-gray-50">Charger plus</button>
                @endif
            </div>
        </div>

        {{-- ── Fil de discussion ──────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 flex flex-col {{ $selected ? 'flex' : 'hidden lg:flex' }}">
            @if($selected)
                <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
                    <button wire:click="back" class="lg:hidden text-gray-500 hover:text-gray-800" aria-label="Retour aux conversations">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $selected->client?->name ?? 'Client supprimé' }}</p>
                        <p class="text-xs text-gray-400">Conversation avec votre compagnie</p>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
                     style="max-height: 26rem;"
                     wire:key="thread-{{ $selected->id }}-{{ $messages->count() }}"
                     x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                    @forelse($messages as $m)
                        @php $duClient = (int) $m->sender_id === (int) $selected->client_id; @endphp
                        <div wire:key="msg-{{ $m->id }}" class="flex {{ $duClient ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $duClient ? 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm' : 'bg-blue-600 text-white rounded-br-sm' }}">
                                @unless($duClient)
                                    <p class="text-xs font-semibold opacity-80 mb-0.5">{{ $m->sender?->name ?? 'Équipe' }}</p>
                                @endunless
                                <p class="whitespace-pre-line break-words">{{ $m->message }}</p>
                                <p class="text-[11px] mt-1 {{ $duClient ? 'text-gray-400' : 'text-blue-100' }}">{{ $m->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-400 py-8">Aucun message dans cette conversation.</p>
                    @endforelse
                </div>

                <form wire:submit="send" class="p-3 border-t border-gray-100">
                    <div class="flex items-end gap-2">
                        <textarea wire:model="reponse" rows="2" maxlength="{{ $maxLength }}" placeholder="Votre réponse… (Entrée pour envoyer, Maj+Entrée pour un retour à la ligne)"
                                  @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send() }"
                                  class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"></textarea>
                        <button type="submit" wire:loading.attr="disabled" wire:target="send"
                                class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 rounded-lg">
                            Envoyer
                        </button>
                    </div>
                    @error('reponse') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </form>
            @else
                <div class="flex-1 flex items-center justify-center text-sm text-gray-400 p-8">
                    Sélectionnez une conversation pour lire et répondre.
                </div>
            @endif
        </div>
    </div>
</div>
