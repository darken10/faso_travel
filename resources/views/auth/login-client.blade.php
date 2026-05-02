<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — {{ config('app.name', 'Liptra') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 shadow-lg mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name', 'Liptra') }}</h1>
        <p class="text-sm text-gray-500 mt-1">Votre espace voyageur</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-8">

        @if ($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @session('status')
            <div class="mb-5 p-3 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700">
                {{ $value }}
            </div>
        @endsession

        {{-- Onglets Email / Téléphone --}}
        <div class="flex rounded-xl bg-gray-100 p-1 mb-6" x-data="{ tab: 'email' }">
            <button type="button"
                @click="tab = 'email'"
                :class="tab === 'email' ? 'bg-white shadow text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 py-2 rounded-lg text-sm transition-all">
                Par email
            </button>
            <a href="{{ route('auth.phone.form') }}"
                class="flex-1 py-2 rounded-lg text-sm text-center text-gray-500 hover:text-gray-700 transition-all">
                Par téléphone
            </a>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                    placeholder="votre@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-600">Se souvenir de moi</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-sm">
                Se connecter
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-500">Créer un compte</a>
        </p>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        &copy; {{ date('Y') }} {{ config('app.name', 'Liptra') }}. Tous droits réservés.
    </p>
</div>

@livewireScripts
</body>
</html>
