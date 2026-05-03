<form wire:submit="save">
    <div class=" grid grid-cols-12  gap-4 mb-4 ">
        <div class=" col-span-12 ">
            <x-label for="voyageInstanceId" value="{{ __('Date Depart') }}" />
            <x-select  wire:model="voyageInstanceId" id="voyageInstanceId" wire:change="updateChaiseDispo" class="mt-1 block w-full" autofocus  >
                @foreach($voyageInstances as $voyageInstance)
                    <option value="{{ $voyageInstance->id }}">{{ $voyageInstance->date->format('D d M Y') }} à {{$voyageInstance->heure->format('H\h i')}}</option>
                @endforeach
            </x-select>
            <x-input-error for="voyageInstanceId" class="mt-2" />
        </div>

    </div>

    <div class=" grid grid-cols-12 gap-4 mb-4">

        <div class="sm:col-span-6 col-span-12">
            <x-label for="numero_chaise" value="{{ __('Numero de la chaise') }}" />
            <x-select  wire:model="numero_chaise" id="statut" type="text" class="mt-1 block w-full" autofocus  >
                @foreach($chaiseDispo as $numero)
                    <option value="{{ $numero }}">Chaise N°{{ $numero }}</option>
                @endforeach
            </x-select>
            <x-input-error for="numero_chaise" class="mt-2" />
        </div>
        <div class="sm:col-span-6 col-span-12">
            <x-label for="statut" value="{{ __('Status') }}" />
            <x-select id="statut" type="text" class="mt-1 block w-full" autofocus  wire:model="statut">
                <option value="{{ \App\Enums\StatutTicket::Payer }}">Activer</option>
                <option value="{{ \App\Enums\StatutTicket::Pause }}">Pause</option>
            </x-select>
            <x-input-error for="statut" class="mt-2" />
        </div>
    </div>




    <button type="submit" class="w-full mt-4 flex justify-center items-center gap-4 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
        <div wire:loading wire:target="save" class="w-8">
            <x-loading-indicator></x-loading-indicator>
        </div>

        Enregistrer
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
        </svg>
    </button>
</form>
