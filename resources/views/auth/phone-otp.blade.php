<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Code de vérification — {{ config('app.name', 'Liptra') }}</title>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name', 'Liptra') }}</h1>
        <p class="text-sm text-gray-500 mt-1">Vérification du code</p>
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
            <h2 class="text-lg font-semibold text-gray-900">Entrez votre code</h2>
            <p class="text-sm text-gray-500 mt-1">
                Un code à 6 chiffres a été envoyé au
                <span class="font-medium text-gray-700">{{ $phone }}</span>.
            </p>
        </div>

        {{-- Mode simulation (hors production) --}}
        @if ($simulation)
            <div class="mb-5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
                <span class="font-semibold">Mode simulation —</span> code : <span class="font-mono font-bold tracking-widest">{{ $simulation }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.phone.verify-otp') }}" class="space-y-5">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code à 6 chiffres</label>
                <input id="code" type="text" name="code" required inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-center text-2xl font-mono tracking-widest"
                    placeholder="000000"
                    autofocus>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-sm">
                Vérifier le code
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center space-y-2">
            <p class="text-sm text-gray-500">Vous n'avez pas reçu de code ?</p>
            <a href="{{ route('auth.phone.form') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                Renvoyer le code
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        &copy; {{ date('Y') }} {{ config('app.name', 'Liptra') }}. Tous droits réservés.
    </p>
</div>

<script>
    // Auto-submit quand 6 chiffres sont saisis
    document.getElementById('code').addEventListener('input', function () {
        if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
            this.closest('form').submit();
        }
    });
</script>

@livewireScripts
</body>
</html>
