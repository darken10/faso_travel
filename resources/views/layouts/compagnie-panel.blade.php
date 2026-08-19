<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Compagnie') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800"
      x-data="{
          sidebarOpen: window.innerWidth >= 1024,
          mobileSidebarOpen: false,
          init() {
              this.$watch('mobileSidebarOpen', v => { document.body.style.overflow = v ? 'hidden' : '' });
          }
      }">

{{-- Mobile overlay --}}
<div x-show="mobileSidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileSidebarOpen = false"
     class="fixed inset-0 bg-black/60 z-30 lg:hidden"
     x-cloak></div>

<div class="flex h-screen overflow-hidden">

    {{-- ═══ Sidebar ═══ --}}
    <aside
        :class="[
            'fixed lg:relative inset-y-0 left-0 z-40 bg-gray-900 flex flex-col transition-all duration-300 ease-in-out',
            mobileSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
            sidebarOpen ? 'lg:w-64' : 'lg:w-16'
        ]"
        class="fixed lg:relative inset-y-0 left-0 z-40 bg-gray-900 flex flex-col">

        {{-- Logo + toggle --}}
        <div class="h-16 flex items-center gap-3 px-3 border-b border-gray-800 flex-shrink-0">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="w-10 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 flex items-center justify-center flex-shrink-0 transition-colors lg:flex hidden">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </button>
            <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0 lg:hidden">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <div x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="overflow-hidden whitespace-nowrap min-w-0">
                <p class="text-white font-semibold text-sm leading-tight truncate">{{ auth()->user()->compagnie?->name ?? config('app.name') }}</p>
                <p class="text-blue-400 text-xs">Espace Compagnie</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 px-2">

            @php
            $compagnieNav = [
                ['route' => 'panel.compagnie.dashboard', 'label' => 'Tableau de bord', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['section' => 'Voyage'],
                ['route' => 'panel.compagnie.trajets', 'label' => 'Trajets', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                ['route' => 'panel.compagnie.voyages', 'label' => 'Voyages', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['route' => 'panel.compagnie.classes', 'label' => 'Classes', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                ['route' => 'panel.compagnie.instances', 'label' => 'Instances', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['section' => 'Guichet'],
                ['route' => 'panel.compagnie.vente-ticket', 'label' => 'Vente de ticket', 'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                ['route' => 'panel.compagnie.tickets', 'label' => 'Tickets', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['route' => 'panel.compagnie.caisse', 'label' => 'Ma Caisse', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ['route' => 'panel.compagnie.caisses-historique', 'label' => 'Historique caisses', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['section' => 'Ressources'],
                ['route' => 'panel.compagnie.gares', 'label' => 'Gares', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                ['route' => 'panel.compagnie.cares', 'label' => 'Véhicules', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['route' => 'panel.compagnie.chauffeurs', 'label' => 'Chauffeurs', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['route' => 'panel.compagnie.documents', 'label' => 'Documents', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['route' => 'panel.compagnie.users', 'label' => 'Équipe', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['section' => 'Contenu'],
                ['route' => 'panel.compagnie.posts', 'label' => 'Articles', 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
                ['section' => 'Comptabilité'],
                ['route' => 'panel.compagnie.rapports', 'label' => 'Rapports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['route' => 'panel.compagnie.bilan', 'label' => 'Bilan financier', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['route' => 'panel.compagnie.depenses', 'label' => 'Dépenses', 'icon' => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'],
                ['route' => 'panel.compagnie.recettes', 'label' => 'Recettes', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                ['route' => 'panel.compagnie.categories', 'label' => 'Catégories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                ['route' => 'panel.compagnie.promos', 'label' => 'Codes promo', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['section' => 'Configuration'],
                ['route' => 'panel.compagnie.parametres', 'label' => 'Paramètres', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
            @endphp

            @foreach($compagnieNav as $item)
                @if(isset($item['section']))
                    <div x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="pt-4 pb-1 px-2">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ $item['section'] }}</p>
                    </div>
                    <div x-show="!sidebarOpen && !mobileSidebarOpen" class="py-2"><div class="h-px bg-gray-800 mx-1"></div></div>
                @else
                    <a href="{{ route($item['route']) }}"
                       title="{{ $item['label'] }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group relative {{ request()->routeIs($item['route']) ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="whitespace-nowrap overflow-hidden">{{ $item['label'] }}</span>
                        <span x-show="!sidebarOpen && !mobileSidebarOpen"
                              class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 hidden lg:block">
                            {{ $item['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach

        </nav>

        {{-- User Footer --}}
        <div class="border-t border-gray-800 p-3 flex-shrink-0">
            <div class="flex items-center gap-3 px-1 py-2 rounded-lg">
                <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-sm font-semibold text-white flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="flex-1 min-w-0 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->first_name ?? auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
                <form x-show="sidebarOpen || mobileSidebarOpen" x-cloak method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Déconnexion" class="text-gray-500 hover:text-red-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ═══ Main Content ═══ --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 sm:px-6 flex-shrink-0 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                {{-- Mobile burger --}}
                <button @click="mobileSidebarOpen = !mobileSidebarOpen"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                {{-- Desktop sidebar toggle --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="hidden lg:flex p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Divider --}}
                <div class="hidden sm:block w-px h-6 bg-gray-200 flex-shrink-0"></div>

                {{-- Page title --}}
                @if(isset($heading))
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-semibold text-gray-800 truncate">{{ $heading }}</h1>
                        <p class="text-xs text-gray-400 hidden sm:block">Espace Compagnie · {{ auth()->user()->compagnie?->name }}</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Actions slot --}}
                @if(isset($actions))
                    <div class="flex items-center gap-2">{{ $actions }}</div>
                    <div class="w-px h-6 bg-gray-200"></div>
                @endif

                {{-- Notifications --}}
                <a href="{{ route('user.notifications') }}"
                   class="relative p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                    @if($unread > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                            {{ min($unread, 9) }}
                        </span>
                    @endif
                </a>

                {{-- User card --}}
                <div class="flex items-center gap-2.5 pl-2 border-l border-gray-100">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-xs flex-shrink-0 shadow-sm">
                        {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="hidden md:block">
                        <p class="text-xs font-semibold text-gray-800 leading-tight">{{ auth()->user()->first_name ?? auth()->user()->name }}</p>
                        <p class="text-[10px] text-blue-500 font-medium leading-tight">Compagnie</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash de session (ex. après une redirection) relayé en toast --}}
        @if(session('success') || session('error'))
            <div x-data x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('toast', { detail: @js(['message' => session('success') ?: session('error'), 'type' => session('success') ? 'success' : 'error']) })))"></div>
        @endif

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            {{ $slot }}
        </main>

    </div>

</div>

<livewire:compagnie.document.document-slide-over />
@stack('modals')
<x-toast-stack />
@livewireScripts
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
