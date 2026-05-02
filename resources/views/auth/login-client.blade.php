<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-slot name="registre">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                Créer un compte
            </a>
        </x-slot>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Connexion</h2>
            <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">Accédez à votre espace voyageur</p>
        </div>

        {{-- Erreurs --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-danger-50 border border-danger-200 text-sm text-danger-700 dark:bg-danger-500/10 dark:border-danger-500/20 dark:text-danger-400">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-success-50 border border-success-200 text-sm text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Identifiant unique : email ou téléphone --}}
            <div>
                <x-label for="login" value="Email ou numéro de téléphone" />
                <div class="relative mt-1">
                    <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                        <svg id="icon-phone" class="w-4 h-4 text-surface-400 transition-opacity duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <svg id="icon-email" class="w-4 h-4 text-surface-400 absolute opacity-0 transition-opacity duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input
                        id="login"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="block w-full pl-10 rounded-xl border-surface-200 dark:border-surface-600 dark:bg-surface-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 text-sm shadow-sm"
                        placeholder="Email ou numéro de téléphone"
                    >
                </div>
            </div>

            {{-- Mot de passe --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <x-label for="password" value="Mot de passe" />
                    <a href="{{ route('password.phone.form') }}" class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
                        Mot de passe oublié ?
                    </a>
                </div>
                <x-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            </div>

            <div class="flex items-center gap-2">
                <x-checkbox id="remember_me" name="remember" />
                <label for="remember_me" class="text-sm text-surface-600 dark:text-surface-400 cursor-pointer">Se souvenir de moi</label>
            </div>

            <x-button class="w-full justify-center">
                Se connecter
            </x-button>
        </form>
    </x-authentication-card>
</x-guest-layout>

<script>
    // Bascule l'icône selon que l'utilisateur saisit un email ou un téléphone
    document.getElementById('login').addEventListener('input', function () {
        const isEmail = this.value.includes('@');
        document.getElementById('icon-phone').style.opacity = isEmail ? '0' : '1';
        document.getElementById('icon-email').style.opacity = isEmail ? '1' : '0';
    });
</script>
