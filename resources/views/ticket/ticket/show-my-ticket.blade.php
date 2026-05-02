@extends("layout")

@section('title','Mon Ticket')

@section('content')

<div class="max-w-lg mx-auto space-y-4">

    {{-- Ticket Card --}}
    <div class="card relative overflow-hidden">
        {{-- Decorative top border --}}
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-500 via-accent-500 to-primary-500"></div>

        {{-- Header: user info + menu --}}
        <div class="flex items-center justify-between pt-2">
            <div class="flex items-center gap-3">
                <img class="w-10 h-10 rounded-full ring-2 ring-surface-200 dark:ring-surface-600 object-cover"
                     src="{{ asset(Auth::user()->profileUrl ? Auth::user()->profileUrl : 'icon/user1.png') }}"
                     alt="User">
                <div>
                    <p class="text-sm font-semibold text-surface-900 dark:text-white">
                        @if($ticket?->is_my_ticket or $ticket?->transferer_a_user_id === auth()->user()->id)
                            {{ $ticket?->user?->name }}
                        @else
                            {{ $ticket?->autre_personne?->name }}
                        @endif
                    </p>
                    <p class="text-xs text-surface-400 dark:text-surface-500">{{ $ticket->created_at->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Actions dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors">
                    <svg class="w-5 h-5 text-surface-500" fill="currentColor" viewBox="0 0 4 15"><path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-surface-800 rounded-xl shadow-elevated border border-surface-200 dark:border-surface-700 z-50 overflow-hidden">
                    <div class="py-1">
                        @if($ticket?->statut === \App\Enums\StatutTicket::Payer or $ticket?->statut === \App\Enums\StatutTicket::Pause)
                            <a href="{{ route('ticket.editTicket',$ticket) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-surface-700 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors">
                                <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                Modifier
                            </a>
                        @endif

                        @if($ticket?->statut === \App\Enums\StatutTicket::Payer)
                            <a href="#" data-modal-target="modal-pause" data-modal-toggle="modal-pause" @click="open = false" class="flex items-center gap-2 px-4 py-2.5 text-sm text-warning-600 dark:text-warning-500 hover:bg-warning-50 dark:hover:bg-warning-500/10 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                                Mettre en pause
                            </a>
                        @elseif($ticket?->statut === \App\Enums\StatutTicket::Pause)
                            <a href="{{ route('ticket.editTicket',$ticket) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-success-600 dark:text-success-500 hover:bg-success-50 dark:hover:bg-success-500/10 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                                Réactiver
                            </a>
                        @elseif($ticket?->statut === \App\Enums\StatutTicket::Bloquer)
                            <a href="#" data-modal-target="popup-modal-ractive" data-modal-toggle="popup-modal-ractive" @click="open = false" class="flex items-center gap-2 px-4 py-2.5 text-sm text-success-600 hover:bg-success-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                Débloquer
                            </a>
                        @elseif($ticket?->statut === \App\Enums\StatutTicket::EnAttente)
                            <a href="@if(!$ticket?->is_my_ticket and $ticket->autre_personne instanceof \App\Models\Ticket\AutrePersonne){{ route('voyage.payerAutrePersonneTicket',$ticket) }}@else{{ route('voyage.instance.acheter',$ticket->voyageInstance) }}@endif"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                Payer
                            </a>
                        @endif

                        @if($ticket?->statut === \App\Enums\StatutTicket::Payer or $ticket?->statut === \App\Enums\StatutTicket::Pause)
                            <a href="{{ route('ticket.transferer-ticket-to-other-user',$ticket) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-surface-700 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors">
                                <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                Transférer
                            </a>
                        @endif

                        @if($ticket?->statut === \App\Enums\StatutTicket::Payer)
                            <div class="border-t border-surface-200 dark:border-surface-700 my-1"></div>
                            <a href="{{ route('ticket.regenerer',$ticket) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-surface-700 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors">
                                <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                Régénérer
                            </a>
                        @endif

                        @if($ticket?->statut === \App\Enums\StatutTicket::Valider)
                            <a href="#" class="flex items-center gap-2 px-4 py-2.5 text-sm text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-500/10 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                Supprimer
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Ticket route visual --}}
        <div class="mt-5 bg-surface-50 dark:bg-surface-900 rounded-xl p-4 border border-surface-200/50 dark:border-surface-700">
            <div class="text-center mb-3">
                <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $ticket->compagnie()->name }}</span>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-center flex-1">
                    <p class="text-lg font-bold text-surface-900 dark:text-white">{{ $ticket?->voyageInstance?->villeDepart()?->name ?? "N/A" }}</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">({{ $ticket?->voyageInstance?->villeDepart()?->region?->pays->iso2 ?? "N/A" }})</p>
                    <p class="text-xs text-primary-500 dark:text-primary-400 mt-1">{{ $ticket?->voyageInstance?->gareDepart()?->name ?? "N/A" }}</p>
                </div>

                <div class="flex flex-col items-center px-3">
                    @if($ticket?->type === \App\Enums\TypeTicket::AllerRetour)
                        <div class="flex items-center gap-1">
                            <div class="h-[2px] w-6 bg-primary-300 dark:bg-primary-600"></div>
                            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                            <div class="h-[2px] w-6 bg-primary-300 dark:bg-primary-600"></div>
                        </div>
                        <span class="badge-primary mt-1 text-[10px]">Aller-Retour</span>
                    @else
                        <div class="flex items-center gap-1">
                            <div class="h-[2px] w-6 bg-primary-300 dark:bg-primary-600"></div>
                            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            <div class="h-[2px] w-6 bg-primary-300 dark:bg-primary-600"></div>
                        </div>
                        <span class="badge-neutral mt-1 text-[10px]">Aller Simple</span>
                    @endif
                </div>

                <div class="text-center flex-1">
                    <p class="text-lg font-bold text-surface-900 dark:text-white">{{ $ticket?->voyageInstance?->villeArrive()?->name ?? "N/A" }}</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">({{ $ticket?->voyageInstance?->villeDepart()?->region?->pays->iso2 ?? "N/A" }})</p>
                    <p class="text-xs text-primary-500 dark:text-primary-400 mt-1">{{ $ticket?->voyageInstance?->gareArrive()?->name ?? "N/A" }}</p>
                </div>
            </div>
        </div>

        {{-- Ticket details grid --}}
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="bg-surface-50 dark:bg-surface-900 rounded-lg p-3 border border-surface-200/50 dark:border-surface-700">
                <p class="text-[10px] uppercase tracking-wider text-surface-400 dark:text-surface-500 font-medium">Date</p>
                <p class="text-sm font-semibold text-surface-900 dark:text-white mt-0.5">{{ $ticket?->voyageInstance?->date?->format('d M Y') }}</p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-900 rounded-lg p-3 border border-surface-200/50 dark:border-surface-700">
                <p class="text-[10px] uppercase tracking-wider text-surface-400 dark:text-surface-500 font-medium">Heure</p>
                <p class="text-sm font-semibold text-surface-900 dark:text-white mt-0.5">{{ $ticket?->voyageInstance?->heure?->format('H\h i') }}</p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-900 rounded-lg p-3 border border-surface-200/50 dark:border-surface-700">
                <p class="text-[10px] uppercase tracking-wider text-surface-400 dark:text-surface-500 font-medium">Classe</p>
                <p class="text-sm font-semibold text-surface-900 dark:text-white mt-0.5">{{ $ticket?->voyageInstance?->classe()?->name ?? $ticket?->voyageInstance?->voyage?->classe->name }}</p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-900 rounded-lg p-3 border border-surface-200/50 dark:border-surface-700">
                <p class="text-[10px] uppercase tracking-wider text-surface-400 dark:text-surface-500 font-medium">Siège</p>
                <p class="text-sm font-semibold text-surface-900 dark:text-white mt-0.5">N° {{ $ticket->numero_chaise ?? "Non défini" }}</p>
            </div>
        </div>

        {{-- Price & Status --}}
        <div class="mt-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-wider text-surface-400 dark:text-surface-500 font-medium">Prix</p>
                <p class="text-lg font-bold text-success-600 dark:text-success-400">
                    @if ($ticket?->payements()->first()?->montant > 0)
                        {{ $ticket?->payements()->first()?->montant }} F CFA
                    @elseif ($ticket->statut === \App\Enums\StatutTicket::EnAttente)
                        {{ $ticket->prix() }} F CFA
                    @else
                        Gratuit
                    @endif
                </p>
            </div>
            <div>
                @if($ticket?->voyageInstance?->statut === \App\Enums\StatutVoyageInstance::DISPONIBLE)
                    <span @class([
                        "badge-success" => $ticket->statut === \App\Enums\StatutTicket::Payer,
                        "badge-warning" => $ticket->statut === \App\Enums\StatutTicket::Pause,
                        "badge-danger" => $ticket->statut === \App\Enums\StatutTicket::Bloquer or $ticket->statut === \App\Enums\StatutTicket::Suspendre,
                        "badge-primary" => $ticket->statut === \App\Enums\StatutTicket::EnAttente,
                        "badge bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400" => $ticket->statut === \App\Enums\StatutTicket::Valider,
                    ])>{{ $ticket->statut }}</span>
                @else
                    <span class="badge-danger">Voyage annulé</span>
                @endif
            </div>
        </div>

        {{-- QR Code section --}}
        <div class="mt-4 pt-4 border-t border-dashed border-surface-200 dark:border-surface-700">
            @if($ticket?->statut === \App\Enums\StatutTicket::Payer)
                <div class="flex flex-col items-center gap-2">
                    <img src="{{ asset(\Illuminate\Support\Facades\Storage::url($ticket?->code_qr_uri)) }}"
                         alt="Code QR" class="w-28 h-28 rounded-lg border border-surface-200 dark:border-surface-700 p-1 bg-white">
                    <div class="flex items-center gap-2 bg-surface-100 dark:bg-surface-900 px-3 py-1.5 rounded-lg">
                        <svg class="w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                        <span class="text-sm font-mono font-bold text-surface-700 dark:text-surface-300">{{ $ticket->code_sms }}</span>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center gap-2 py-3 text-surface-400 dark:text-surface-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <span class="text-sm font-medium">{{ $ticket?->statut }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Navigation Button - "S'orienter vers la gare" --}}
    @if($ticket?->statut === \App\Enums\StatutTicket::Payer || $ticket?->statut === \App\Enums\StatutTicket::Valider)
        <a href="{{ route('ticket.navigate-to-gare', $ticket) }}"
           class="card group flex items-center gap-4 hover:shadow-elevated hover:border-primary-300 dark:hover:border-primary-500 transition-all duration-300 cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-surface-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">S'orienter vers la gare</p>
                <p class="text-sm text-surface-500 dark:text-surface-400">Itinéraire en temps réel vers {{ $ticket?->voyageInstance?->gareDepart()?->name ?? 'la gare' }}</p>
            </div>
            <svg class="w-5 h-5 text-surface-300 dark:text-surface-600 group-hover:text-primary-500 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </a>
    @endif

    {{-- Quick Actions --}}
    @if($ticket->statut === \App\Enums\StatutTicket::Payer)
        <div class="grid grid-cols-1 gap-2">
            <a href="{{ route('ticket.reenvoyer',$ticket) }}" class="card group flex items-center gap-3 hover:shadow-soft transition-all duration-200">
                <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-surface-900 dark:text-white">Re-envoyer le PDF par mail</p>
                </div>
                <svg class="w-4 h-4 text-surface-300 dark:text-surface-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>

            <a href="{{ route('ticket.regenerer',$ticket) }}" class="card group flex items-center gap-3 hover:shadow-soft transition-all duration-200">
                <div class="w-10 h-10 rounded-lg bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center group-hover:bg-accent-200 dark:group-hover:bg-accent-900/50 transition-colors">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-surface-900 dark:text-white">Régénérer le ticket</p>
                </div>
                <svg class="w-4 h-4 text-surface-300 dark:text-surface-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>

            <a href="{{ route('ticket.transferer-ticket-to-other-user',$ticket) }}" class="card group flex items-center gap-3 hover:shadow-soft transition-all duration-200">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-surface-900 dark:text-white">Transférer à un autre utilisateur</p>
                </div>
                <svg class="w-4 h-4 text-surface-300 dark:text-surface-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    @endif
</div>

{{-- Modal de mise en pause --}}
<div id="modal-pause" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-elevated dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
            <button type="button" class="absolute top-4 end-4 text-surface-400 bg-transparent hover:bg-surface-100 hover:text-surface-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-surface-700 dark:hover:text-white transition-colors" data-modal-hide="modal-pause">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
            </button>
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-warning-100 dark:bg-warning-500/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-warning-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
                <h3 class="mb-2 text-lg font-bold text-surface-900 dark:text-white">Mettre en pause ?</h3>
                <p class="mb-5 text-sm text-surface-500 dark:text-surface-400">Êtes-vous sûr de vouloir mettre votre ticket en pause ?</p>
                <form action="{{ route('ticket.mettre-en-pause',$ticket) }}" method="post" class="flex gap-3 justify-center">
                    @csrf
                    <button data-modal-hide="modal-pause" type="reset" class="btn-secondary">
                        Non, annuler
                    </button>
                    <button data-modal-hide="modal-pause" type="submit" class="btn-danger">
                        Oui, mettre en pause
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
    <script src="{{ asset('js/flowbite.min.js') }}"></script>
@endsection
