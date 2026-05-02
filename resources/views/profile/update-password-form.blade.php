<div wire:submit="updatePassword">

    <div class="space-y-4">

        <div>
            <label for="current_password" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">
                Mot de passe actuel
            </label>
            <input id="current_password" type="password" wire:model="state.current_password" autocomplete="current-password"
                class="w-full px-4 py-2.5 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 text-surface-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                placeholder="••••••••">
            <x-input-error for="current_password" class="mt-1" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">
                Nouveau mot de passe
            </label>
            <input id="password" type="password" wire:model="state.password" autocomplete="new-password"
                class="w-full px-4 py-2.5 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 text-surface-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                placeholder="Minimum 8 caractères">
            <x-input-error for="password" class="mt-1" />

            {{-- Indicateur de force --}}
            <div class="mt-2 flex gap-1" x-data="passwordStrength()" x-init="watch()">
                <template x-for="i in 4">
                    <div class="h-1 flex-1 rounded-full transition-colors"
                         :class="i <= strength ? colors[strength] : 'bg-gray-200 dark:bg-surface-600'">
                    </div>
                </template>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">
                Confirmer le mot de passe
            </label>
            <input id="password_confirmation" type="password" wire:model="state.password_confirmation" autocomplete="new-password"
                class="w-full px-4 py-2.5 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 text-surface-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                placeholder="••••••••">
            <x-input-error for="password_confirmation" class="mt-1" />
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-surface-100 dark:border-surface-700">
        <x-action-message on="saved" class="text-sm text-green-600 dark:text-green-400">
            Mot de passe mis à jour.
        </x-action-message>
        <button type="button" wire:click="updatePassword"
            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
            Mettre à jour
        </button>
    </div>
</div>

<script>
    function passwordStrength() {
        return {
            strength: 0,
            colors: { 1: 'bg-red-400', 2: 'bg-orange-400', 3: 'bg-yellow-400', 4: 'bg-green-500' },
            watch() {
                const input = document.getElementById('password');
                if (!input) return;
                input.addEventListener('input', () => {
                    const v = input.value;
                    let s = 0;
                    if (v.length >= 8)          s++;
                    if (/[A-Z]/.test(v))        s++;
                    if (/[0-9]/.test(v))        s++;
                    if (/[^A-Za-z0-9]/.test(v)) s++;
                    this.strength = s;
                });
            }
        };
    }
</script>
