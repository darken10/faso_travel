<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Classes</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $classes->total() }} classes définies</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvelle classe
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher une classe..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Description</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Conforts</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Défaut</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($classes as $classe)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $classe->name }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $classe->description ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $classe->conforts_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($classe->is_default)
                                <span class="text-amber-500">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openConforts({{ $classe->id }})" title="Gérer les conforts" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                </button>
                                @if(!$classe->is_default)
                                    <button wire:click="openEdit({{ $classe->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="delete({{ $classe->id }})" wire:confirm="Supprimer cette classe ?" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Aucune classe trouvée</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($classes->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $classes->links() }}</div>
        @endif
    </div>

    {{-- Modal Classe --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Modifier la classe' : 'Nouvelle classe' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input wire:model="name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: VIP, Économie...">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Description de la classe..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">{{ $editingId ? 'Enregistrer' : 'Créer' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Conforts --}}
    @if($showConfortsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('showConfortsModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showConfortsModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Conforts — {{ $selectedClasse?->name }}</h3>
                        <p class="text-xs text-gray-500">Cochez pour attacher un confort à cette classe</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showConfortForm', true)" class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">+ Nouveau confort</button>
                        <button wire:click="$set('showConfortsModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @if($showConfortForm)
                    <div class="px-6 py-3 bg-blue-50 border-b border-gray-100">
                        <form wire:submit="saveConfort" class="flex gap-2">
                            <input wire:model="confort_title" type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Ex: Climatisation, WiFi...">
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Ajouter</button>
                            <button type="button" wire:click="$set('showConfortForm', false)" class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg">✕</button>
                        </form>
                    </div>
                @endif
                <div class="max-h-80 overflow-y-auto p-6 grid grid-cols-2 gap-2">
                    @forelse($allConforts as $confort)
                        <label class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 cursor-pointer border border-gray-100 hover:border-blue-200 transition-colors">
                            <input type="checkbox" wire:click="toggleConfort({{ $confort->id }})"
                                   @checked(in_array($confort->id, $attachedConfortIds))
                                   class="rounded text-blue-600 border-gray-300 focus:ring-blue-400">
                            <span class="text-sm text-gray-700">{{ $confort->title }}</span>
                        </label>
                    @empty
                        <p class="col-span-2 text-center text-gray-400 text-sm py-4">Aucun confort disponible</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
