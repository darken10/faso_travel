<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Espace Compagnie — {{ config('app.name', 'Liptra') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-800 to-blue-900 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-500 shadow-lg mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold ">Espace Compagnie</h1>
        <p class="text-sm text-blue-200 mt-1">{{ config('app.name', 'Liptra') }} — Portail partenaires</p>
    </div>

    <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl shadow-2xl p-8">

        @if ($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-sm text-red-200">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @session('status')
            <div class="mb-5 p-3 rounded-xl bg-green-500/20 border border-green-400/30 text-sm text-green-200">
                {{ $value }}
            </div>
        @endsession

        <div class="mb-6">
            <h2 class="text-lg font-semibold">Connexion</h2>
            <p class="text-sm text-blue-200 mt-1">Connectez-vous à votre espace de gestion</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-blue-100 mb-1">Adresse email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent outline-none transition text-sm"
                    placeholder="contact@compagnie.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-blue-100 mb-1">Mot de passe</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent outline-none transition text-sm"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-white/30 bg-white/10 text-blue-400">
                    <span class="text-sm text-blue-200">Se souvenir de moi</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-300 hover:text-white transition-colors">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-400 text-white font-semibold rounded-xl transition-colors text-sm">
                Accéder à mon espace
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-blue-300/60 mt-6">
        &copy; {{ date('Y') }} {{ config('app.name', 'Liptra') }}. Tous droits réservés.
    </p>
</div>

@livewireScripts
</body>
</html>
