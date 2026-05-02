<div>
    <form wire:submit.prevent="updatePassword" class="space-y-4">

        <div>
            <x-label for="current_password" value="Mot de passe actuel" />
            <x-input id="current_password" type="password" class="block mt-1 w-full"
                wire:model="state.current_password" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error for="current_password" class="mt-1" />
        </div>

        <div>
            <x-label for="password" value="Nouveau mot de passe" />
            <x-input id="password" type="password" class="block mt-1 w-full"
                wire:model="state.password" autocomplete="new-password" placeholder="Minimum 8 caractères" />
            <x-input-error for="password" class="mt-1" />

            {{-- Indicateur de force --}}
            <div class="mt-2 flex gap-1.5" id="pwd-bars">
                <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="pwd-bar-1"></div>
                <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="pwd-bar-2"></div>
                <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="pwd-bar-3"></div>
                <div class="h-1 flex-1 rounded-full bg-surface-200 dark:bg-surface-600 transition-colors" id="pwd-bar-4"></div>
            </div>
            <p class="mt-1 text-xs text-surface-400 dark:text-surface-500" id="pwd-label"></p>
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmer le nouveau mot de passe" />
            <x-input id="password_confirmation" type="password" class="block mt-1 w-full"
                wire:model="state.password_confirmation" autocomplete="new-password" placeholder="••••••••" />
            <x-input-error for="password_confirmation" class="mt-1" />
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-surface-100 dark:border-surface-700">
            <x-action-message on="saved" class="text-sm text-success-600 dark:text-success-400 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Mot de passe mis à jour
            </x-action-message>
            <x-button>
                Mettre à jour
            </x-button>
        </div>
    </form>
</div>

<script>
    (function() {
        const input = document.getElementById('password');
        if (!input) return;
        input.addEventListener('input', function () {
            const v = this.value;
            let s = 0;
            if (v.length >= 8)          s++;
            if (/[A-Z]/.test(v))        s++;
            if (/[0-9]/.test(v))        s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;

            const colors = { 1: 'bg-red-400', 2: 'bg-orange-400', 3: 'bg-yellow-400', 4: 'bg-green-500' };
            const labels = { 0: '', 1: 'Faible', 2: 'Moyen', 3: 'Fort', 4: 'Très fort' };

            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('pwd-bar-' + i);
                if (!bar) continue;
                bar.className = 'h-1 flex-1 rounded-full transition-colors ' +
                    (i <= s && s > 0 ? colors[s] : 'bg-surface-200 dark:bg-surface-600');
            }
            const lbl = document.getElementById('pwd-label');
            if (lbl) lbl.textContent = labels[s] || '';
        });
    })();
</script>
