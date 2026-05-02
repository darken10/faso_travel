<nav x-data="{ mobileOpen: false, profileOpen: false }" @click.away="profileOpen = false"
     class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-b border-gray-200/80 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 flex-shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
                <span class="font-bold text-gray-900 text-lg tracking-tight hidden sm:block">Liptra</span>
            </a>

            {{-- Desktop Navigation --}}
            @php
                $navLinks = [
                    ['href' => '/', 'label' => 'Accueil', 'route' => null],
                    ['href' => route('voyage.index'), 'label' => 'Voyages', 'route' => 'voyage.*'],
                    ['href' => route('ticket.myTickets'), 'label' => 'Mes tickets', 'route' => 'ticket.*'],
                    ['href' => route('client.compagnies.index'), 'label' => 'Compagnies', 'route' => 'client.compagnies.*'],
                ];
            @endphp
            <div class="hidden lg:flex lg:items-center lg:gap-0.5">
                @foreach($navLinks as $link)
                    @php $active = $link['route'] && request()->routeIs($link['route']); @endphp
                    <a href="{{ $link['href'] }}"
                       class="relative px-3.5 py-2 text-sm font-medium rounded-lg transition-colors
                              {{ $active
                                  ? 'text-blue-600 bg-blue-50'
                                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                        {{ $link['label'] }}
                        @if($active)
                            <span class="absolute bottom-0 left-3 right-3 h-0.5 bg-blue-600 rounded-full"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Right Section --}}
            <div class="flex items-center gap-2">
                {{-- Réserver CTA --}}
                <a href="{{ route('voyage.index') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Réserver
                </a>

                {{-- Notifications --}}
                <a href="{{ route('user.notifications') }}"
                   class="relative p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                    @php $unread = Auth::user()->unreadNotifications()->count(); @endphp
                    @if($unread > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-4 h-4 text-[9px] font-bold text-white bg-red-500 rounded-full">
                            {{ min($unread, 9) }}
                        </span>
                    @endif
                </a>

                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                        <img class="h-8 w-8 rounded-full object-cover ring-2 ring-gray-200"
                             src="{{ asset(Auth::user()->profileUrl ?: 'icon/user1.png') }}"
                             alt="Profil" />
                        <span class="hidden md:block text-sm font-medium text-gray-700">{{ Auth::user()->first_name ?? Auth::user()->name }}</span>
                        <svg class="hidden md:block w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black/5 py-1 z-50">

                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <a href="{{ route('profile.show') }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Mon profil
                        </a>
                        <a href="{{ route('ticket.myTickets') }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            Mes tickets
                        </a>
                        <a href="{{ route('user.notifications') }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Notifications
                            @if($unread > 0)
                                <span class="ml-auto text-xs font-semibold bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">{{ $unread }}</span>
                            @endif
                        </a>

                        <div class="border-t border-gray-100 mt-1">
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Se déconnecter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden border-t border-gray-200 bg-white shadow-lg">
        <div class="px-4 py-4 space-y-1">
            <a href="{{ route('voyage.index') }}"
               class="flex items-center gap-3 px-3 py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                Réserver un billet
            </a>
            @foreach($navLinks as $link)
                @php $active = $link['route'] && request()->routeIs($link['route']); @endphp
                <a href="{{ $link['href'] }}"
                   class="block px-3 py-2.5 text-sm font-medium rounded-lg transition-colors
                          {{ $active ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="px-4 pb-4 border-t border-gray-100 pt-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img class="h-9 w-9 rounded-full object-cover ring-2 ring-gray-200"
                     src="{{ asset(Auth::user()->profileUrl ?: 'icon/user1.png') }}" alt="Profil"/>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</nav>
