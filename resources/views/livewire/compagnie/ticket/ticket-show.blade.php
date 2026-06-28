@php
    $statutColors = [
        'Payer'     => 'bg-green-50 text-green-700',
        'En attente'=> 'bg-amber-50 text-amber-700',
        'Valider'   => 'bg-blue-50 text-blue-700',
        'Pause'     => 'bg-purple-50 text-purple-700',
        'Annuler'   => 'bg-red-50 text-red-700',
        'Bloquer'   => 'bg-red-50 text-red-700',
        'Suspendre' => 'bg-gray-100 text-gray-600',
        'Refuser'   => 'bg-red-50 text-red-700',
    ];
    $sv = $ticket->statut?->value ?? '—';
    $sc = $statutColors[$sv] ?? 'bg-gray-100 text-gray-600';
    $vi = $ticket->voyageInstance;
    $passengerName = $ticket->is_my_ticket
        ? ($ticket->user?->name ?? '—')
        : ($ticket->autre_personne?->name ?? $ticket->user?->name ?? '—');
    $totalPaid = $ticket->payements->sum('montant');
@endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- En-tête --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('panel.compagnie.tickets') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Ticket {{ $ticket->numero_ticket }}</h1>
                <p class="text-sm text-gray-500">{{ $vi?->villeDepart()?->name ?? '—' }} → {{ $vi?->villeArrive()?->name ?? '—' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('panel.compagnie.tickets.print', ['ticketId' => $ticket->id]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimer
            </a>
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ $sc }}">{{ $sv }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Passager --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Passager</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="text-gray-500">Nom</p><p class="font-medium text-gray-800">{{ $passengerName }}</p></div>
                    <div><p class="text-gray-500">Type</p><p class="font-medium text-gray-800">{{ $ticket->is_my_ticket ? 'Pour le titulaire' : 'Pour un proche' }}</p></div>
                    <div><p class="text-gray-500">Siège</p><p class="font-medium text-gray-800">Chaise n°{{ $ticket->numero_chaise }}</p></div>
                    @unless($ticket->is_my_ticket)
                        <div><p class="text-gray-500">Téléphone</p><p class="font-medium text-gray-800">{{ $ticket->autre_personne?->numero_identifiant }}{{ $ticket->autre_personne?->numero }}</p></div>
                        <div><p class="text-gray-500">Lien</p><p class="font-medium text-gray-800">{{ $ticket->autre_personne?->lien_relation ?? '—' }}</p></div>
                        @if($ticket->autre_personne?->note)
                            <div class="col-span-2 md:col-span-3"><p class="text-gray-500">Note</p><p class="font-medium text-gray-800">{{ $ticket->autre_personne->note }}</p></div>
                        @endif
                    @endunless
                </div>
                <p class="text-xs text-gray-400 mt-3">Acheté par {{ $ticket->user?->name ?? '—' }}</p>
            </div>

            {{-- Voyage --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Voyage</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="text-gray-500">Date</p><p class="font-medium text-gray-800">{{ $vi?->date ? \Illuminate\Support\Carbon::parse($vi->date)->format('d/m/Y') : '—' }}</p></div>
                    <div><p class="text-gray-500">Heure</p><p class="font-medium text-gray-800">{{ $vi?->heure ? \Illuminate\Support\Carbon::parse($vi->heure)->format('H\hi') : '—' }}</p></div>
                    <div><p class="text-gray-500">Type</p><p class="font-medium text-gray-800">{{ $ticket->type?->value ?? '—' }}</p></div>
                    <div><p class="text-gray-500">Véhicule</p><p class="font-medium text-gray-800">{{ $vi?->care?->immatrculation ?? '—' }}</p></div>
                    <div><p class="text-gray-500">Compagnie</p><p class="font-medium text-gray-800">{{ $vi?->voyage?->compagnie?->name ?? '—' }}</p></div>
                    @if($vi)
                        <div class="flex items-end">
                            <a href="{{ route('panel.compagnie.instances.show', ['instanceId' => $vi->id]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Voir l'instance →</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Paiements --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Paiements</h2>
                    <span class="text-sm font-semibold text-gray-800">{{ number_format($totalPaid, 0, ',', ' ') }} XOF</span>
                </div>
                @forelse($ticket->payements as $p)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ number_format($p->montant, 0, ',', ' ') }} XOF</p>
                            <p class="text-xs text-gray-500">{{ $p->moyen_payment ?? $p->moyen ?? 'Paiement' }} · {{ $p->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($p->transaction_id)<span class="text-xs text-gray-400">{{ $p->transaction_id }}</span>@endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-4 text-center">Aucun paiement enregistré.</p>
                @endforelse
            </div>

            {{-- Transfert --}}
            @if($ticket->transferer_at)
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5">
                    <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wide mb-2">Transfert</h2>
                    <p class="text-sm text-purple-800">
                        Transféré le {{ \Illuminate\Support\Carbon::parse($ticket->transferer_at)->format('d/m/Y à H:i') }}
                        à <strong>{{ $transferRecipient?->name ?? $ticket->autre_personne?->name ?? '—' }}</strong>.
                    </p>
                </div>
            @endif
        </div>

        {{-- Colonne QR --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 text-center h-fit">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Code d'embarquement</h2>
            @if($qr)
                <img src="{{ $qr }}" alt="QR" class="w-44 h-44 mx-auto">
            @else
                <div class="w-44 h-44 mx-auto bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-sm">Pas de QR</div>
            @endif
            <p class="mt-4 text-xs text-gray-500">Code SMS</p>
            <p class="text-lg font-bold tracking-widest text-gray-800">{{ $ticket->code_sms ?? '—' }}</p>
        </div>
    </div>
</div>
