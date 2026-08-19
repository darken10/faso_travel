@props([
    'definition',
    'model',
    'value'    => null,
    'accent'   => 'blue',
    'disabled' => false,
])

@php
    /** @var \App\DTOs\Compagnie\SettingDefinition $definition */
    $type = $definition->type;

    $ring  = $accent === 'amber' ? 'focus:ring-amber-400' : 'focus:ring-blue-500';
    $check = $accent === 'amber' ? 'text-amber-500 focus:ring-amber-400' : 'text-blue-600 focus:ring-blue-500';

    $inputClass = "w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 {$ring} disabled:bg-gray-100 disabled:text-gray-400";

    // Interrupteur : état rendu côté serveur avec des classes littérales, plutôt
    // que des variantes peer-checked:after:* que le JIT Tailwind peut manquer.
    $isOn      = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    $trackOn   = $accent === 'amber' ? 'bg-amber-500' : 'bg-blue-600';
    $trackCls  = $isOn ? $trackOn : 'bg-gray-300';
    $knobCls   = $isOn ? 'translate-x-[22px]' : 'translate-x-[2px]';
@endphp

<div class="py-4 {{ $type === \App\Enums\CompagnieSettingType::Boolean ? 'sm:flex sm:items-start sm:justify-between sm:gap-6' : '' }}">
    <div class="{{ $type === \App\Enums\CompagnieSettingType::Boolean ? 'sm:flex-1' : 'mb-2' }}">
        <label class="block text-sm font-medium text-gray-800" @if($type !== \App\Enums\CompagnieSettingType::Boolean) for="{{ $model }}" @endif>
            {{ $definition->label }}
            @if($definition->isAdminOnly())
                <span class="ml-1.5 align-middle inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-amber-100 text-amber-700">Admin</span>
            @endif
        </label>
        @if($definition->help)
            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $definition->help }}</p>
        @endif
    </div>

    <div class="{{ $type === \App\Enums\CompagnieSettingType::Boolean ? 'mt-2 sm:mt-0 sm:flex-shrink-0' : '' }}">
        @switch($type)

            @case(\App\Enums\CompagnieSettingType::Boolean)
                <div class="flex items-center gap-3">
                    <button type="button" role="switch" aria-checked="{{ $isOn ? 'true' : 'false' }}"
                            aria-label="{{ $definition->label }}"
                            @if(! $disabled) wire:click="toggleSetting('{{ $definition->key->value }}')" @endif
                            @disabled($disabled)
                            class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $ring }} {{ $trackCls }} {{ $disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-1 ring-black/5 transition-transform duration-200 {{ $knobCls }}"></span>
                    </button>
                    <span class="text-sm {{ $isOn ? 'text-gray-700 font-medium' : 'text-gray-400' }}">
                        {{ $isOn ? 'Activé' : 'Désactivé' }}
                    </span>
                </div>
                @break

            @case(\App\Enums\CompagnieSettingType::Select)
                <select id="{{ $model }}" wire:model.live="{{ $model }}" @disabled($disabled) class="{{ $inputClass }}">
                    @foreach($definition->options as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @break

            @case(\App\Enums\CompagnieSettingType::MultiSelect)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($definition->options as $value => $label)
                        <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm {{ $disabled ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50' }}">
                            <input type="checkbox" value="{{ $value }}" wire:model.live="{{ $model }}" @disabled($disabled)
                                   class="rounded border-gray-300 {{ $check }}">
                            <span class="text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case(\App\Enums\CompagnieSettingType::Text)
                <textarea id="{{ $model }}" wire:model.blur="{{ $model }}" rows="3" @disabled($disabled) class="{{ $inputClass }}"></textarea>
                @break

            @case(\App\Enums\CompagnieSettingType::Color)
                <div class="flex items-center gap-2">
                    <input type="color" wire:model.live="{{ $model }}" @disabled($disabled)
                           class="h-9 w-12 rounded border border-gray-300 p-0.5 {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                    <input type="text" id="{{ $model }}" wire:model.blur="{{ $model }}" @disabled($disabled)
                           class="{{ $inputClass }} font-mono uppercase" placeholder="#2196F3">
                </div>
                @break

            @case(\App\Enums\CompagnieSettingType::Integer)
            @case(\App\Enums\CompagnieSettingType::Float)
                <div class="flex items-center gap-2">
                    <input type="number"
                           step="{{ $type === \App\Enums\CompagnieSettingType::Float ? '0.01' : '1' }}"
                           id="{{ $model }}" wire:model.blur="{{ $model }}" @disabled($disabled)
                           class="{{ $inputClass }} sm:max-w-[10rem]">
                    @if($definition->suffix)
                        <span class="text-sm text-gray-500 whitespace-nowrap">{{ $definition->suffix }}</span>
                    @endif
                </div>
                @break

            @case(\App\Enums\CompagnieSettingType::Time)
                <input type="time" id="{{ $model }}" wire:model.blur="{{ $model }}" @disabled($disabled) class="{{ $inputClass }} sm:max-w-[10rem]">
                @break

            @default
                <input type="text" id="{{ $model }}" wire:model.blur="{{ $model }}" @disabled($disabled) class="{{ $inputClass }}">
        @endswitch

        @error($model)
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
