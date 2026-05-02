<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Nouveau mot de passe</h2>
            <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">
                Code envoyé via <span class="font-medium">{{ $channel === 'whatsapp' ? 'WhatsApp' : 'SMS' }}</span> au <span class="font-medium">+226 {{ $phone }}</span>
            </p>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-success-50 border border-success-200 text-sm text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
                {{ $value }}
            </div>
        @endsession

        {{-- Mode simulation --}}
        @if ($simulation)
            <div class="mb-5 p-3 rounded-xl bg-warning-50 border border-warning-200 text-sm text-warning-800 dark:bg-warning-500/10 dark:border-warning-500/20 dark:text-warning-300">
                <span class="font-semibold">Mode simulation —</span>
                code : <span class="font-mono font-bold tracking-widest text-lg">{{ $simulation }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.phone.reset') }}" class="space-y-5">
            @csrf

            {{-- Code OTP --}}
            <div>
                <x-label for="code" value="Code à 6 chiffres" />
                <input id="code" type="text" name="code" required inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    autofocus
                    class="mt-1 block w-full rounded-xl border-surface-200 dark:border-surface-600 dark:bg-surface-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 shadow-sm text-center text-2xl font-mono tracking-widest py-3"
                    placeholder="000000">
            </div>

            {{-- Séparateur --}}
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-surface-200 dark:border-surface-700"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-white dark:bg-surface-800 text-surface-400">Nouveau mot de passe</span>
                </div>
            </div>

            {{-- Nouveau mot de passe --}}
            <div>
                <x-label for="password" value="Nouveau mot de passe" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimum 8 caractères" />
                {{-- Indicateur de force --}}
                <div class="mt-2 flex gap-1" id="strength-bars">
                    <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="bar-1"></div>
                    <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="bar-2"></div>
                    <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="bar-3"></div>
                    <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="bar-4"></div>
                </div>
                <p class="mt-1 text-xs text-surface-400" id="strength-label"></p>
            </div>

            {{-- Confirmation --}}
            <div>
                <x-label for="password_confirmation" value="Confirmer le mot de passe" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            </div>

            <x-button class="w-full justify-center">
                Réinitialiser le mot de passe
            </x-button>
        </form>

        <p class="text-center text-sm text-surface-500 dark:text-surface-400 mt-6">
            <a href="{{ route('password.phone.form') }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors">
                &larr; Changer de numéro
            </a>
        </p>
    </x-authentication-card>
</x-guest-layout>

@push('scripts')
<script>
    document.getElementById('code').addEventListener('input', function () {
        if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
            document.getElementById('password').focus();
        }
    });

    document.getElementById('password').addEventListener('input', function () {
        const v = this.value;
        let s = 0;
        if (v.length >= 8)          s++;
        if (/[A-Z]/.test(v))        s++;
        if (/[0-9]/.test(v))        s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;

        const barColors = { 1: 'bg-red-400', 2: 'bg-orange-400', 3: 'bg-yellow-400', 4: 'bg-green-500' };
        const labels    = { 0: '', 1: 'Faible', 2: 'Moyen', 3: 'Fort', 4: 'Très fort' };

        for (let i = 1; i <= 4; i++) {
            const bar = document.getElementById('bar-' + i);
            bar.className = 'h-1 flex-1 rounded-full transition-colors ' + (i <= s && s > 0 ? barColors[s] : 'bg-surface-200 dark:bg-surface-600');
        }
        document.getElementById('strength-label').textContent = labels[s];
    });
</script>
@endpush
