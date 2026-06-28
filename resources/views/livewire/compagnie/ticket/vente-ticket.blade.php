<div>
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @if($step === 4 && $ticketVenduId)
        {{-- Confirmation --}}
        <div class="max-w-lg mx-auto">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Vente effectuée !</h2>
                @if($ticketVendu)
                    <p class="text-gray-500 text-sm mb-6">Ticket <span class="font-mono font-semibold text-gray-700">{{ $ticketVendu->numero_ticket }}</span> créé avec succès.</p>
                    <div class="bg-gray-50 rounded-xl p-4 text-left space-y-2 text-sm mb-6">
                        <div class="flex justify-between"><span class="text-gray-500">Client</span><span class="font-medium">{{ $ticketVendu->autrePersonne?->first_name }} {{ $ticketVendu->autrePersonne?->last_name }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Voyage</span><span class="font-medium">{{ $ticketVendu->voyageInstance?->voyage?->trajet?->depart?->name }} → {{ $ticketVendu->voyageInstance?->voyage?->trajet?->arriver?->name }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Siège</span><span class="font-mono font-semibold text-blue-600">{{ $ticketVendu->numero_chaise }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Monnaie</span><span class="font-semibold text-green-600">{{ number_format($monnaie, 0, ',', ' ') }} F</span></div>
                    </div>
                @endif
                @if($ticketVendu)
                    <a href="{{ route('panel.compagnie.tickets.print', ['ticketId' => $ticketVendu->id]) }}" target="_blank"
                       class="w-full inline-flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 rounded-xl transition-colors mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimer le ticket
                    </a>
                @endif
                <button wire:click="nouvelleVente" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition-colors">
                    Nouvelle vente
                </button>
            </div>
        </div>
    @elseif(!$caisse)
        {{-- ── Caisse non ouverte : écran bloquant ──────────────────────────────── --}}
        <div class="max-w-md mx-auto mt-12">
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m0-10v4m-6.938 7h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 mb-2">Caisse non ouverte</h2>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    Vous devez ouvrir une caisse avant de pouvoir vendre un ticket.<br>
                    Toute vente doit être rattachée à une session de caisse active.
                </p>
                <a href="{{ route('panel.compagnie.caisse') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    Ouvrir la caisse
                </a>
            </div>
        </div>

    @else
        <div class="max-w-2xl mx-auto">

            {{-- Steps indicator --}}
            <div class="flex items-center gap-0 mb-8">
                @foreach(['Voyage', 'Client', 'Paiement'] as $i => $label)
                    @php $num = $i + 1; @endphp
                    <div class="flex items-center {{ $i < 2 ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors
                                {{ $step > $num ? 'bg-green-500 text-white' : ($step === $num ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                                @if($step > $num)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $num }}
                                @endif
                            </div>
                            <span class="text-sm font-medium {{ $step === $num ? 'text-blue-600' : 'text-gray-400' }}">{{ $label }}</span>
                        </div>
                        @if($i < 2)
                            <div class="flex-1 h-px mx-3 {{ $step > $num ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                {{-- Step 1: Voyage --}}
                @if($step === 1)
                    <h3 class="text-base font-semibold text-gray-800 mb-5">Choisir le voyage</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Voyage disponible *</label>
                            <select wire:model.live="voyage_instance_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Sélectionner un voyage --</option>
                                @foreach($instances as $instance)
                                    @php
                                        $depart = $instance->villeDepart()?->name ?? '?';
                                        $arrivee = $instance->villeArrive()?->name ?? '?';
                                        $places = count($instance->chaiseDispo());
                                    @endphp
                                    <option value="{{ $instance->id }}">
                                        {{ $depart }} → {{ $arrivee }} | {{ $instance->date?->format('d/m/Y') }} {{ $instance->heure ? \Carbon\Carbon::parse($instance->heure)->format('H\hi') : '' }} ({{ $places }} places)
                                    </option>
                                @endforeach
                            </select>
                            @error('voyage_instance_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de ticket *</label>
                            <div class="flex gap-3">
                                @foreach($typeTickets as $t)
                                    <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-colors
                                        {{ $type_ticket === $t->value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">
                                        <input type="radio" wire:model.live="type_ticket" value="{{ $t->value }}" class="sr-only">
                                        <span class="text-sm font-medium">{{ $t->value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Plan des sièges --}}
                        @if($voyage_instance_id && $totalSeats > 0)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Choisir la chaise *</label>
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-100 border border-gray-300"></span> Libre</span>
                                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-600"></span> Choisie</span>
                                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-300"></span> Occupée</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 gap-2 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    @for($n = 1; $n <= $totalSeats; $n++)
                                        @php $isOccupied = in_array($n, $occupiedSeats, true); $isSelected = $numero_chaise === $n; @endphp
                                        <button type="button"
                                            @if(!$isOccupied) wire:click="selectSeat({{ $n }})" @endif
                                            @disabled($isOccupied)
                                            class="aspect-square flex items-center justify-center rounded-lg text-xs font-semibold transition-colors
                                                {{ $isOccupied
                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                    : ($isSelected ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-400') }}">
                                            {{ $n }}
                                        </button>
                                    @endfor
                                </div>
                                @error('numero_chaise') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @if($numero_chaise)
                                    <p class="text-xs text-gray-500 mt-2">Chaise sélectionnée : <span class="font-semibold text-blue-600">n°{{ $numero_chaise }}</span></p>
                                @endif
                            </div>
                        @endif

                        @if($prix > 0)
                            <div class="bg-blue-50 rounded-xl p-4 flex items-center justify-between">
                                <span class="text-sm text-blue-700 font-medium">Prix du ticket</span>
                                <span class="text-2xl font-bold text-blue-600">{{ number_format($prix, 0, ',', ' ') }} F</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end mt-6">
                        <button wire:click="nextStep" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition-colors">
                            Continuer →
                        </button>
                    </div>
                @endif

                {{-- Step 2: Client --}}
                @if($step === 2)
                    <h3 class="text-base font-semibold text-gray-800 mb-5">Informations du client</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                <input wire:model="client_nom" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: OUEDRAOGO">
                                @error('client_nom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                                <input wire:model="client_prenom" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: Ibrahim">
                                @error('client_prenom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone (facultatif)</label>
                            <input wire:model="client_telephone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: 70 12 34 56">
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button wire:click="previousStep" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">← Retour</button>
                        <button wire:click="nextStep" class="flex-1 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition-colors">Continuer →</button>
                    </div>
                @endif

                {{-- Step 3: Paiement --}}
                @if($step === 3)
                    <h3 class="text-base font-semibold text-gray-800 mb-5">Encaissement</h3>
                    <div class="space-y-4">
                        <div class="bg-blue-50 rounded-xl p-4 flex items-center justify-between mb-2">
                            <span class="text-sm text-blue-700 font-medium">Montant à encaisser</span>
                            <span class="text-2xl font-bold text-blue-600">{{ number_format($prix, 0, ',', ' ') }} F</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant reçu *</label>
                            <input wire:model.live="montant_recu" type="number" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="{{ $prix }}">
                            @error('montant_recu') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if($monnaie > 0)
                            <div class="bg-green-50 rounded-xl p-4 flex items-center justify-between">
                                <span class="text-sm text-green-700 font-medium">Monnaie à rendre</span>
                                <span class="text-xl font-bold text-green-600">{{ number_format($monnaie, 0, ',', ' ') }} F</span>
                            </div>
                        @endif

                        {{-- Summary --}}
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                            <p class="font-semibold text-gray-700 mb-2">Récapitulatif</p>
                            <div class="flex justify-between text-gray-600"><span>Voyage</span><span>{{ optional(optional(optional(\App\Models\Voyage\VoyageInstance::find($voyage_instance_id))->voyage)->trajet)->depart?->name }} → {{ optional(optional(optional(\App\Models\Voyage\VoyageInstance::find($voyage_instance_id))->voyage)->trajet)->arriver?->name }}</span></div>
                            <div class="flex justify-between text-gray-600"><span>Client</span><span class="font-medium">{{ $client_nom }} {{ $client_prenom }}</span></div>
                            <div class="flex justify-between text-gray-600"><span>Type</span><span>{{ $type_ticket }}</span></div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button wire:click="previousStep" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl">← Retour</button>
                        <button wire:click="vendreTicket" class="flex-1 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-xl transition-colors">
                            Confirmer la vente
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
