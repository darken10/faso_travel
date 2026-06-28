<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Rapports</h1>
            <p class="text-sm text-gray-500">Synthèse d'activité et exports sur une période</p>
        </div>
    </div>

    {{-- Sélecteur de période --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="inline-flex flex-wrap rounded-lg border border-gray-200 p-0.5 bg-gray-50">
                @foreach (['this_month' => 'Ce mois', 'last_month' => 'Mois dernier', 'this_week' => 'Cette semaine', '7d' => '7 jours', '30d' => '30 jours'] as $key => $label)
                    <button type="button" wire:click="applyPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition {{ $preset === $key ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">Du</span>
                <input wire:model.live="dateDebut" type="date" class="px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                <span class="text-xs text-gray-500">au</span>
                <input wire:model.live="dateFin" type="date" class="px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Recettes totales</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($data['totalRecettes'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Dépenses</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($data['totalDepenses'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Bénéfice net</p>
            <p class="text-2xl font-bold mt-1 {{ $data['benefice'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $data['benefice'] >= 0 ? '+' : '' }}{{ number_format($data['benefice'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Tickets vendus</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($data['ticketsCount'], 0, ',', ' ') }}</p>
        </div>
    </div>

    {{-- Téléchargements --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Exporter</h2>
        <div class="flex flex-wrap gap-3">
            <button wire:click="exportRapportPdf" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Rapport de synthèse (PDF)
            </button>
            <button wire:click="exportPaiements" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Paiements / comptable (Excel)
            </button>
            <button wire:click="exportTrajets" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Performance par trajet (Excel)
            </button>
        </div>
    </div>

    {{-- Aperçu top trajets --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Top trajets</h2></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Trajet</th>
                    <th class="px-4 py-2.5 text-right font-medium">Tickets</th>
                    <th class="px-4 py-2.5 text-right font-medium">Recette</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse(array_slice($data['topTrajets'], 0, 10) as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $r['trajet'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $r['tickets'] }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($r['recette'], 0, ',', ' ') }} F</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Aucune vente sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
