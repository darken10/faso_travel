<div>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Articles & Publications</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $posts->total() }} articles</p>
        </div>
        <a href="{{ route('panel.compagnie.posts.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel article
        </a>
    </div>

    {{-- Search --}}
    <div class="relative mb-5">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Rechercher un article..."
               class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white shadow-sm">
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($posts as $post)
            @php $imgs = $post->images_uri ?? []; @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">

                {{-- Photo grid --}}
                @if(count($imgs) === 0)
                    <div class="h-40 bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @elseif(count($imgs) === 1)
                    <img src="{{ Storage::url($imgs[0]) }}" alt="" class="w-full h-40 object-cover">
                @else
                    <div class="grid grid-cols-2 h-40 gap-0.5">
                        @foreach(array_slice($imgs, 0, 4) as $i => $uri)
                            <div class="relative overflow-hidden {{ count($imgs) <= 2 ? 'row-span-1' : '' }}">
                                <img src="{{ Storage::url($uri) }}" alt="" class="w-full h-full object-cover">
                                @if($i === 3 && count($imgs) > 4)
                                    <div class="absolute inset-0 bg-black/55 flex items-center justify-center">
                                        <span class="text-white text-xl font-bold">+{{ count($imgs) - 4 }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Body --}}
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-1.5">
                        <h3 class="font-semibold text-gray-800 text-sm leading-snug line-clamp-2">{{ $post->title }}</h3>
                        @if($post->category)
                            <span class="shrink-0 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">{{ $post->category->name }}</span>
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

                    <div class="flex items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-50">
                        <span>{{ $post->created_at?->format('d/m/Y') }}</span>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('panel.compagnie.posts.edit', $post->id) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">Éditer</a>
                            <button wire:click="delete({{ $post->id }})" wire:confirm="Supprimer cet article ?"
                                    class="text-red-500 hover:text-red-700 font-medium">Supprimer</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-16 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <p class="font-medium">Aucun article trouvé</p>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="mt-6">{{ $posts->links() }}</div>
    @endif
</div>
