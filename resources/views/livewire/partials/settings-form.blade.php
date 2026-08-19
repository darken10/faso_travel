@php
    /**
     * Formulaire de paramétrage partagé par le panel compagnie et le panel admin.
     *
     * @var array  $catalogue        Groupes et définitions à afficher.
     * @var string $activeGroup      Onglet courant.
     * @var array  $customizedKeys   Clés dont la compagnie a une valeur propre.
     * @var bool   $readOnly         Consultation seule.
     * @var string $accent           'blue' (compagnie) ou 'amber' (admin).
     */
    $accent = $accent ?? 'blue';

    $tabActive = $accent === 'amber' ? 'bg-amber-500 text-white' : 'bg-blue-600 text-white';
    $btnPrimary = $accent === 'amber' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-blue-600 hover:bg-blue-700';
    $dotAccent = $accent === 'amber' ? 'bg-amber-500' : 'bg-blue-600';
@endphp

<div class="flex flex-col lg:flex-row gap-6">

    {{-- Onglets des groupes --}}
    <nav class="lg:w-64 flex-shrink-0 ">
        <div class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
            @foreach ($catalogue as $slug => $entry)
                @php $group = $entry['group']; @endphp
                <button type="button" wire:click="selectGroup('{{ $slug }}')"
                    class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors text-left
                               {{ $activeGroup === $slug ? $tabActive : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="{{ $group->icon() }}" />
                    </svg>
                    <span>{{ $group->label() }}</span>
                    @if ($group->isAdminOnly())
                        <span
                            class="ml-auto text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded {{ $activeGroup === $slug ? 'bg-white/20' : 'bg-amber-100 text-amber-700' }}">Admin</span>
                    @endif
                </button>
            @endforeach
        </div>
    </nav>

    {{-- Champs du groupe actif --}}
    <div class="flex-1 min-w-0">
        @php $entry = $catalogue[$activeGroup] ?? null; @endphp

        @if ($entry)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ $entry['group']->label() }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $entry['group']->description() }}</p>
                </div>

                <div class="px-5 divide-y divide-gray-100">
                    @foreach ($entry['definitions'] as $definition)
                        @php
                            $disabled = $readOnly || ($definition->isAdminOnly() && !$canManageAdvanced);
                        @endphp
                        <div class="relative">
                            @if (in_array($definition->key->value, $customizedKeys, true))
                                <span class="absolute left-[-14px] top-6 w-1.5 h-1.5 rounded-full {{ $dotAccent }}"
                                    title="Valeur personnalisée pour cette compagnie"></span>
                            @endif
                            <x-settings.field :definition="$definition" :model="'values.' . $definition->key->value" :accent="$accent" :disabled="$disabled" />
                        </div>
                    @endforeach
                </div>

                @unless ($readOnly)
                    <div
                        class="px-5 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" wire:click="resetGroup"
                            wire:confirm="Rétablir les valeurs par défaut de « {{ $entry['group']->label() }} » ?"
                            class="text-sm text-gray-500 hover:text-red-600 transition-colors self-start">
                            Rétablir les valeurs par défaut
                        </button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-lg transition-colors disabled:opacity-60 {{ $btnPrimary }}">
                            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Enregistrer
                        </button>
                    </div>
                @endunless
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-10 text-center text-gray-400">
                Aucun paramètre dans ce groupe.
            </div>
        @endif
    </div>
</div>
