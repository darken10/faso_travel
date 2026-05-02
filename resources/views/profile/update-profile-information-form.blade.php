<div x-data="{ photoPreview: null }">

    {{-- Photo de profil --}}
    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
        <div class="flex items-center gap-5 mb-6 pb-6 border-b border-surface-100 dark:border-surface-700">
            {{-- Avatar actuel / preview --}}
            <div class="relative flex-shrink-0">
                <div x-show="! photoPreview">
                    <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}"
                         class="w-20 h-20 rounded-xl object-cover ring-2 ring-surface-200 dark:ring-surface-600">
                </div>
                <div x-show="photoPreview" style="display:none;"
                     class="w-20 h-20 rounded-xl bg-cover bg-center bg-no-repeat ring-2 ring-primary-300"
                     x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </div>
            </div>

            {{-- Actions photo --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-surface-900 dark:text-white mb-1">Photo de profil</p>
                <p class="text-xs text-surface-500 dark:text-surface-400 mb-3">JPG, PNG ou GIF — max 1 Mo</p>

                <input type="file" id="photo" class="hidden" wire:model.live="photo"
                    x-ref="photo"
                    x-on:change="
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; };
                        reader.readAsDataURL($refs.photo.files[0]);
                    ">

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" x-on:click.prevent="$refs.photo.click()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900/50 border border-primary-200 dark:border-primary-700 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Changer la photo
                    </button>

                    @if ($this->user->profile_photo_path)
                        <button type="button" wire:click="deleteProfilePhoto"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 border border-red-200 dark:border-red-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer
                        </button>
                    @endif
                </div>
                <x-input-error for="photo" class="mt-2" />
            </div>
        </div>
    @endif

    {{-- Formulaire --}}
    <form wire:submit.prevent="updateProfileInformation" class="space-y-4">

        <div>
            <x-label for="name" value="Nom complet" />
            <x-input id="name" type="text" class="block mt-1 w-full" wire:model="state.name"
                required autocomplete="name" placeholder="Votre nom complet" />
            <x-input-error for="name" class="mt-1" />
        </div>

        <div>
            <x-label for="email" value="Adresse e-mail" />
            <x-input id="email" type="email" class="block mt-1 w-full" wire:model="state.email"
                required autocomplete="username" placeholder="votre@email.com" />
            <x-input-error for="email" class="mt-1" />

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <div class="mt-2 flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Email non vérifié.
                    <button type="button" wire:click.prevent="sendEmailVerification"
                        class="underline font-medium hover:text-amber-700 dark:hover:text-amber-300 transition-colors">
                        Renvoyer le lien
                    </button>
                </div>
                @if ($this->verificationLinkSent)
                    <p class="mt-1 text-xs text-success-600 dark:text-success-400">Lien envoyé !</p>
                @endif
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4 border-t border-surface-100 dark:border-surface-700">
            <x-action-message on="saved" class="text-sm text-success-600 dark:text-success-400 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistré
            </x-action-message>
            <x-button>
                Enregistrer les modifications
            </x-button>
        </div>
    </form>
</div>
