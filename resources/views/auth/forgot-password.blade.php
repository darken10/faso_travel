<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Mot de passe oublié</h2>
            <p class="mt-2 text-sm text-surface-500 dark:text-surface-400 leading-relaxed">
                Choisissez comment réinitialiser votre mot de passe.
            </p>
        </div>

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-success-50 border border-success-200 text-sm text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        {{-- Option 1 : Email --}}
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5 mb-6">
            @csrf

            <div class="p-4 rounded-xl border border-surface-200 dark:border-surface-700 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-9 h-9 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-surface-900 dark:text-white">Via email</p>
                        <p class="text-xs text-surface-500">Recevoir un lien de réinitialisation</p>
                    </div>
                </div>

                <div>
                    <x-label for="email" value="Adresse email" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" autofocus autocomplete="username" placeholder="votre@email.com" />
                </div>

                <x-button class="w-full justify-center">
                    Envoyer le lien
                </x-button>
            </div>
        </form>

        {{-- Séparateur --}}
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-surface-200 dark:border-surface-700"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="px-3 bg-white dark:bg-surface-800 text-surface-400">ou</span>
            </div>
        </div>

        {{-- Option 2 : Téléphone --}}
        <a href="{{ route('password.phone.form') }}"
            class="flex items-center gap-3 p-4 rounded-xl border border-surface-200 dark:border-surface-700 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors group">
            <div class="flex-shrink-0 w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-surface-900 dark:text-white">Via numéro de téléphone</p>
                <p class="text-xs text-surface-500">Recevoir un code par SMS ou WhatsApp</p>
            </div>
            <svg class="w-4 h-4 text-surface-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <p class="text-center text-sm text-surface-500 dark:text-surface-400 mt-6">
            <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
                &larr; Retour à la connexion
            </a>
        </p>
    </x-authentication-card>
</x-guest-layout>
