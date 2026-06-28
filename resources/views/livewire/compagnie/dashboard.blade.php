<div>
    {{-- ═══ Aujourd'hui (temps réel) ═══ --}}
    <div class="mb-6">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Aujourd'hui</h3>

        {{-- Alertes --}}
        @if($caissesNonCloturees > 0 || $departsSousRemplis > 0)
            <div class="flex flex-wrap gap-3 mb-4">
                @if($caissesNonCloturees > 0)
                    <a href="{{ route('panel.compagnie.caisses-historique') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-red-50 border border-red-200 text-red-700 hover:bg-red-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $caissesNonCloturees }} caisse(s) non clôturée(s)
                    </a>
                @endif
                @if($departsSousRemplis > 0)
                    <a href="{{ route('panel.compagnie.instances') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $departsSousRemplis }} départ(s) sous-rempli(s) (&lt;30%, 48h)
                    </a>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Ventes du jour</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($ventesJour, 0, ',', ' ') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Recette du jour</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($recetteJour, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 col-span-2 lg:col-span-1">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Remplissage à venir (7j)</p>
                <div class="flex items-center gap-3 mt-2">
                    <p class="text-2xl font-bold text-gray-800">{{ $tauxRemplissage }}%</p>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ $tauxRemplissage }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mon activité (agent) --}}
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl shadow-sm p-5 text-white">
            <p class="text-xs uppercase tracking-wide opacity-80">Mes ventes du jour</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($mesVentesJour, 0, ',', ' ') }}</p>
            <a href="{{ route('panel.compagnie.vente-ticket') }}" class="inline-flex items-center gap-1 mt-3 text-sm font-medium bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg transition-colors">
                Vendre un ticket
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-gray-500">Ma caisse</p>
            @if($maCaisse)
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Ouverte</span>
                    <span class="text-xs text-gray-400">depuis {{ $maCaisse->opened_at?->format('d/m H:i') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($maCaisse->totalVentes(), 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">F</span></p>
                <a href="{{ route('panel.compagnie.caisse.detail', $maCaisse) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium mt-2 inline-block">Voir ma caisse →</a>
            @else
                <p class="text-sm text-gray-500 mt-2">Aucune caisse ouverte.</p>
                <a href="{{ route('panel.compagnie.caisse') }}" class="inline-flex items-center gap-1.5 mt-3 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg">Ouvrir une caisse</a>
            @endif
        </div>
    </div>

    {{-- Prochains départs --}}
    <div class="mb-6 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Prochains départs</h3>
            <a href="{{ route('panel.compagnie.instances') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Tout voir →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Date / Heure</th>
                    <th class="px-4 py-2.5 text-left font-medium">Trajet</th>
                    <th class="px-4 py-2.5 text-left font-medium">Véhicule</th>
                    <th class="px-4 py-2.5 text-left font-medium">Occupation</th>
                    <th class="px-4 py-2.5 text-right font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($prochainsDeparts as $d)
                    @php $rate = $d->nb_place > 0 ? round($d->occupied_count / $d->nb_place * 100) : 0; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-700">
                            <span class="font-medium">{{ \Carbon\Carbon::parse($d->date)->format('d/m') }}</span>
                            <span class="text-gray-500">{{ \Carbon\Carbon::parse($d->heure)->format('H\hi') }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $d->voyage?->trajet?->depart?->name }} → {{ $d->voyage?->trajet?->arriver?->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $d->care?->immatrculation ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-700 whitespace-nowrap">{{ $d->occupied_count }}/{{ $d->nb_place }}</span>
                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $rate < 30 ? 'bg-amber-500' : 'bg-blue-600' }}" style="width: {{ $rate }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.compagnie.instances.show', ['instanceId' => $d->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucun départ à venir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Stat cards: Activité --}}
    <div class="mb-6">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Activité</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-panel.stat-card label="Voyages" value="{{ $totalVoyages }}" color="blue"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>'/> 
            <x-panel.stat-card label="Gares" value="{{ $totalGares }}" color="purple"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>'/>
            <x-panel.stat-card label="Publications" value="{{ $totalPosts }}" color="amber"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>'/>
            <x-panel.stat-card label="Utilisateurs" value="{{ $totalUsers }}" color="green"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'/> 
        </div>
    </div>

    {{-- Stat cards: Tickets --}}
    <div class="mb-6">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Tickets</h3>
        <div class="grid grid-cols-3 gap-4">
            <x-panel.stat-card label="En attente" value="{{ $ticketsPayes }}" color="amber"
                description="Payés, non validés"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>'/>
            <x-panel.stat-card label="Validés" value="{{ $ticketsValides }}" color="green"
                description="Voyages confirmés"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'/>
            <x-panel.stat-card label="Bloqués" value="{{ $ticketsBloques }}" color="red"
                description="Accès interdit"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>'/>
        </div>
    </div>

    {{-- Stat cards: Finance --}}
    <div class="mb-6">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Finance</h3>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <x-panel.stat-card label="Recettes tickets" value="{{ number_format($recetteTickets, 0, ',', ' ') }} F" color="green"
                description="Tickets validés"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>'/>
            <x-panel.stat-card label="Autres recettes" value="{{ number_format($recetteManuelles, 0, ',', ' ') }} F" color="blue"
                description="Recettes manuelles"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'/>
            <x-panel.stat-card label="Total dépenses" value="{{ number_format($totalDepenses, 0, ',', ' ') }} F" color="red"
                description="Toutes les dépenses"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>'/>
            <x-panel.stat-card label="Solde" value="{{ number_format($solde, 0, ',', ' ') }} F" color="{{ $solde >= 0 ? 'green' : 'red' }}"
                description="{{ $solde >= 0 ? 'Bénéfice' : 'Déficit' }}"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'/>
            <x-panel.stat-card label="Recettes ce mois" value="{{ number_format($recettesMois, 0, ',', ' ') }} F" color="green"
                description="{{ now()->translatedFormat('F Y') }}"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'/>
            <x-panel.stat-card label="Dépenses ce mois" value="{{ number_format($depensesMois, 0, ',', ' ') }} F" color="amber"
                description="{{ now()->translatedFormat('F Y') }}"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'/>
        </div>
    </div>

    {{-- Carte du réseau de gares --}}
    <div class="mb-6 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-700">Carte du réseau</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $garesGeo->count() }} gare(s) géolocalisée(s)</p>
            </div>
            @if($garesGeo->isEmpty())
                <span class="text-xs text-amber-500 italic">Aucune gare avec coordonnées GPS</span>
            @endif
        </div>
        <div id="dashboard-map" class="h-[420px] w-full"></div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Line chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Recettes vs Dépenses — 6 derniers mois</h3>
            <div class="h-56">
                <canvas id="financeLineChart"></canvas>
            </div>
        </div>
        {{-- Doughnut chart --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Dépenses par catégorie</h3>
            <div class="h-56 flex items-center justify-center">
                <canvas id="depensesDoughnutChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        // ── Carte du réseau ──
        var garesGeo = @json($garesGeo);
        var mapEl = document.getElementById('dashboard-map');
        if (mapEl) {
            var hasGares = garesGeo.length > 0;
            var center   = hasGares
                ? [
                    garesGeo.reduce(function(s, g) { return s + g.lat; }, 0) / garesGeo.length,
                    garesGeo.reduce(function(s, g) { return s + g.lng; }, 0) / garesGeo.length,
                  ]
                : [12.3714277, -1.5196603];

            var dashMap = L.map(mapEl, { zoomControl: true }).setView(center, 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(dashMap);

            garesGeo.forEach(function(g) {
                L.marker([g.lat, g.lng])
                    .bindPopup(
                        '<strong style="font-size:13px">' + g.name + '</strong>' +
                        (g.ville ? '<br><span style="color:#6b7280;font-size:11px">' + g.ville + '</span>' : '')
                    )
                    .addTo(dashMap);
            });

            if (hasGares && garesGeo.length > 1) {
                var bounds = L.latLngBounds(garesGeo.map(function(g) { return [g.lat, g.lng]; }));
                dashMap.fitBounds(bounds, { padding: [50, 50] });
            } else if (hasGares) {
                dashMap.setView([garesGeo[0].lat, garesGeo[0].lng], 13);
            }

            setTimeout(function() { dashMap.invalidateSize(); }, 100);
        }

        const lineCtx = document.getElementById('financeLineChart')?.getContext('2d');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: 'Recettes',
                            data: @json($chartRecettes),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.15)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                        },
                        {
                            label: 'Dépenses',
                            data: @json($chartDepenses),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.15)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } },
                    },
                },
            });
        }

        const doughnutCtx = document.getElementById('depensesDoughnutChart')?.getContext('2d');
        if (doughnutCtx) {
            new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($doughnutLabels),
                    datasets: [{
                        data: @json($doughnutData),
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(20, 184, 166, 0.8)',
                            'rgba(107, 114, 128, 0.8)',
                        ],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12 } },
                    },
                },
            });
        }
    })();
    </script>
    @endpush
</div>
