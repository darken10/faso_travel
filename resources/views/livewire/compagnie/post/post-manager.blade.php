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
            <h2 class="text-xl font-bold text-gray-800">Articles & Publications</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $posts->total() }} articles</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvel article
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-4">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un article..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($posts as $post)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                @if($post->images_uri && count($post->images_uri) > 0)
                    <img src="{{ Storage::url($post->images_uri[0]) }}" alt="" class="w-full h-40 object-cover">
                @else
                    <div class="w-full h-40 bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                @endif
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-semibold text-gray-800 text-sm leading-snug line-clamp-2">{{ $post->title }}</h3>
                        @if($post->category)
                            <x-panel.badge color="blue" size="xs">{{ $post->category->name }}</x-panel.badge>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mb-3 line-clamp-2">{{ strip_tags($post->content) }}</p>
                    @if($post->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mb-3">
                            @foreach($post->tags->take(3) as $tag)
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>{{ $post->created_at?->format('d/m/Y') }}</span>
                        <div class="flex items-center gap-3">
                            <button wire:click="openEdit({{ $post->id }})" class="text-blue-600 hover:text-blue-800 font-medium">Éditer</button>
                            <button wire:click="delete({{ $post->id }})" wire:confirm="Supprimer cet article ?" class="text-red-500 hover:text-red-700 font-medium">Supprimer</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-16 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                Aucun article trouvé
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="mt-4">{{ $posts->links() }}</div>
    @endif

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-4" x-trap.noscroll="true">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Modifier l\'article' : 'Nouvel article' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                        <input wire:model="title" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Titre de l'article">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenu *</label>
                        <textarea wire:model="content" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" placeholder="Rédigez votre article..."></textarea>
                        @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select wire:model="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">-- Catégorie --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                                @foreach($allTags as $tag)
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}" class="rounded text-blue-600 w-3 h-3">
                                        <span class="text-xs text-gray-600">{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Images</label>
                        <input wire:model="images" type="file" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div wire:loading wire:target="images" class="text-xs text-gray-400 mt-1">Chargement...</div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">{{ $editingId ? 'Enregistrer' : 'Publier' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
