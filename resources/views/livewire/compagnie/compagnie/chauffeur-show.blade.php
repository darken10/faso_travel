<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('panel.compagnie.chauffeurs') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-800">Fiche chauffeur</h1>
    </div>

    {{-- Profil --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-wrap items-center gap-5">
        <div class="w-20 h-20 rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-bold shrink-0">
            {{ strtoupper(mb_substr($chauffeur->first_name, 0, 1)) }}{{ strtoupper(mb_substr($chauffeur->last_name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-semibold text-gray-800">{{ $chauffeur->first_name }} {{ $chauffeur->last_name }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm">
                <div><p class="text-gray-500">Matricule</p><p class="font-medium text-gray-800">{{ $chauffeur->matricule ?? '—' }}</p></div>
                <div><p class="text-gray-500">Téléphone</p><p class="font-medium text-gray-800">{{ $chauffeur->telephone ?? '—' }}</p></div>
                <div><p class="text-gray-500">Genre</p><p class="font-medium text-gray-800">{{ $chauffeur->genre ?? '—' }}</p></div>
                <div><p class="text-gray-500">Statut</p><p class="font-medium text-gray-800">{{ is_object($chauffeur->statut) ? ($chauffeur->statut->value ?? '—') : ($chauffeur->statut ?? '—') }}</p></div>
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
                    <th class="px-4 py-2.5 text-left font-medium">Véhicule</th>
                    <th class="px-4 py-2.5 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($assignments as $a)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ \Illuminate\Support\Carbon::parse($a->date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($a->heure)->format('H\hi') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $a->voyage?->trajet?->depart?->name }} → {{ $a->voyage?->trajet?->arriver?->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $a->care?->immatrculation ?? '—' }}</td>
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
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Documents ({{ $chauffeur->documents->count() }})</h2>
        @forelse ($chauffeur->documents as $doc)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                <span class="text-gray-800">{{ $doc->name ?? $doc->type ?? 'Document' }}</span>
                <span class="text-xs text-gray-400">{{ $doc->created_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-4 text-center">Aucun document.</p>
        @endforelse
    </div>
</div>
