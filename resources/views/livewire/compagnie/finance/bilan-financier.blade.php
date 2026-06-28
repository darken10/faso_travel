<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Bilan financier</h1>
            <p class="text-sm text-gray-500">Vue d'ensemble des recettes et dépenses</p>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Total recettes</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($totalRecettes, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
            <p class="text-xs text-gray-400 mt-1">Tickets: {{ number_format($totalTicketRecettes, 0, ',', ' ') }} F · Manuel: {{ number_format($totalManualRecettes, 0, ',', ' ') }} F</p>
            @if($totalReductionsPromo > 0)
                <p class="text-xs text-purple-500 mt-1">
                    <span class="font-medium">−{{ number_format($totalReductionsPromo, 0, ',', ' ') }} F</span> de réductions promo
                    <span class="text-gray-400">· brut {{ number_format($totalTicketRecettes + $totalReductionsPromo, 0, ',', ' ') }} F</span>
                </p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Total dépenses</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($totalDepenses, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-{{ $solde >= 0 ? 'green' : 'red' }}-100 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Solde net</p>
            <p class="text-2xl font-bold {{ $solde >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $solde >= 0 ? '+' : '' }}{{ number_format($solde, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Ce mois</p>
            <p class="text-sm font-semibold text-green-600">+{{ number_format($recettesMois, 0, ',', ' ') }} F</p>
            <p class="text-sm font-semibold text-red-500 mt-0.5">-{{ number_format($depensesMois, 0, ',', ' ') }} F</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Line chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Recettes vs Dépenses — 6 derniers mois</h3>
            <canvas id="bilanLineChart" height="120"></canvas>
        </div>
        {{-- Doughnut chart --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Dépenses par catégorie</h3>
            <canvas id="bilanDoughnutChart" height="180"></canvas>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-3 gap-4 mt-6">
        <a href="{{ route('panel.compagnie.recettes') }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Recettes manuelles</span>
        </a>
        <a href="{{ route('panel.compagnie.depenses') }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Dépenses</span>
        </a>
        <a href="{{ route('panel.compagnie.categories') }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow flex items-center gap-3">
            <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Catégories</span>
        </a>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const lineCtx = document.getElementById('bilanLineChart');
    const doughnutCtx = document.getElementById('bilanDoughnutChart');
    if (!lineCtx || !doughnutCtx) return;

    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Recettes',
                    data: @json($chartRecettes),
                    backgroundColor: 'rgba(34,197,94,0.15)',
                    borderColor: 'rgb(34,197,94)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                },
                {
                    label: 'Dépenses',
                    data: @json($chartDepenses),
                    backgroundColor: 'rgba(239,68,68,0.15)',
                    borderColor: 'rgb(239,68,68)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => v.toLocaleString('fr') + ' F' }
                }
            }
        }
    });

    const colors = [
        'rgba(245,158,11,0.85)','rgba(59,130,246,0.85)','rgba(239,68,68,0.85)',
        'rgba(34,197,94,0.85)','rgba(168,85,247,0.85)','rgba(236,72,153,0.85)',
        'rgba(20,184,166,0.85)','rgba(107,114,128,0.85)'
    ];

    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: @json($doughnutData->pluck('nom')),
            datasets: [{
                data: @json($doughnutData->pluck('total')),
                backgroundColor: colors.slice(0, {{ $doughnutData->count() }}),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
})();
</script>
@endpush
