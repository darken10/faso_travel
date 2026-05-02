<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion par téléphone — {{ config('app.name', 'Liptra') }}</title>
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
        <p class="text-sm text-gray-500 mt-1">Connexion par numéro de téléphone</p>
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

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Votre numéro de téléphone</h2>
            <p class="text-sm text-gray-500 mt-1">Nous vous enverrons un code de vérification par SMS.</p>
        </div>

        <form method="POST" action="{{ route('auth.phone.request-otp') }}" class="space-y-5">
            @csrf

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Numéro de téléphone</label>
                <div class="flex">
                    <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm font-medium">
                        +226
                    </span>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autofocus inputmode="numeric"
                        class="flex-1 px-4 py-2.5 rounded-r-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                        placeholder="70000000">
                </div>
                <p class="mt-1 text-xs text-gray-400">Saisissez votre numéro sans indicatif, sans espaces.</p>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-sm">
                Recevoir le code
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700">
                ← Connexion par email
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        &copy; {{ date('Y') }} {{ config('app.name', 'Liptra') }}. Tous droits réservés.
    </p>
</div>

@livewireScripts
</body>
</html>
