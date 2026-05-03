<div x-data="{ step: 1, maxStep: 3 }" class="space-y-6">

    {{-- Progress Stepper --}}
    <div class="flex items-center justify-center gap-0 px-4 py-6">
        <template x-for="s in maxStep" :key="s">
            <div class="flex items-center">
                <div class="flex flex-col items-center">
                    <div :class="step >= s
                        ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30 scale-110'
                        : 'bg-surface-200 text-surface-500 dark:bg-surface-700 dark:text-surface-400'"
                        class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300">
                        <template x-if="step > s">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </template>
                        <template x-if="step <= s"><span x-text="s"></span></template>
                    </div>
                    <span :class="step >= s ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-surface-400 dark:text-surface-500'"
                          class="text-[11px] mt-1.5 whitespace-nowrap transition-colors">
                        <template x-if="s === 1">Siège & Type</template>
                        <template x-if="s === 2">Pour qui ?</template>
                        <template x-if="s === 3">Confirmation</template>
                    </span>
                </div>
                <template x-if="s < maxStep">
                    <div :class="step > s ? 'bg-primary-500' : 'bg-surface-200 dark:bg-surface-700'"
                         class="w-12 sm:w-20 h-0.5 mx-2 rounded-full transition-colors duration-300 mb-5"></div>
                </template>
            </div>
        </template>
    </div>

    {{-- Step 1: Siège & Type de voyage --}}
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
        <div class="card space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <div>
                    <h3 class="card-header text-lg">Choisissez votre siège</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Sélectionnez votre place et le type de trajet</p>
                </div>
            </div>

            {{-- Seat selection --}}
            <div>
                <label for="numero_chaise" class="input-label">Numéro de siège</label>
                <select wire:model="numero_chaise" id="numero_chaise" class="input">
                    <option value="">-- Choisir un siège --</option>
                    @forelse($chaiseDispo as $chaise)
                        <option value="{{ $chaise }}">Siège N° {{ $chaise }}</option>
                    @empty
                        <option value="" disabled>Aucune place disponible</option>
                    @endforelse
                </select>
                <x-input-error for="numero_chaise" />
            </div>

            {{-- Trip type --}}
            <div>
                <label class="input-label">Type de voyage</label>
                <div class="grid grid-cols-2 gap-3">
                    <label :class="$wire.voyageType === 'aller_simple'
                        ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-900/20 dark:border-primary-400'
                        : 'border-surface-200 bg-white dark:bg-surface-800 dark:border-surface-600 hover:border-surface-300 dark:hover:border-surface-500'"
                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200">
                        <input type="radio" wire:model.live="voyageType" value="aller_simple" class="sr-only">
                        <div :class="$wire.voyageType === 'aller_simple' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-400'"
                             class="transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-surface-900 dark:text-white">Aller Simple</span>
                            <span class="block text-xs text-surface-500 dark:text-surface-400">Un seul trajet</span>
                        </div>
                    </label>
                    <label :class="$wire.voyageType === 'aller_retour'
                        ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-900/20 dark:border-primary-400'
                        : 'border-surface-200 bg-white dark:bg-surface-800 dark:border-surface-600 hover:border-surface-300 dark:hover:border-surface-500'"
                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200">
                        <input type="radio" wire:model.live="voyageType" value="aller_retour" class="sr-only">
                        <div :class="$wire.voyageType === 'aller_retour' ? 'text-primary-600 dark:text-primary-400' : 'text-surface-400'"
                             class="transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-surface-900 dark:text-white">Aller-Retour</span>
                            <span class="block text-xs text-surface-500 dark:text-surface-400">Trajet complet</span>
                        </div>
                    </label>
                </div>
                <x-input-error for="voyageType" />
            </div>

            <button @click="step = 2" class="btn-primary w-full mt-2">
                Continuer
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </button>
        </div>
    </div>

    {{-- Step 2: Pour qui ? --}}
    <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
        <div class="card space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-accent-100 dark:bg-accent-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-600 dark:text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <div>
                    <h3 class="card-header text-lg">Pour qui achetez-vous ?</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Choisissez le bénéficiaire du ticket</p>
                </div>
            </div>

            {{-- Buy for selector --}}
            <div class="grid grid-cols-2 gap-3">
                <label :class="$wire.buyFor === 'self'
                    ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-900/20 dark:border-primary-400'
                    : 'border-surface-200 bg-white dark:bg-surface-800 dark:border-surface-600 hover:border-surface-300'"
                    class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 cursor-pointer transition-all duration-200">
                    <input type="radio" wire:model.live="buyFor" value="self" class="sr-only">
                    <div :class="$wire.buyFor === 'self' ? 'bg-primary-500 text-white' : 'bg-surface-100 text-surface-500 dark:bg-surface-700 dark:text-surface-400'"
                         class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-surface-900 dark:text-white">Moi-même</span>
                </label>
                <label :class="$wire.buyFor === 'other'
                    ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-900/20 dark:border-primary-400'
                    : 'border-surface-200 bg-white dark:bg-surface-800 dark:border-surface-600 hover:border-surface-300'"
                    class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 cursor-pointer transition-all duration-200">
                    <input type="radio" wire:model.live="buyFor" value="other" class="sr-only">
                    <div :class="$wire.buyFor === 'other' ? 'bg-primary-500 text-white' : 'bg-surface-100 text-surface-500 dark:bg-surface-700 dark:text-surface-400'"
                         class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-surface-900 dark:text-white">Autre personne</span>
                </label>
            </div>

            {{-- Other person form --}}
            @if ($buyFor === 'other')
            <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="p-5 bg-surface-50 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-xl space-y-4">
                <h4 class="text-sm font-semibold text-surface-700 dark:text-surface-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                    Informations du passager
                </h4>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="first_name" class="input-label">Nom</label>
                        <input wire:model.defer="otherPerson.first_name" id="first_name" type="text" class="input" placeholder="Ex: Ouédraogo" />
                        <x-input-error for="otherPerson.first_name" />
                    </div>
                    <div>
                        <label for="last_name" class="input-label">Prénom</label>
                        <input wire:model.defer="otherPerson.last_name" id="last_name" type="text" class="input" placeholder="Ex: Awa" />
                        <x-input-error for="otherPerson.last_name" />
                    </div>
                </div>

                <div>
                    <label class="input-label">Genre</label>
                    <div class="flex gap-2">
                        @foreach(['Homme', 'Femme', 'Autre'] as $genre)
                        <label :class="$wire.otherPerson?.sexe === '{{ $genre }}'
                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300 dark:border-primary-400'
                            : 'border-surface-200 text-surface-600 dark:border-surface-600 dark:text-surface-400 hover:border-surface-300'"
                            class="flex-1 text-center py-2.5 px-3 rounded-lg border-2 text-sm font-medium cursor-pointer transition-all duration-200">
                            <input type="radio" wire:model.live="otherPerson.sexe" value="{{ $genre }}" class="sr-only">
                            {{ $genre }}
                        </label>
                        @endforeach
                    </div>
                    <x-input-error for="otherPerson.sexe" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="numero_identifiant" class="input-label">N° Identifiant</label>
                        <input wire:model.defer="otherPerson.numero_identifiant" id="numero_identifiant" type="text" class="input" placeholder="CNIB / Passport" />
                        <x-input-error for="otherPerson.numero_identifiant" />
                    </div>
                    <div>
                        <label for="numero" class="input-label">Téléphone</label>
                        <input wire:model.defer="otherPerson.numero" id="numero" type="tel" class="input" placeholder="70 00 00 00" />
                        <x-input-error for="otherPerson.numero" />
                    </div>
                </div>

                <div>
                    <label for="email" class="input-label">Email <span class="text-surface-400 font-normal">(facultatif)</span></label>
                    <input wire:model.defer="otherPerson.email" id="email" type="email" class="input" placeholder="email@exemple.com" />
                    <x-input-error for="otherPerson.email" />
                </div>

                <div>
                    <label for="lien_relation" class="input-label">Lien de relation</label>
                    <select wire:model.defer="otherPerson.lien_relation" id="lien_relation" class="input">
                        <option value="">-- Sélectionner --</option>
                        @foreach($liens as $lien)
                            <option value="{{ $lien }}">{{ $lien }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="otherPerson.lien_relation" />
                </div>

                <label class="flex items-start gap-3 p-3 rounded-lg bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-600 cursor-pointer">
                    <input wire:model.defer="otherPerson.accepter" required id="accepter" type="checkbox" class="mt-0.5 w-4 h-4 text-primary-600 border-surface-300 rounded focus:ring-primary-500 dark:border-surface-600 dark:bg-surface-700 dark:focus:ring-primary-400">
                    <span class="text-sm text-surface-600 dark:text-surface-400">J'accepte les <a href="{{ route('divers.termes-et-conditions') }}" class="text-primary-600 dark:text-primary-400 underline">conditions générales</a> pour l'achat de ce ticket.</span>
                </label>
                <x-input-error for="otherPerson.accepter" />
            </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button @click="step = 1" class="btn-secondary flex-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Retour
                </button>
                <button @click="step = 3" class="btn-primary flex-1">
                    Continuer
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Step 3: Confirmation --}}
    <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
        <div class="card space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-success-100 dark:bg-success-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="card-header text-lg">Récapitulatif</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Vérifiez avant de confirmer votre achat</p>
                </div>
            </div>

            <div class="bg-surface-50 dark:bg-surface-900 rounded-xl p-4 space-y-3 border border-surface-200 dark:border-surface-700">
                <div class="flex justify-between items-center py-2 border-b border-surface-200 dark:border-surface-700">
                    <span class="text-sm text-surface-500 dark:text-surface-400">Siège</span>
                    <span class="text-sm font-semibold text-surface-900 dark:text-white" x-text="$wire.numero_chaise ? 'N° ' + $wire.numero_chaise : 'Non sélectionné'"></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-surface-200 dark:border-surface-700">
                    <span class="text-sm text-surface-500 dark:text-surface-400">Type de voyage</span>
                    <span class="badge-primary" x-text="$wire.voyageType === 'aller_retour' ? 'Aller-Retour' : 'Aller Simple'"></span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-surface-500 dark:text-surface-400">Passager</span>
                    <span class="text-sm font-semibold text-surface-900 dark:text-white" x-text="$wire.buyFor === 'self' ? 'Moi-même' : 'Autre personne'"></span>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button @click="step = 2" class="btn-secondary flex-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Retour
                </button>
                <button wire:click="submit" wire:loading.attr="disabled" wire:target="submit" class="btn-success flex-1 relative">
                    <span wire:loading.remove wire:target="submit" class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                        Confirmer l'achat
                    </span>
                    <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Traitement...
                    </span>
                </button>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-success-50 border border-success-200 text-success-700 px-4 py-3 rounded-xl dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-xl dark:bg-danger-500/10 dark:border-danger-500/20 dark:text-danger-400">
            {{ session('error') }}
        </div>
    @endif
</div>
