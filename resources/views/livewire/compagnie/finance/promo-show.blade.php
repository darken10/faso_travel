@php
    $passengerName = fn($t) => $t->is_my_ticket
        ? ($t->user?->name ?? '—')
        : ($t->autre_personne?->name ?? $t->user?->name ?? '—');
@endphp

<div class="space-y-6">
    {{-- En-tête --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('panel.compagnie.promos') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <span class="font-mono">{{ $promo->code }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $promo->active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $promo->active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        {{ $promo->active ? 'Actif' : 'Inactif' }}
                    </span>
                </h1>
                <p class="text-sm text-gray-500">
                    @if($promo->type === 'pourcentage') -{{ $promo->valeur }}% @else -{{ number_format($promo->valeur, 0, ',', ' ') }} F @endif
                    · {{ $promo->date_debut?->format('d/m/Y') ?? '—' }} → {{ $promo->date_fin?->format('d/m/Y') ?? '∞' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportPdf" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </button>
            <a href="{{ route('panel.compagnie.promos') }}" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">Retour</a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Utilisations</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $totalUtilisations }}{{ $promo->usage_limit ? ' / ' . $promo->usage_limit : '' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Réduction totale</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalReduction, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Montant min.</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $promo->min_montant ? number_format($promo->min_montant, 0, ',', ' ') . ' F' : '—' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Compteur enregistré</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $promo->used_count }}</p>
        </div>
    </div>

    {{-- Bénéficiaires --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Bénéficiaires ({{ $tickets->total() }})</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">N° Ticket</th>
                    <th class="px-4 py-2.5 text-left font-medium">Bénéficiaire</th>
                    <th class="px-4 py-2.5 text-left font-medium">Trajet</th>
                    <th class="px-4 py-2.5 text-left font-medium">Date</th>
                    <th class="px-4 py-2.5 text-right font-medium">Réduction</th>
                    <th class="px-4 py-2.5 text-right font-medium">Payé</th>
                    <th class="px-4 py-2.5 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($tickets as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $t->numero_ticket }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $passengerName($t) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $t->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $t->voyageInstance?->voyage?->trajet?->arriver?->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $t->created_at?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right text-red-600 font-medium">-{{ number_format($t->reduction ?? 0, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($t->payements->sum('montant'), 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.compagnie.tickets.show', ['ticketId' => $t->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Aucun ticket n'a encore utilisé ce code.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $tickets->links() }}</div>
    </div>
</div>
