<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Réinitialisation par téléphone</h2>
            <p class="mt-2 text-sm text-surface-500 dark:text-surface-400 leading-relaxed">
                Saisissez votre numéro et choisissez comment recevoir votre code.
            </p>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-success-50 border border-success-200 text-sm text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('password.phone.send') }}" class="space-y-5">
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

            {{-- Choix du canal --}}
            <div>
                <x-label value="Recevoir le code via" />
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="channel" value="sms" class="sr-only peer" checked>
                        <div class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-surface-200 dark:border-surface-600 peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 transition-all">
                            <svg class="w-4 h-4 text-surface-400 peer-checked:text-primary-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span class="text-sm font-medium text-surface-700 dark:text-surface-300">SMS</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="channel" value="whatsapp" class="sr-only peer">
                        <div class="flex items-center gap-2.5 p-3 rounded-xl border-2 border-surface-200 dark:border-surface-600 peer-checked:border-success-500 peer-checked:bg-success-50 dark:peer-checked:bg-success-900/20 transition-all">
                            <svg class="w-4 h-4 text-surface-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span class="text-sm font-medium text-surface-700 dark:text-surface-300">WhatsApp</span>
                        </div>
                    </label>
                </div>
            </div>

            <x-button class="w-full justify-center">
                Envoyer le code
            </x-button>
        </form>

        <p class="text-center text-sm text-surface-500 dark:text-surface-400 mt-6">
            <a href="{{ route('password.request') }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
                &larr; Réinitialiser par email
            </a>
        </p>
    </x-authentication-card>
</x-guest-layout>
