<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administration — {{ config('app.name', 'Liptra') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-950 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500 shadow-lg mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Administration</h1>
        <p class="text-sm text-gray-400 mt-1">{{ config('app.name', 'Liptra') }} — Accès restreint</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl p-8">

        @if ($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-900/40 border border-red-700 text-sm text-red-300">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @session('status')
            <div class="mb-5 p-3 rounded-xl bg-green-900/40 border border-green-700 text-sm text-green-300">
                {{ $value }}
            </div>
        @endsession

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white">Connexion administrateur</h2>
            <p class="text-sm text-gray-400 mt-1">Réservé aux administrateurs autorisés</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Adresse email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none transition text-sm"
                    placeholder="admin@liptra.net">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Mot de passe</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none transition text-sm"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-600 bg-gray-800 text-amber-500">
                    <span class="text-sm text-gray-400">Se souvenir de moi</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-amber-400 hover:text-amber-300">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-xl transition-colors text-sm">
                Accéder à l'administration
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-600 mt-6">
        &copy; {{ date('Y') }} {{ config('app.name', 'Liptra') }}. Accès sécurisé.
    </p>
</div>

@livewireScripts
</body>
</html>
