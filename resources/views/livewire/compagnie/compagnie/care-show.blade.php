<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('panel.compagnie.cares') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-800">Fiche véhicule</h1>
    </div>

    {{-- Profil --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-wrap items-center gap-5">
        <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
            <svg class="w-9 h-9 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h8m-8 0a2 2 0 00-2 2v6a1 1 0 001 1h1m0-9V5a1 1 0 011-1h6a1 1 0 011 1v2m-8 9h8m0 0h1a1 1 0 001-1V9a2 2 0 00-2-2m1 9a1 1 0 102 0 1 1 0 00-2 0zM7 16a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-semibold text-gray-800">{{ $care->immatrculation }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm">
                <div><p class="text-gray-500">Numéro</p><p class="font-medium text-gray-800">{{ $care->numero ?? '—' }}</p></div>
                <div><p class="text-gray-500">Places</p><p class="font-medium text-gray-800">{{ $care->number_place }}</p></div>
                <div><p class="text-gray-500">État</p><p class="font-medium text-gray-800">{{ $care->etat ?? '—' }}</p></div>
                <div><p class="text-gray-500">Statut</p><p class="font-medium text-gray-800">{{ is_object($care->statut) ? ($care->statut->value ?? '—') : ($care->statut ?? '—') }}</p></div>
            </div>
        </div>
    </div>

    {{-- Affectations à venir --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Affectations à venir ({{ $assignments->total() }})</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Date</th>
                    <th class="px-4 py-2.5 text-left font-medium">Heure</th>
                    <th class="px-4 py-2.5 text-left font-medium">Trajet</th>
                    <th class="px-4 py-2.5 text-left font-medium">Chauffeur</th>
                    <th class="px-4 py-2.5 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($assignments as $a)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ \Illuminate\Support\Carbon::parse($a->date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($a->heure)->format('H\hi') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $a->voyage?->trajet?->depart?->name }} → {{ $a->voyage?->trajet?->arriver?->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $a->chauffer ? $a->chauffer->first_name.' '.$a->chauffer->last_name : '—' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('panel.compagnie.instances.show', ['instanceId' => $a->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucune affectation à venir.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $assignments->links() }}</div>
    </div>

    {{-- Documents --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Documents ({{ $care->documents->count() }})</h2>
        @forelse ($care->documents as $doc)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                <span class="text-gray-800">{{ $doc->name ?? $doc->type ?? 'Document' }}</span>
                <span class="text-xs text-gray-400">{{ $doc->created_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-4 text-center">Aucun document.</p>
        @endforelse
    </div>
</div>
