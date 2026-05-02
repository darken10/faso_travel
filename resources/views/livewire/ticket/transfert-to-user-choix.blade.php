<div>
    {{-- Search --}}
    <div class="card mb-4">
        <h2 class="text-xl font-bold text-surface-900 dark:text-white mb-4">Choisir le destinataire</h2>
        <form wire:submit="handleChange">
            @csrf
            <div class="flex gap-2">
                <div class="flex-1">
                    <input type="text" wire:model.live="name" placeholder="Rechercher par nom ou prénom…" class="input w-full" required autofocus>
                </div>
                <button type="submit" class="btn-primary flex-shrink-0">
                    <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    Rechercher
                </button>
            </div>
        </form>
    </div>

    {{-- Results --}}
    <div class="card">
        <form action="{{ route('ticket.transferer-ticket-to-other-user-traitement',$ticket) }}" method="post">
            @csrf
            <div class="space-y-1">
                @forelse($users as $user)
                    <button value="{{ $user->id }}" name="user_selected" class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors text-left">
                        <img class="w-10 h-10 rounded-full object-cover flex-shrink-0" src="{{ asset($user->profileUrl ?? 'icon/user1.png') }}" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-surface-900 dark:text-white truncate">{{ $user->name }}</p>
                            <p class="text-sm text-surface-500 dark:text-surface-400 truncate">{{ $user->email }}</p>
                        </div>
                        <svg class="w-5 h-5 text-surface-300 dark:text-surface-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                @empty
                    <div class="text-center py-8">
                        <div class="w-12 h-12 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-surface-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                        <p class="text-surface-500 dark:text-surface-400">Aucun utilisateur trouvé</p>
                    </div>
                @endforelse
            </div>
        </form>
    </div>
</div>
