@php
    $statutColors = [
        'DISPONIBLE' => ['bg' => 'bg-green-50',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
        'INACTIF'    => ['bg' => 'bg-gray-100',  'text' => 'text-gray-600',   'dot' => 'bg-gray-400'],
        'RETARDE'    => ['bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'dot' => 'bg-amber-500'],
        'ANNULE'     => ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'dot' => 'bg-red-500'],
    ];
    $sv = $instance->statut->value ?? 'DISPONIBLE';
    $sc = $statutColors[$sv] ?? $statutColors['INACTIF'];
    $passengerName = fn($t) => $t->is_my_ticket
        ? ($t->user?->name ?? '—')
        : ($t->autre_personne?->name ?? $t->user?->name ?? '—');
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    {{-- Flash --}}
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- En-tête --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('panel.compagnie.instances') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    {{ $instance->villeDepart()?->name ?? '—' }}
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    {{ $instance->villeArrive()?->name ?? '—' }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ \Illuminate\Support\Carbon::parse($instance->date)->translatedFormat('D d M Y') }}
                    · {{ \Illuminate\Support\Carbon::parse($instance->heure)->format('H\hi') }}
                    · {{ $instance->voyage?->compagnie?->name }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                <span class="w-2 h-2 rounded-full {{ $sc['dot'] }}"></span> {{ $sv }}
            </span>
            <button wire:click="exportManifeste" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Manifeste
            </button>
            <button wire:click="openAssignModal" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">Affecter</button>
            @if ($sv !== 'ANNULE')
                <button wire:click="openAlertModal('RETARDE')" class="px-3 py-1.5 text-sm font-medium border border-amber-200 text-amber-700 rounded-lg hover:bg-amber-50">Retarder</button>
                <button wire:click="openAlertModal('ANNULE')" class="px-3 py-1.5 text-sm font-medium border border-red-200 text-red-700 rounded-lg hover:bg-red-50">Annuler</button>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Places</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $total }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Occupées</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $occupied }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Disponibles</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $available }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Recette</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($revenue, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">XOF</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Colonne gauche : infos + plan des sièges --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Affectation --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Affectation</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Véhicule</p>
                        <p class="font-medium text-gray-800">{{ $instance->care?->immatrculation ?? '— Non affecté —' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Chauffeur</p>
                        <p class="font-medium text-gray-800">{{ $instance->chauffer ? $instance->chauffer->first_name.' '.$instance->chauffer->last_name : '— Non affecté —' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Classe</p>
                        <p class="font-medium text-gray-800">{{ $instance->voyage?->classe?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Prix aller</p>
                        <p class="font-medium text-gray-800">{{ number_format($instance->prix ?: $instance->voyage?->prix, 0, ',', ' ') }} XOF</p>
                    </div>
                </div>
            </div>

            {{-- Plan des sièges --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Plan des sièges</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-100 border border-gray-300"></span> Libre</span>
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-600"></span> Occupé</span>
                    </div>
                </div>
                <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 gap-2">
                    @foreach ($seats as $seat)
                        <div title="{{ $seat['occupied'] ? $passengerName($seat['ticket']) : 'Libre' }}"
                             class="aspect-square flex items-center justify-center rounded-lg text-xs font-semibold
                                {{ $seat['occupied'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 border border-gray-200' }}">
                            {{ $seat['number'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Colonne droite : passagers --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Passagers ({{ $passengers->count() }})</h2>
            @forelse ($passengers as $t)
                <div class="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $passengerName($t) }}</p>
                        <p class="text-xs text-gray-500">Siège {{ $t->numero_chaise }} · {{ $t->numero_ticket }}</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $t->statut === \App\Enums\StatutTicket::Payer ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $t->statut->value }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-6 text-center">Aucun passager pour le moment.</p>
            @endforelse
        </div>
    </div>

    {{-- Modale affectation --}}
    @if ($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Affecter véhicule & chauffeur</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule</label>
                        <select wire:model="assignCareId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">— Aucun —</option>
                            @foreach ($cares as $car)
                                <option value="{{ $car->id }}">{{ $car->immatrculation }} ({{ $car->number_place }} places)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chauffeur</label>
                        <select wire:model="assignChauffeurId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">— Aucun —</option>
                            @foreach ($chauffeurs as $ch)
                                <option value="{{ $ch->id }}">{{ $ch->first_name }} {{ $ch->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button wire:click="$set('showAssignModal', false)" class="px-4 py-2 text-sm text-gray-600">Annuler</button>
                    <button wire:click="saveAssignment" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Enregistrer</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modale alerte --}}
    @if ($showAlertModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-1">{{ $alertType === 'ANNULE' ? 'Annuler le voyage' : 'Signaler un retard' }}</h3>
                <p class="text-sm text-gray-500 mb-4">
                    {{ $alertType === 'ANNULE'
                        ? 'Les tickets payés seront mis en pause et les clients notifiés.'
                        : 'Les clients seront notifiés du retard.' }}
                </p>
                <textarea wire:model="alertReason" rows="3" placeholder="Motif (facultatif)…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                <div class="flex justify-end gap-2 mt-5">
                    <button wire:click="$set('showAlertModal', false)" class="px-4 py-2 text-sm text-gray-600">Fermer</button>
                    <button wire:click="confirmAlert"
                        class="px-4 py-2 text-sm font-semibold text-white rounded-lg {{ $alertType === 'ANNULE' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700' }}">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
