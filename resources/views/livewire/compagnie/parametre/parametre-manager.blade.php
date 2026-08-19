<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Paramètres</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Configuration de {{ $compagnie?->name ?? 'la compagnie' }}
            </p>
        </div>

        @if($readOnly)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 text-xs font-medium self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Consultation seule
            </span>
        @endif
    </div>

    @include('livewire.partials.settings-form', ['accent' => 'blue'])
</div>
