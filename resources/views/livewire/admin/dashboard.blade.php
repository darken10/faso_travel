<div>
    {{-- Flash --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Tableau de bord</h2>
            <p class="text-sm text-gray-500 mt-0.5">Vue d'ensemble de la plateforme</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-panel.stat-card
            label="Compagnies"
            :value="$totalCompagnies"
            color="blue"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>'
        />
        <x-panel.stat-card
            label="Voyages"
            :value="$totalVoyages"
            color="green"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618"/>'
        />
        <x-panel.stat-card
            label="Tickets"
            :value="$totalTickets"
            color="amber"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>'
        />
        <x-panel.stat-card
            label="Utilisateurs"
            :value="$totalUsers"
            color="purple"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'
        />
    </div>

    {{-- Quick links --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('panel.admin.pays') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-amber-400 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Gérer les pays</p>
                    <p class="text-xs text-gray-500">Pays, régions, villes</p>
                </div>
            </div>
        </a>
        <a href="{{ route('panel.admin.compagnies') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-amber-400 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Compagnies</p>
                    <p class="text-xs text-gray-500">Gestion des compagnies</p>
                </div>
            </div>
        </a>
        <a href="{{ route('panel.admin.settings') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-amber-400 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center group-hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Paramètres</p>
                    <p class="text-xs text-gray-500">Configuration compagnies</p>
                </div>
            </div>
        </a>
    </div>
</div>
