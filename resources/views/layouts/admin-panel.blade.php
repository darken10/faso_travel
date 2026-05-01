<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
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
            'fixed lg:relative inset-y-0 left-0 z-40 bg-gray-900 flex flex-col flex-shrink-0 transition-all duration-300 ease-in-out',
            mobileSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
            sidebarOpen ? 'lg:w-64' : 'lg:w-16'
        ]"
        class="fixed lg:relative inset-y-0 left-0 z-40 bg-gray-900 flex flex-col">

        {{-- Logo + toggle --}}
        <div class="h-16 flex items-center gap-3 px-3 border-b border-gray-800 flex-shrink-0">
            <button @click="sidebarOpen = !sidebarOpen; if(!sidebarOpen) mobileSidebarOpen = false"
                    class="w-10 h-10 rounded-lg bg-amber-500 hover:bg-amber-600 flex items-center justify-center flex-shrink-0 transition-colors lg:flex hidden">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </button>
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center flex-shrink-0 lg:hidden">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <div x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="overflow-hidden whitespace-nowrap">
                <p class="text-white font-semibold text-sm leading-tight">Faso Travel</p>
                <p class="text-amber-400 text-xs">Administration</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 px-2">

            @php
            $adminNav = [
                ['route' => 'panel.admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['section' => 'Géographie'],
                ['route' => 'panel.admin.pays', 'label' => 'Pays', 'icon' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9'],
                ['route' => 'panel.admin.regions', 'label' => 'Régions', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                ['route' => 'panel.admin.villes', 'label' => 'Villes', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                ['section' => 'Compagnies'],
                ['route' => 'panel.admin.compagnies', 'label' => 'Compagnies', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                ['route' => 'panel.admin.settings', 'label' => 'Paramètres', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
            @endphp

            @foreach($adminNav as $item)
                @if(isset($item['section']))
                    <div x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="pt-4 pb-1 px-2">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ $item['section'] }}</p>
                    </div>
                    <div x-show="!sidebarOpen && !mobileSidebarOpen" class="py-2"><div class="h-px bg-gray-800 mx-1"></div></div>
                @else
                    <a href="{{ route($item['route']) }}"
                       title="{{ $item['label'] }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group relative {{ request()->routeIs($item['route']) ? 'bg-amber-500 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span x-show="sidebarOpen || mobileSidebarOpen" x-cloak class="whitespace-nowrap overflow-hidden">{{ $item['label'] }}</span>
                        {{-- Tooltip when collapsed --}}
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
                <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-sm font-semibold text-white flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'A', 0, 1)) }}
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
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 flex-shrink-0">
            <div class="flex items-center gap-3">
                {{-- Mobile burger --}}
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                {{-- Desktop sidebar toggle --}}
                <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @if(isset($heading))
                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 hidden sm:block">{{ $heading }}</h1>
                @endif
            </div>
            <div class="flex items-center gap-3">
                {{-- Breadcrumb current page on mobile --}}
                @if(isset($heading))
                    <span class="text-sm font-semibold text-gray-800 sm:hidden">{{ $heading }}</span>
                @endif
                @if(isset($actions))
                    {{ $actions }}
                @endif
                {{-- Quick profile --}}
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold text-xs border border-amber-200">
                        {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success') || session('error'))
        <div class="px-4 sm:px-6 pt-4">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm shadow-sm">
                <svg class="w-4 h-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
            @elseif(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm shadow-sm">
                <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
            @endif
        </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            {{ $slot }}
        </main>

    </div>

</div>

@stack('modals')
@livewireScripts
@stack('scripts')
</body>
</html>
