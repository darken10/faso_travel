<div x-data="{ photoPreview: null }" wire:submit="updateProfileInformation">

    {{-- Photo de profil --}}
    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-surface-100 dark:border-surface-700">
            <div class="relative">
                <div x-show="! photoPreview">
                    <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}"
                         class="w-16 h-16 rounded-xl object-cover">
                </div>
                <div x-show="photoPreview" style="display:none;" class="w-16 h-16 rounded-xl bg-cover bg-center bg-no-repeat"
                     x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </div>
            </div>
            <div>
                <input type="file" id="photo" class="hidden" wire:model.live="photo"
                    x-ref="photo"
                    x-on:change="
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; };
                        reader.readAsDataURL($refs.photo.files[0]);
                    ">
                <button type="button"
                    x-on:click.prevent="$refs.photo.click()"
                    class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                    Changer la photo
                </button>
                @if ($this->user->profile_photo_path)
                    <button type="button" wire:click="deleteProfilePhoto"
                        class="ml-3 text-sm text-surface-400 hover:text-red-500 transition-colors">
                        Supprimer
                    </button>
                @endif
                <p class="text-xs text-surface-400 mt-1">JPG, PNG ou GIF. Max 1 Mo.</p>
                <x-input-error for="photo" class="mt-1" />
            </div>
        </div>
    @endif

    {{-- Champs --}}
    <div class="space-y-4">

        <div>
            <label for="name" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Nom complet</label>
            <input id="name" type="text" wire:model="state.name" required autocomplete="name"
                class="w-full px-4 py-2.5 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 text-surface-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm">
            <x-input-error for="name" class="mt-1" />
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Adresse e-mail</label>
            <input id="email" type="email" wire:model="state.email" required autocomplete="username"
                class="w-full px-4 py-2.5 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 text-surface-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm">
            <x-input-error for="email" class="mt-1" />

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    Email non vérifié.
                    <button type="button" wire:click.prevent="sendEmailVerification"
                        class="underline font-medium hover:text-amber-700 transition-colors">
                        Renvoyer la vérification
                    </button>
                </p>
                @if ($this->verificationLinkSent)
                    <p class="mt-1 text-xs text-green-600">Lien envoyé !</p>
                @endif
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-surface-100 dark:border-surface-700">
        <x-action-message on="saved" class="text-sm text-green-600 dark:text-green-400">
            Enregistré.
        </x-action-message>
        <button type="button" wire:click="updateProfileInformation"
            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
            Enregistrer
        </button>
    </div>
</div>
