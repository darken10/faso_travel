<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="FasoTravel — Réservez vos tickets de transport en ligne. Voyages inter-urbains au Burkina Faso et en Afrique de l'Ouest.">
    <title>FasoTravel — Tickets de transport en ligne</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #1a5276 100%);
        }
        .card-hover {
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,.12);
        }
        .route-card {
            background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.03) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.12);
        }
        .stat-number {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .search-shadow {
            box-shadow: 0 25px 60px rgba(0,0,0,.35);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .floating { animation: float 4s ease-in-out infinite; }
        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-white">

{{-- ════════════════════════════════════════════════ NAVBAR ════════════════════════════════════════════════ --}}
<nav x-data="{ open: false, scrolled: false }"
     @scroll.window="scrolled = (window.scrollY > 30)"
     :class="scrolled ? 'bg-white shadow-md' : 'bg-transparent'"
     class="fixed top-0 inset-x-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center shadow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
                <span :class="scrolled ? 'text-gray-900' : 'text-white'" class="text-xl font-bold tracking-tight transition-colors">FasoTravel</span>
            </a>

            {{-- Desktop nav --}}
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('voyage.index') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium transition-colors">Voyages</a>
                <a href="{{ route('client.compagnies.index') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium transition-colors">Compagnies</a>
                <a href="{{ route('divers.about-us') }}" :class="scrolled ? 'text-gray-600 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium transition-colors">À propos</a>
            </div>

            {{-- CTA --}}
            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ route('voyage.index') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">
                        Mes voyages
                    </a>
                @else
                    <a href="{{ route('login') }}" :class="scrolled ? 'text-gray-700 hover:text-gray-900' : 'text-white/80 hover:text-white'" class="text-sm font-medium transition-colors">Connexion</a>
                    <a href="{{ route('auth.register.step1') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">
                        Inscription gratuite
                    </a>
                @endauth
            </div>

            {{-- Mobile burger --}}
            <button @click="open = !open" class="md:hidden p-2 rounded-lg" :class="scrolled ? 'text-gray-700' : 'text-white'">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden bg-white rounded-xl shadow-xl border border-gray-100 mt-2 mb-3 p-4 space-y-2">
            <a href="{{ route('voyage.index') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-600">Voyages</a>
            <a href="{{ route('client.compagnies.index') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-600">Compagnies</a>
            <a href="{{ route('divers.about-us') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-600">À propos</a>
            <div class="pt-2 border-t border-gray-100 flex flex-col gap-2">
                @auth
                    <a href="{{ route('voyage.index') }}" class="w-full text-center bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors">Mes voyages</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Se connecter</a>
                    <a href="{{ route('auth.register.step1') }}" class="w-full text-center bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors">Inscription gratuite</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- ════════════════════════════════════════════════ HERO ════════════════════════════════════════════════ --}}
<section class="hero-gradient relative min-h-screen flex items-center overflow-hidden">

    {{-- Decorative circles --}}
    <div class="absolute top-20 right-10 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 left-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-white/3 rounded-full blur-3xl"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-40 w-full">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            {{-- Left content --}}
            <div>
                <div class="inline-flex items-center gap-2 bg-amber-500/20 text-amber-300 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 border border-amber-500/30">
                    <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse"></span>
                    Transport sécurisé & fiable
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
                    Voyagez partout,<br>
                    <span class="text-amber-400">réservez</span> en ligne
                </h1>

                <p class="text-lg text-blue-100/80 mb-10 max-w-lg leading-relaxed">
                    La plateforme de référence pour la vente et gestion de tickets de transport au Burkina Faso. Compagnies sérieuses, trajets sécurisés, paiement facile.
                </p>

                {{-- Search card --}}
                <div class="bg-white rounded-2xl p-5 search-shadow">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Rechercher un voyage</p>
                    <form action="{{ route('voyage.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Départ</label>
                            <input name="depart" type="text" placeholder="Ex: Ouagadougou"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent placeholder:text-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Arrivée</label>
                            <input name="arriver" type="text" placeholder="Ex: Bobo-Dioulasso"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent placeholder:text-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                            <div class="flex gap-2">
                                <input name="date" type="date" value="{{ date('Y-m-d') }}"
                                       class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                                <button type="submit" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-6 mt-8">
                    <div>
                        <p class="text-2xl font-bold text-white">50+</p>
                        <p class="text-xs text-blue-200/70">Trajets disponibles</p>
                    </div>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">10+</p>
                        <p class="text-xs text-blue-200/70">Compagnies partenaires</p>
                    </div>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">5k+</p>
                        <p class="text-xs text-blue-200/70">Tickets vendus</p>
                    </div>
                </div>
            </div>

            {{-- Right — illustration / route cards --}}
            <div class="hidden lg:flex flex-col gap-4 items-end">

                <div class="route-card rounded-2xl p-5 w-72 floating" style="animation-delay: 0s">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm">Ouaga → Bobo</p>
                            <p class="text-blue-200/60 text-xs">Départ 06h00</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                            <span class="text-green-300 text-xs font-medium">Places disponibles</span>
                        </div>
                        <span class="text-amber-400 font-bold text-sm">5 000 FCFA</span>
                    </div>
                </div>

                <div class="route-card rounded-2xl p-5 w-72 floating" style="animation-delay: 1.5s">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm">Koudougou → Ouaga</p>
                            <p class="text-blue-200/60 text-xs">Départ 08h30</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                            <span class="text-amber-300 text-xs font-medium">Quelques places</span>
                        </div>
                        <span class="text-amber-400 font-bold text-sm">2 500 FCFA</span>
                    </div>
                </div>

                <div class="route-card rounded-2xl p-5 w-72 floating" style="animation-delay: 3s">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm">Ticket confirmé ✓</p>
                            <p class="text-blue-200/60 text-xs">QR Code généré · PDF envoyé</p>
                        </div>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-1.5">
                        <div class="bg-green-400 h-1.5 rounded-full" style="width:100%"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Wave --}}
    <svg class="wave" viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
        <path d="M0 40C240 80 480 0 720 40C960 80 1200 0 1440 40V80H0V40Z" fill="white"/>
    </svg>
</section>

{{-- ════════════════════════════════════════════════ FEATURES ════════════════════════════════════════════════ --}}
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <span class="text-amber-500 text-sm font-semibold uppercase tracking-widest">Pourquoi FasoTravel</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-2">Simple, rapide, sécurisé</h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto">Tout ce qu'il vous faut pour voyager l'esprit tranquille, disponible en quelques clics.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
            $features = [
                ['icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'color' => 'amber', 'title' => 'Recherche rapide', 'desc' => 'Trouvez votre trajet en quelques secondes. Filtrez par date, ville et compagnie.'],
                ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'color' => 'green', 'title' => 'Paiement sécurisé', 'desc' => 'Payez par Orange Money, espèces ou virement. Transactions 100% sécurisées.'],
                ['icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16.97 16.97A7 7 0 1112 19a7 7 0 014.97-2.03z', 'color' => 'blue', 'title' => 'QR Code instantané', 'desc' => 'Votre ticket QR code est généré immédiatement après paiement et envoyé par email.'],
                ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'purple', 'title' => 'Voyages fiables', 'desc' => 'Seules les compagnies vérifiées et agréées sont présentes sur la plateforme.'],
            ];
            @endphp

            @foreach($features as $f)
            <div class="card-hover bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                <div class="w-12 h-12 bg-{{ $f['color'] }}-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-{{ $f['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $f['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ COMMENT ÇA MARCHE ════════════════════════════════════════════════ --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <span class="text-amber-500 text-sm font-semibold uppercase tracking-widest">En 3 étapes</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-2">Comment ça marche ?</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            @php
            $steps = [
                ['step' => '01', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'title' => 'Recherchez votre trajet', 'desc' => 'Entrez votre ville de départ, d\'arrivée et la date de votre voyage. Des dizaines de trajets s\'affichent en temps réel.'],
                ['step' => '02', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'title' => 'Réservez & payez', 'desc' => 'Choisissez votre siège, indiquez vos informations et payez en toute sécurité via Orange Money ou espèces.'],
                ['step' => '03', 'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16.97 16.97A7 7 0 1112 19a7 7 0 014.97-2.03z', 'title' => 'Voyagez !', 'desc' => 'Recevez votre ticket par email avec QR code. Présentez-le au départ pour valider votre montée à bord.'],
            ];
            @endphp

            @foreach($steps as $i => $s)
            <div class="relative">
                @if(!$loop->last)
                <div class="hidden md:block absolute top-8 left-full w-full h-px bg-gradient-to-r from-amber-200 to-transparent -ml-8 z-0"></div>
                @endif
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 z-10">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <span class="text-5xl font-black text-amber-100">{{ $s['step'] }}</span>
                        </div>
                        <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center flex-shrink-0 mt-1 shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $s['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="font-bold text-gray-900 mt-4 mb-2 text-lg">{{ $s['title'] }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $s['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ ROUTES POPULAIRES ════════════════════════════════════════════════ --}}
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-amber-500 text-sm font-semibold uppercase tracking-widest">Destinations</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-2">Trajets populaires</h2>
            </div>
            <a href="{{ route('voyage.index') }}" class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                Voir tous les voyages
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
            $routes = [
                ['from' => 'Ouagadougou', 'to' => 'Bobo-Dioulasso', 'duration' => '4h30', 'from_short' => 'OUA', 'to_short' => 'BOB', 'price' => '5 000', 'badge' => 'Populaire', 'badge_color' => 'amber'],
                ['from' => 'Ouagadougou', 'to' => 'Koudougou', 'duration' => '2h00', 'from_short' => 'OUA', 'to_short' => 'KOD', 'price' => '2 500', 'badge' => 'Fréquent', 'badge_color' => 'blue'],
                ['from' => 'Bobo-Dioulasso', 'to' => 'Banfora', 'duration' => '1h30', 'from_short' => 'BOB', 'to_short' => 'BAN', 'price' => '2 000', 'badge' => 'Économique', 'badge_color' => 'green'],
                ['from' => 'Ouagadougou', 'to' => 'Dédougou', 'duration' => '3h00', 'from_short' => 'OUA', 'to_short' => 'DED', 'price' => '3 500', 'badge' => null, 'badge_color' => ''],
                ['from' => 'Koudougou', 'to' => 'Bobo-Dioulasso', 'duration' => '2h30', 'from_short' => 'KOD', 'to_short' => 'BOB', 'price' => '3 000', 'badge' => null, 'badge_color' => ''],
                ['from' => 'Ouagadougou', 'to' => 'Ouahigouya', 'duration' => '3h30', 'from_short' => 'OUA', 'to_short' => 'OHY', 'price' => '4 000', 'badge' => null, 'badge_color' => ''],
            ];
            @endphp

            @foreach($routes as $r)
            <a href="{{ route('voyage.index') }}" class="card-hover group block bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:border-amber-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <div class="flex items-center gap-1.5 text-sm font-bold text-gray-900">
                            <span>{{ $r['from_short'] }}</span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>{{ $r['to_short'] }}</span>
                        </div>
                    </div>
                    @if($r['badge'])
                    <span class="text-xs font-semibold px-2.5 py-1 bg-{{ $r['badge_color'] }}-50 text-{{ $r['badge_color'] }}-600 rounded-full border border-{{ $r['badge_color'] }}-100">{{ $r['badge'] }}</span>
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $r['from'] }}</p>
                        <div class="flex items-center gap-1 my-1">
                            <div class="h-px bg-gray-200 flex-1 max-w-[30px]"></div>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs text-gray-400">{{ $r['duration'] }}</span>
                            <div class="h-px bg-gray-200 flex-1 max-w-[30px]"></div>
                        </div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $r['to'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-black text-amber-500">{{ $r['price'] }}</p>
                        <p class="text-xs text-gray-400">FCFA</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="text-center mt-8 sm:hidden">
            <a href="{{ route('voyage.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600 hover:text-amber-700">
                Voir tous les voyages <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ STATS ════════════════════════════════════════════════ --}}
<section class="py-20 bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            @php
            $stats = [
                ['value' => '10 000+', 'label' => 'Voyageurs satisfaits'],
                ['value' => '50+', 'label' => 'Trajets disponibles'],
                ['value' => '10+', 'label' => 'Compagnies partenaires'],
                ['value' => '99.5%', 'label' => 'Taux de satisfaction'],
            ];
            @endphp
            @foreach($stats as $s)
            <div>
                <p class="stat-number text-4xl font-black mb-2">{{ $s['value'] }}</p>
                <p class="text-gray-400 text-sm">{{ $s['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ COMPAGNIES ════════════════════════════════════════════════ --}}
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <span class="text-amber-500 text-sm font-semibold uppercase tracking-widest">Partenaires</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-2">Nos compagnies partenaires</h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto">Des compagnies sérieuses, vérifiées et agréées pour assurer votre sécurité à chaque voyage.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $companies = [
                ['name' => 'TSR Transport', 'routes' => '12 trajets', 'color' => 'blue', 'icon' => 'T'],
                ['name' => 'Rahimo Voyages', 'routes' => '8 trajets', 'color' => 'green', 'icon' => 'R'],
                ['name' => 'Faso Bus', 'routes' => '15 trajets', 'color' => 'amber', 'icon' => 'F'],
                ['name' => 'Burkina Express', 'routes' => '6 trajets', 'color' => 'red', 'icon' => 'B'],
                ['name' => 'National Transport', 'routes' => '20 trajets', 'color' => 'purple', 'icon' => 'N'],
                ['name' => 'Sotrao', 'routes' => '9 trajets', 'color' => 'indigo', 'icon' => 'S'],
            ];
            @endphp

            @foreach($companies as $c)
            <a href="{{ route('client.compagnies.index') }}" class="card-hover flex items-center gap-4 p-5 border border-gray-100 rounded-2xl bg-white shadow-sm hover:border-{{ $c['color'] }}-200">
                <div class="w-14 h-14 bg-{{ $c['color'] }}-50 rounded-xl flex items-center justify-center text-{{ $c['color'] }}-600 font-black text-xl flex-shrink-0 border border-{{ $c['color'] }}-100">
                    {{ $c['icon'] }}
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-sm">{{ $c['name'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $c['routes'] }}</p>
                    <div class="flex items-center gap-1 mt-1.5">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="text-xs text-gray-400 ml-1">5.0</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('client.compagnies.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-xl transition-colors shadow">
                Voir toutes les compagnies
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ CTA COMPAGNIE ════════════════════════════════════════════════ --}}
<section class="py-20 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="grid lg:grid-cols-2">
                <div class="p-10 lg:p-14">
                    <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-6">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                        Pour les compagnies
                    </span>
                    <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Digitalisez votre compagnie de transport</h2>
                    <p class="text-gray-500 mb-8 leading-relaxed">Gérez vos voyages, tickets, caisses et équipes depuis un tableau de bord moderne. Vendez vos tickets en ligne et en agence.</p>

                    <ul class="space-y-3 mb-10">
                        @foreach(['Gestion complète des voyages & trajets', 'Vente de tickets en ligne & au guichet', 'Tableau de bord financier temps réel', 'Gestion des équipes & gares', 'QR codes & PDF automatiques'] as $item)
                        <li class="flex items-center gap-3 text-sm text-gray-700">
                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('auth.register.step1') }}" class="flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition-colors shadow-md">
                            Commencer gratuitement
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('divers.contact') }}" class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-200 hover:border-amber-300 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                            Nous contacter
                        </a>
                    </div>
                </div>

                <div class="bg-gray-900 p-10 lg:p-14 flex flex-col justify-center">
                    <p class="text-gray-400 text-sm mb-6 uppercase tracking-widest font-semibold">Tableau de bord compagnie</p>

                    <div class="space-y-3">
                        @php
                        $demoStats = [
                            ['label' => 'Tickets vendus aujourd\'hui', 'value' => '47', 'change' => '+12%', 'up' => true],
                            ['label' => 'Recettes du mois', 'value' => '2 350 000 F', 'change' => '+8%', 'up' => true],
                            ['label' => 'Voyages planifiés', 'value' => '23', 'change' => '0', 'up' => true],
                        ];
                        @endphp
                        @foreach($demoStats as $ds)
                        <div class="bg-gray-800 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs">{{ $ds['label'] }}</p>
                                <p class="text-white font-bold text-lg mt-0.5">{{ $ds['value'] }}</p>
                            </div>
                            @if($ds['change'] !== '0')
                            <span class="text-{{ $ds['up'] ? 'green' : 'red' }}-400 text-sm font-semibold bg-{{ $ds['up'] ? 'green' : 'red' }}-400/10 px-2.5 py-1 rounded-lg">{{ $ds['change'] }}</span>
                            @endif
                        </div>
                        @endforeach

                        <div class="bg-gray-800 rounded-xl p-4 mt-2">
                            <p class="text-gray-500 text-xs mb-3">Recettes 6 derniers mois</p>
                            <div class="flex items-end gap-1.5 h-16">
                                @foreach([40, 65, 55, 80, 70, 95] as $h)
                                <div class="flex-1 bg-amber-500/20 rounded-sm relative overflow-hidden" style="height: {{ $h }}%">
                                    <div class="absolute bottom-0 left-0 right-0 bg-amber-500 rounded-sm" style="height: {{ $h }}%"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ FAQ ════════════════════════════════════════════════ --}}
<section class="py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-amber-500 text-sm font-semibold uppercase tracking-widest">FAQ</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">Questions fréquentes</h2>
        </div>

        <div x-data="{ open: null }" class="space-y-3">
            @php
            $faqs = [
                ['q' => 'Comment puis-je réserver un ticket ?', 'a' => 'Inscrivez-vous gratuitement, recherchez votre trajet, sélectionnez votre siège et payez via Orange Money ou espèces. Votre ticket QR code est envoyé immédiatement par email.'],
                ['q' => 'Mon ticket est-il remboursable ?', 'a' => 'Les conditions de remboursement varient selon la compagnie. Vous pouvez également transférer votre ticket à une autre personne directement depuis votre espace client.'],
                ['q' => 'Comment fonctionne le paiement Orange Money ?', 'a' => 'Lors de votre achat, choisissez "Orange Money", entrez votre numéro de téléphone et validez le paiement depuis votre application Orange Money. La transaction est instantanée et sécurisée.'],
                ['q' => 'Que faire si je perds mon ticket ?', 'a' => 'Pas d\'inquiétude ! Votre ticket est sauvegardé dans votre espace client. Vous pouvez le re-télécharger ou le faire renvoyer par email à tout moment.'],
                ['q' => 'Ma compagnie peut-elle rejoindre la plateforme ?', 'a' => 'Oui ! Contactez-nous via le formulaire de contact ou inscrivez votre compagnie directement. Après vérification, vous aurez accès à votre tableau de bord complet.'],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
            <div class="border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors">
                    <span class="font-semibold text-gray-900 text-sm pr-4">{{ $faq['q'] }}</span>
                    <svg :class="open === {{ $i }} ? 'rotate-180 text-amber-500' : 'text-gray-400'"
                         class="w-5 h-5 flex-shrink-0 transition-transform"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-5 pb-5">
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ FOOTER CTA ════════════════════════════════════════════════ --}}
<section class="py-20 bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">Prêt à voyager ?</h2>
        <p class="text-gray-400 mb-8">Inscrivez-vous gratuitement et réservez votre premier ticket en moins de 2 minutes.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('auth.register.step1') }}" class="px-8 py-3.5 bg-amber-500 hover:bg-amber-400 text-white font-bold rounded-xl transition-colors shadow-lg">
                Créer mon compte gratuit
            </a>
            <a href="{{ route('voyage.index') }}" class="px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl transition-colors border border-white/10">
                Explorer les voyages
            </a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════ FOOTER ════════════════════════════════════════════════ --}}
<footer class="bg-gray-950 text-gray-400">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </div>
                    <span class="text-white font-bold text-lg">FasoTravel</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs">La plateforme de référence pour la vente et gestion de tickets de transport au Burkina Faso et en Afrique de l'Ouest.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Voyageurs</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('voyage.index') }}" class="hover:text-white transition-colors">Rechercher un voyage</a></li>
                    <li><a href="{{ route('client.compagnies.index') }}" class="hover:text-white transition-colors">Les compagnies</a></li>
                    @auth
                    <li><a href="{{ route('ticket.myTickets') }}" class="hover:text-white transition-colors">Mes tickets</a></li>
                    @else
                    <li><a href="{{ route('auth.register.step1') }}" class="hover:text-white transition-colors">Créer un compte</a></li>
                    @endauth
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Entreprise</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('divers.about-us') }}" class="hover:text-white transition-colors">À propos</a></li>
                    <li><a href="{{ route('divers.contact') }}" class="hover:text-white transition-colors">Contact</a></li>
                    <li><a href="{{ route('divers.politique-confidentialite') }}" class="hover:text-white transition-colors">Politique de confidentialité</a></li>
                    <li><a href="{{ route('divers.termes-et-conditions') }}" class="hover:text-white transition-colors">Conditions d'utilisation</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs">© {{ date('Y') }} FasoTravel. Tous droits réservés.</p>
            <p class="text-xs">Construit avec ❤️ pour le Burkina Faso</p>
        </div>
    </div>
</footer>

</body>
</html>
