@php
    $statutColors = [
        'DISPONIBLE' => 'bg-green-50 text-green-700',
        'INACTIF'    => 'bg-gray-100 text-gray-600',
        'RETARDE'    => 'bg-amber-50 text-amber-700',
        'ANNULE'     => 'bg-red-50 text-red-700',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- En-tête --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('panel.compagnie.voyages') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    {{ $voyage->trajet?->depart?->name ?? '—' }}
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    {{ $voyage->trajet?->arriver?->name ?? '—' }}
                </h1>
                <p class="text-sm text-gray-500">
                    Départ {{ $voyage->heure ? \Illuminate\Support\Carbon::parse($voyage->heure)->format('H\hi') : '—' }}
                    @if ($voyage->is_quotidient) · Quotidien @elseif(is_array($voyage->days) && count($voyage->days)) · {{ implode(', ', $voyage->days) }} @endif
                </p>
            </div>
        </div>
        <a href="{{ route('panel.compagnie.voyages.edit', ['voyageId' => $voyage->id]) }}" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">Modifier</a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Instances à venir</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $instancesUpcoming }}</p>
            <p class="text-xs text-gray-400">{{ $instancesTotal }} au total</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Tickets payés</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $ticketsPayes }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Recette totale</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($revenue, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">XOF</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Prix aller</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($voyage->prix, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">XOF</span></p>
        </div>
    </div>

    {{-- Détails --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Détails du voyage</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><p class="text-gray-500">Gare de départ</p><p class="font-medium text-gray-800">{{ $voyage->gareDepart?->name ?? '—' }}</p></div>
            <div><p class="text-gray-500">Gare d'arrivée</p><p class="font-medium text-gray-800">{{ $voyage->gareArrive?->name ?? '—' }}</p></div>
            <div><p class="text-gray-500">Durée</p><p class="font-medium text-gray-800">{{ $voyage->temps ? \Illuminate\Support\Carbon::parse($voyage->temps)->format('H\hi') : '—' }}</p></div>
            <div><p class="text-gray-500">Classe</p><p class="font-medium text-gray-800">{{ $voyage->classe?->name ?? '—' }}</p></div>
            <div><p class="text-gray-500">Prix aller-retour</p><p class="font-medium text-gray-800">{{ $voyage->prix_aller_retour ? number_format($voyage->prix_aller_retour, 0, ',', ' ').' XOF' : '—' }}</p></div>
            <div><p class="text-gray-500">Places par défaut</p><p class="font-medium text-gray-800">{{ $voyage->nb_pace ?? '—' }}</p></div>
            <div><p class="text-gray-500">Véhicule par défaut</p><p class="font-medium text-gray-800">{{ $voyage->vehicule?->immatrculation ?? '—' }}</p></div>
            <div><p class="text-gray-500">Chauffeur par défaut</p><p class="font-medium text-gray-800">{{ $voyage->chauffer ? $voyage->chauffer->first_name.' '.$voyage->chauffer->last_name : '—' }}</p></div>
            <div><p class="text-gray-500">Entrée en vigueur</p><p class="font-medium text-gray-800">{{ $voyage->date_debut ? $voyage->date_debut->format('d/m/Y') : '—' }}</p></div>
            <div><p class="text-gray-500">Fin de validité</p><p class="font-medium text-gray-800">{{ $voyage->date_fin ? $voyage->date_fin->format('d/m/Y') : 'Illimitée' }}</p></div>
        </div>
    </div>

    {{-- Prochaines instances --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Prochaines instances</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Date</th>
                    <th class="px-4 py-2.5 text-left font-medium">Heure</th>
                    <th class="px-4 py-2.5 text-left font-medium">Véhicule</th>
                    <th class="px-4 py-2.5 text-left font-medium">Occupation</th>
                    <th class="px-4 py-2.5 text-left font-medium">Statut</th>
                    <th class="px-4 py-2.5 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($instances as $inst)
                    @php $sv = $inst->statut->value ?? 'DISPONIBLE'; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ \Illuminate\Support\Carbon::parse($inst->date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($inst->heure)->format('H\hi') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $inst->care?->immatrculation ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $inst->occupied_count }} / {{ $inst->nb_place }}</td>
                        <td class="px-4 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statutColors[$sv] ?? $statutColors['INACTIF'] }}">{{ $sv }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.compagnie.instances.show', ['instanceId' => $inst->id]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucune instance à venir.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $instances->links() }}</div>
    </div>
</div>
