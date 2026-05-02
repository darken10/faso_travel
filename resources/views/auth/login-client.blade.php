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
            <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">Connectez-vous avec votre numéro de téléphone</p>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-success-50 border border-success-200 text-sm text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="phone" value="Numéro de téléphone" />
                <div class="flex mt-1">
                    <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-surface-200 dark:border-surface-600 bg-surface-50 dark:bg-surface-700 text-surface-500 dark:text-surface-400 text-sm font-medium select-none">
                        +226
                    </span>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autofocus inputmode="numeric"
                        class="flex-1 rounded-r-xl border-surface-200 dark:border-surface-600 dark:bg-surface-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 text-sm shadow-sm"
                        placeholder="70 00 00 00">
                </div>
            </div>

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
