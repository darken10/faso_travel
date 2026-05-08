<div>
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('panel.compagnie.posts') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $postId ? 'Modifier l\'article' : 'Nouvel article' }}</h2>
            <p class="text-sm text-gray-400">{{ $postId ? 'Mettez à jour votre publication' : 'Créez et publiez un nouvel article' }}</p>
        </div>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Colonne principale ── --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Titre --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Titre de l'article *</label>
                    <input wire:model="title" type="text"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 placeholder-gray-300"
                           placeholder="Un titre accrocheur…">
                    @error('title') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Contenu --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contenu *</label>
                    <textarea wire:model="content" rows="12"
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none placeholder-gray-300"
                              placeholder="Rédigez votre article ici…"></textarea>
                    @error('content') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Images --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Photos
                        <span class="font-normal text-gray-400 ml-1">(plusieurs autorisées, max 5 Mo chacune)</span>
                    </label>

                    {{-- Zone de dépôt --}}
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                        <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm text-gray-400">Cliquer pour ajouter des photos</span>
                        <input wire:model="images" type="file" multiple accept="image/*" class="hidden">
                    </label>
                    <div wire:loading wire:target="images" class="text-xs text-blue-500 mt-2 flex items-center gap-1">
                        <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Chargement…
                    </div>

                    {{-- Aperçu des images existantes --}}
                    @if($existingImages)
                        <div class="mt-3">
                            <p class="text-xs text-gray-400 mb-2">Photos actuelles</p>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                @foreach($existingImages as $i => $uri)
                                    <div class="relative group aspect-square">
                                        <img src="{{ Storage::url($uri) }}" class="w-full h-full object-cover rounded-lg">
                                        <button type="button" wire:click="removeExistingImage({{ $i }})"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs">
                                            ×
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Aperçu nouvelles images --}}
                    @if($images)
                        <div class="mt-3">
                            <p class="text-xs text-gray-400 mb-2">Nouvelles photos ({{ count($images) }})</p>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                @foreach($images as $img)
                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img src="{{ $img->temporaryUrl() }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Colonne latérale ── --}}
            <div class="space-y-5">

                {{-- Publier --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Publication</h4>
                    <button type="submit"
                            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span wire:loading.remove wire:target="save">{{ $postId ? 'Enregistrer' : 'Publier' }}</span>
                        <span wire:loading wire:target="save">En cours…</span>
                    </button>
                    <a href="{{ route('panel.compagnie.posts') }}"
                       class="mt-2 w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition-colors flex items-center justify-center">
                        Annuler
                    </a>
                </div>

                {{-- Catégorie --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700">Catégorie</h4>
                        <button type="button" wire:click="$toggle('showCategoryForm')"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouvelle
                        </button>
                    </div>

                    @if($showCategoryForm)
                        <div class="flex gap-2 mb-3">
                            <input wire:model="newCategoryName" type="text"
                                   class="flex-1 px-3 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Nom de la catégorie"
                                   wire:keydown.enter.prevent="addCategory">
                            <button type="button" wire:click="addCategory"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700">
                                OK
                            </button>
                        </div>
                        @error('newCategoryName') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                    @endif

                    <select wire:model="category_id"
                            class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">— Aucune —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tags --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700">Tags</h4>
                        <button type="button" wire:click="$toggle('showTagForm')"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouveau
                        </button>
                    </div>

                    @if($showTagForm)
                        <div class="flex gap-2 mb-3">
                            <input wire:model="newTagName" type="text"
                                   class="flex-1 px-3 py-1.5 text-sm border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Nom du tag"
                                   wire:keydown.enter.prevent="addTag">
                            <button type="button" wire:click="addTag"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700">
                                OK
                            </button>
                        </div>
                        @error('newTagName') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                    @endif

                    <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                        @forelse($allTags as $tag)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}" class="sr-only peer">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border transition-colors
                                             border-gray-200 bg-gray-50 text-gray-600
                                             peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600">
                                    #{{ $tag->name }}
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-400">Aucun tag. Créez-en un.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
