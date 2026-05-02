<div>
    {{-- Backdrop --}}
    <div x-data x-show="$wire.open"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/40 backdrop-blur-sm"
         wire:click="close"
         style="display: none;">
    </div>

    {{-- Panel --}}
    <div x-data x-show="$wire.open"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl flex flex-col"
         style="display: none;"
         @keydown.escape.window="$wire.close()">

        {{-- Header --}}
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50 flex-shrink-0">
            <div class="min-w-0 pr-3">
                <div class="flex items-center gap-2 mb-0.5">
                    <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs font-medium text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full">{{ $entityTypeName }}</span>
                </div>
                <h2 class="text-base font-bold text-gray-800 truncate">{{ $entityLabel }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $documents->count() }} document(s)</p>
            </div>
            <button wire:click="close" class="flex-shrink-0 p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white/60 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Formulaire d'ajout/édition --}}
            @if($showForm)
                <div class="p-5 border-b border-indigo-100 bg-indigo-50/50">
                    <h3 class="text-sm font-semibold text-indigo-800 mb-4">
                        {{ $editingDocId ? 'Modifier le document' : 'Nouveau document' }}
                    </h3>
                    <form wire:submit="save" class="space-y-4">

                        {{-- Titre --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Titre *</label>
                            <input wire:model="titre" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                   placeholder="Ex: Permis de conduire">
                            @error('titre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                            <textarea wire:model="description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                      placeholder="Notes optionnelles..."></textarea>
                        </div>

                        {{-- Fichier --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Fichier {{ $editingDocId ? '(laisser vide pour conserver l\'existant)' : '*' }}
                            </label>
                            @if($editingDocId && $existingFileName && !$fichier)
                                <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg text-xs text-gray-600 mb-2">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="truncate">{{ $existingFileName }}</span>
                                </div>
                            @endif
                            <input wire:model="fichier" type="file"
                                   class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                            <div wire:loading wire:target="fichier" class="text-xs text-indigo-500 mt-1 flex items-center gap-1">
                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Chargement…
                            </div>
                            @error('fichier') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Expiration --}}
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="$toggle('has_expiration')"
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $has_expiration ? 'bg-indigo-600' : 'bg-gray-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $has_expiration ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-xs font-medium text-gray-700">A une date d'expiration</span>
                        </div>
                        @if($has_expiration)
                            <div>
                                <input wire:model="date_expiration" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                @error('date_expiration') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        {{-- Rappels --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-gray-700">Rappels</label>
                                <button type="button" wire:click="addRappel"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Ajouter
                                </button>
                            </div>
                            @foreach($rappels as $i => $rappel)
                                <div class="bg-white border border-gray-200 rounded-lg p-3 mb-2 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <input wire:model="rappels.{{ $i }}.delai_valeur" type="number" min="1"
                                               class="w-16 px-2 py-1 border border-gray-200 rounded text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                        <select wire:model="rappels.{{ $i }}.delai_unite"
                                                class="flex-1 px-2 py-1 border border-gray-200 rounded text-xs bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                            <option value="jours">jours avant</option>
                                            <option value="heures">heures avant</option>
                                        </select>
                                        <button type="button" wire:click="removeRappel({{ $i }})"
                                                class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $canal => $label)
                                            @php $active = in_array($canal, $rappel['canaux'] ?? []); @endphp
                                            <button type="button" wire:click="toggleCanal({{ $i }}, '{{ $canal }}')"
                                                    class="px-2 py-0.5 rounded-full text-xs font-medium border transition-colors {{ $active ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-500 hover:border-indigo-300' }}">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                    @error("rappels.{$i}.canaux") <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>

                        {{-- Boutons formulaire --}}
                        <div class="flex gap-2 pt-1">
                            <button type="button" wire:click="cancelForm"
                                    class="flex-1 px-3 py-2 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="flex-1 px-3 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                                <span wire:loading.remove wire:target="save">{{ $editingDocId ? 'Enregistrer' : 'Ajouter' }}</span>
                                <span wire:loading wire:target="save">Enregistrement…</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                {{-- Bouton ajouter --}}
                <div class="px-5 pt-4 pb-3 flex-shrink-0">
                    <button wire:click="openAddForm"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-dashed border-indigo-200 rounded-xl text-sm font-medium text-indigo-600 hover:border-indigo-400 hover:bg-indigo-50/50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter un document
                    </button>
                </div>
            @endif

            {{-- Liste des documents --}}
            <div class="px-5 pb-5">
                @if($documents->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm font-medium">Aucun document</p>
                        <p class="text-xs mt-1">Ajoutez le premier document ci-dessus.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($documents as $doc)
                            @php
                                $icon   = $doc->fileIcon;
                                $statut = $doc->statut;
                                $iconCfg = match($icon) {
                                    'pdf'         => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'label' => 'PDF'],
                                    'image'       => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'label' => 'IMG'],
                                    'spreadsheet' => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'label' => 'XLS'],
                                    'word'        => ['bg' => 'bg-sky-100',    'text' => 'text-sky-600',    'label' => 'DOC'],
                                    default       => ['bg' => 'bg-gray-100',   'text' => 'text-gray-500',   'label' => 'FIC'],
                                };
                                $statutCfg = match($statut) {
                                    'expire'        => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'label' => 'Expiré'],
                                    'expire_bientot'=> ['bg' => 'bg-amber-100',  'text' => 'text-amber-700',  'label' => 'Expire bientôt'],
                                    default         => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'label' => 'Valide'],
                                };
                            @endphp
                            <div class="flex items-start gap-3 p-3 bg-white border border-gray-100 rounded-xl hover:border-gray-200 hover:shadow-sm transition-all group">

                                {{-- Icône fichier --}}
                                <div class="flex-shrink-0 w-10 h-10 {{ $iconCfg['bg'] }} {{ $iconCfg['text'] }} rounded-lg flex items-center justify-center font-bold text-xs">
                                    {{ $iconCfg['label'] }}
                                </div>

                                {{-- Infos --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-medium text-gray-800 truncate leading-snug">{{ $doc->titre }}</p>
                                        <span class="flex-shrink-0 text-xs px-1.5 py-0.5 rounded-full font-medium {{ $statutCfg['bg'] }} {{ $statutCfg['text'] }}">
                                            {{ $statutCfg['label'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1">
                                        @if($doc->has_expiration && $doc->date_expiration)
                                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                {{ $doc->date_expiration->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if($doc->rappels->count())
                                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                {{ $doc->rappels->count() }} rappel(s)
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-300">{{ $doc->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-0.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                       class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                       title="Télécharger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    <button wire:click="openEditForm({{ $doc->id }})"
                                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="deleteDoc({{ $doc->id }})"
                                            wire:confirm="Supprimer ce document ?"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
