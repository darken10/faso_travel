@props([
    'model',                    // Nom de la propriété Livewire recevant le fichier (ex. "logo")
    'file'      => null,        // TemporaryUploadedFile en cours d'upload, s'il y en a un
    'existing'  => null,        // Chemin de l'image déjà stockée (disk public)
    'fallback'  => null,        // Texte affiché à défaut d'image (initiales)
    'label'     => 'Image',
    'hint'      => 'PNG, JPG, WEBP ou SVG · 2 Mo maximum',
    'accent'    => 'blue',
    'shape'     => 'square',    // square | circle
    'removeAction' => null,     // Méthode Livewire supprimant l'image existante
])

@php
    $isAmber = $accent === 'amber';

    $accentText   = $isAmber ? 'text-amber-600' : 'text-blue-600';
    $accentBar    = $isAmber ? 'bg-amber-500' : 'bg-blue-600';
    $accentBorder = $isAmber ? 'border-amber-400' : 'border-blue-400';
    $accentBg     = $isAmber ? 'bg-amber-50' : 'bg-blue-50';
    $accentRing   = $isAmber ? 'focus:ring-amber-400' : 'focus:ring-blue-500';
    $accentSoft   = $isAmber ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-blue-50 text-blue-700 hover:bg-blue-100';

    $rounded = $shape === 'circle' ? 'rounded-full' : 'rounded-xl';

    // Un fichier fraîchement déposé prime sur l'image déjà enregistrée. Un dépôt
    // non prévisualisable (PDF glissé par erreur) retombe sur l'image existante :
    // temporaryUrl() lèverait une exception et casserait toute la page.
    $pendingPreview = $file && (! method_exists($file, 'isPreviewable') || $file->isPreviewable())
        ? $file->temporaryUrl()
        : null;

    $previewUrl = $pendingPreview ?? ($existing ? Storage::url($existing) : null);
    $hasImage   = $previewUrl !== null;
@endphp

<div x-data="{
        dropping: false,
        uploading: false,
        progress: 0,
        open() { $refs.input.click() },
        drop(event) {
            this.dropping = false;
            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) return;
            $refs.input.files = files;
            $refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        },
     }"
     x-on:livewire-upload-start="uploading = true; progress = 0"
     x-on:livewire-upload-finish="uploading = false; progress = 100"
     x-on:livewire-upload-cancel="uploading = false"
     x-on:livewire-upload-error="uploading = false"
     x-on:livewire-upload-progress="progress = $event.detail.progress"
     x-on:dragover.prevent="dropping = true"
     x-on:dragleave.prevent="dropping = false"
     x-on:drop.prevent="drop($event)"
     class="space-y-2">

    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>

    <div class="flex items-center gap-4 rounded-xl border-2 border-dashed p-4 transition-colors"
         :class="dropping ? '{{ $accentBorder }} {{ $accentBg }}' : 'border-gray-200 bg-gray-50/50'">

        {{-- Aperçu --}}
        <button type="button" x-on:click="open()"
                class="relative w-20 h-20 flex-shrink-0 {{ $rounded }} overflow-hidden border border-gray-200 bg-white group focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $accentRing }}"
                title="Choisir une image">
            @if($hasImage)
                <img src="{{ $previewUrl }}" alt="{{ $label }}" class="w-full h-full object-cover">
            @else
                <span class="w-full h-full flex items-center justify-center text-gray-300 font-bold text-xl">
                    {{ $fallback ?: '—' }}
                </span>
            @endif

            <span class="absolute inset-0 bg-gray-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>

            {{-- Voile de chargement --}}
            <span x-show="uploading" x-cloak class="absolute inset-0 bg-white/80 flex items-center justify-center">
                <svg class="w-5 h-5 animate-spin {{ $accentText }}" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
            </span>
        </button>

        {{-- Zone d'action --}}
        <div class="min-w-0 flex-1">
            <p class="text-sm text-gray-600">
                <button type="button" x-on:click="open()" class="font-semibold {{ $accentText }} hover:underline">
                    Choisir une image
                </button>
                <span class="text-gray-400">ou glissez-la ici</span>
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $hint }}</p>

            @if($file)
                <p class="text-xs text-gray-500 mt-1.5 truncate">
                    <span class="font-medium {{ $accentText }}">{{ $file->getClientOriginalName() }}</span>
                    <span class="text-gray-400">· {{ number_format($file->getSize() / 1024, 0, ',', ' ') }} Ko</span>
                </p>
            @endif

            <div class="flex flex-wrap items-center gap-2 mt-2">
                <button type="button" x-on:click="open()"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors {{ $accentSoft }}">
                    {{ $hasImage ? 'Remplacer' : 'Parcourir' }}
                </button>

                @if($hasImage && $removeAction)
                    <button type="button" wire:click="{{ $removeAction }}"
                            wire:confirm="Retirer cette image ?"
                            class="px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                        Retirer
                    </button>
                @endif

                @if($file)
                    <span class="text-xs text-green-600 font-medium inline-flex items-center gap-1" x-show="!uploading">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Prête à enregistrer
                    </span>
                @elseif($existing)
                    <span class="text-xs text-gray-400">Image actuelle conservée</span>
                @endif
            </div>

            {{-- Progression réelle de l'upload --}}
            <div x-show="uploading" x-cloak class="mt-2">
                <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full {{ $accentBar }} transition-all duration-150" :style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1"><span x-text="progress"></span>% envoyé</p>
            </div>
        </div>

        <input x-ref="input" type="file" wire:model="{{ $model }}" class="sr-only"
               accept="image/png,image/jpeg,image/webp,image/svg+xml">
    </div>

    @error($model)
        <p class="text-red-500 text-xs">{{ $message }}</p>
    @enderror
</div>
