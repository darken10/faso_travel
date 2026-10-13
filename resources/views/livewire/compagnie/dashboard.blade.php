<div>
    <div class="mb-7">
        <p class="text-sm text-gray-500">Bonjour, {{ auth()->user()->first_name ?: auth()->user()->name }}.</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Votre tableau de bord</h1>
        <p class="mt-1 text-sm text-gray-500">Les accès ci-dessous sont adaptés à votre rôle et à vos habilitations.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Vos rôles</span>
        @forelse($roles as $role)
            <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">{{ $role->label ?: $role->name }}</span>
        @empty
            <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700">Habilitations en cours de configuration</span>
        @endforelse
    </div>

    @if($actions->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($actions as $action)
                <a href="{{ route($action['route']) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-{{ $action['color'] }}-50 group-hover:bg-{{ $action['color'] }}-100">
                            <svg class="h-5 w-5 text-{{ $action['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-800">{{ $action['label'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $action['description'] }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <h2 class="font-semibold">Aucun espace de travail n'est encore attribué</h2>
            <p class="mt-1 text-sm">Contactez un administrateur de votre compagnie afin de vérifier vos rôles et habilitations.</p>
        </div>
    @endif
</div>
