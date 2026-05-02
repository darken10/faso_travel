<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero --}}
            <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-surface-200 dark:border-surface-700 overflow-hidden">
                <div class="h-24 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                <div class="px-6 pb-6">
                    <div class="flex items-end gap-5 -mt-10">
                        <div class="relative">
                            <img src="{{ auth()->user()->profile_photo_url }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-20 h-20 rounded-2xl object-cover ring-4 ring-white dark:ring-surface-800 shadow-lg">
                        </div>
                        <div class="pb-1 flex-1 min-w-0">
                            <h1 class="text-xl font-bold text-surface-900 dark:text-white truncate">
                                {{ auth()->user()->name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1">
                                @if(auth()->user()->numero)
                                    <span class="flex items-center gap-1 text-sm text-surface-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        +226 {{ auth()->user()->numero }}
                                    </span>
                                @endif
                                @if(auth()->user()->email)
                                    <span class="flex items-center gap-1 text-sm text-surface-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ auth()->user()->email }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations du profil --}}
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-surface-200 dark:border-surface-700">
                    <div class="px-6 py-5 border-b border-surface-100 dark:border-surface-700">
                        <h2 class="text-base font-semibold text-surface-900 dark:text-white">Informations personnelles</h2>
                        <p class="text-sm text-surface-500 mt-0.5">Mettez à jour vos informations de profil.</p>
                    </div>
                    <div class="p-6">
                        @livewire('profile.update-profile-information-form')
                    </div>
                </div>
            @endif

            {{-- Changer le mot de passe --}}
            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-surface-200 dark:border-surface-700">
                    <div class="px-6 py-5 border-b border-surface-100 dark:border-surface-700">
                        <h2 class="text-base font-semibold text-surface-900 dark:text-white">Sécurité du compte</h2>
                        <p class="text-sm text-surface-500 mt-0.5">Utilisez un mot de passe long et aléatoire pour sécuriser votre compte.</p>
                    </div>
                    <div class="p-6">
                        @livewire('profile.update-password-form')
                    </div>
                </div>
            @endif

            {{-- Sessions actives --}}
            <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-surface-200 dark:border-surface-700">
                <div class="px-6 py-5 border-b border-surface-100 dark:border-surface-700">
                    <h2 class="text-base font-semibold text-surface-900 dark:text-white">Sessions actives</h2>
                    <p class="text-sm text-surface-500 mt-0.5">Gérez vos sessions sur d'autres appareils.</p>
                </div>
                <div class="p-6">
                    @livewire('profile.logout-other-browser-sessions-form')
                </div>
            </div>

            {{-- Authentification à deux facteurs --}}
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-surface-200 dark:border-surface-700">
                    <div class="px-6 py-5 border-b border-surface-100 dark:border-surface-700">
                        <h2 class="text-base font-semibold text-surface-900 dark:text-white">Authentification à deux facteurs</h2>
                        <p class="text-sm text-surface-500 mt-0.5">Ajoutez une sécurité supplémentaire à votre compte.</p>
                    </div>
                    <div class="p-6">
                        @livewire('profile.two-factor-authentication-form')
                    </div>
                </div>
            @endif

            {{-- Supprimer le compte --}}
            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div class="bg-white dark:bg-surface-800 rounded-2xl shadow-card border border-red-100 dark:border-red-900/30">
                    <div class="px-6 py-5 border-b border-red-100 dark:border-red-900/30">
                        <h2 class="text-base font-semibold text-red-600 dark:text-red-400">Zone de danger</h2>
                        <p class="text-sm text-surface-500 mt-0.5">La suppression de votre compte est irréversible.</p>
                    </div>
                    <div class="p-6">
                        @livewire('profile.delete-user-form')
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
