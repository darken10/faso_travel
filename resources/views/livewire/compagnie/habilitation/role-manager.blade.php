<div class="p-4 lg:p-6 space-y-6">

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rôles et habilitations</h1>
            <p class="text-sm text-gray-500 mt-1">
                Les rôles livrés avec l'application ne se modifient pas : dérivez-en un pour l'adapter
                à votre organisation.
            </p>
        </div>
        <button wire:click="openCreate"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
            Nouveau rôle
        </button>
    </div>

    {{-- Cumuls interdits --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <p class="text-sm font-medium text-amber-900">Cumuls refusés à l'attribution</p>
        <ul class="mt-2 space-y-1 text-xs text-amber-800">
            @foreach($cumulsInterdits as [$a, $b, $raison])
                <li>· <span class="font-medium">{{ $a }}</span> + <span class="font-medium">{{ $b }}</span> — {{ $raison }}</li>
            @endforeach
            <li>· tout rôle de compagnie + tout rôle plateforme — un employé de compagnie ne porte jamais un rôle plateforme</li>
        </ul>
    </div>

    {{-- Rôles propres à la compagnie --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">Vos rôles</h2>
        </div>
        @if($rolesCompagnie->isEmpty())
            <p class="px-4 py-6 text-sm text-gray-500">
                Aucun rôle propre. Vous utilisez les rôles livrés ci-dessous.
            </p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Rôle</th>
                        <th class="px-4 py-2 text-left">Rang</th>
                        <th class="px-4 py-2 text-left">Permissions</th>
                        <th class="px-4 py-2 text-left">Comptes</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rolesCompagnie as $role)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $role->label }}</p>
                                @if($role->description)
                                    <p class="text-xs text-gray-500">{{ $role->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $role->rang }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $role->permissions_count }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $role->users_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button wire:click="openEdit({{ $role->id }})" class="text-blue-600 hover:underline text-xs">Modifier</button>
                                <button wire:click="delete({{ $role->id }})"
                                        wire:confirm="Supprimer ce rôle ?"
                                        class="text-red-600 hover:underline text-xs ml-3">Supprimer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Gabarits livrés --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">Rôles livrés avec l'application</h2>
            <p class="text-xs text-gray-500 mt-0.5">Lecture seule — dérivez-en un pour l'adapter.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($gabarits as $gabarit)
                <div class="px-4 py-3 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $gabarit->label }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $gabarit->description }}</p>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <span class="text-xs text-gray-500">rang {{ $gabarit->rang }}</span>
                        <span class="text-xs text-gray-500">{{ $gabarit->permissions_count }} perm.</span>
                        <button wire:click="derive({{ $gabarit->id }})" class="text-blue-600 hover:underline text-xs">Dériver</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Formulaire --}}
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl w-full max-w-3xl my-8">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">{{ $editingId ? 'Modifier le rôle' : 'Nouveau rôle' }}</h3>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Libellé *</label>
                            <input wire:model="label" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="Ex: Chef de gare de Bobo">
                            @error('label') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Rang <span class="text-xs text-gray-400">(max {{ $rangMaximum }})</span>
                            </label>
                            <input wire:model="rang" type="number" min="0" max="{{ $rangMaximum }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @error('rang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Permissions
                                <span class="text-xs text-gray-400">({{ count($selectedPermissions) }} sélectionnée(s))</span>
                            </label>
                            <select wire:model.live="domaineOuvert"
                                    class="px-2 py-1 border border-gray-300 rounded-lg text-xs">
                                @foreach($domaines as $domaine)
                                    <option value="{{ $domaine->value }}">{{ $domaine->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-72 overflow-y-auto">
                            @forelse($permissionsDuDomaine as $permission)
                                <div class="px-3 py-2 flex items-center gap-3">
                                    <input type="checkbox"
                                           wire:click="togglePermission('{{ $permission->name }}')"
                                           @checked(isset($selectedPermissions[$permission->name]))
                                           class="rounded text-blue-600">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-800">{{ $permission->label }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $permission->name }}</p>
                                    </div>
                                    @if(isset($selectedPermissions[$permission->name]))
                                        <select wire:model="selectedPermissions.{{ $permission->name }}"
                                                class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            <option value="compagnie">Toute la compagnie</option>
                                            <option value="gare">Sa gare</option>
                                            <option value="own">Ses enregistrements</option>
                                        </select>
                                    @endif
                                    @if($permission->is_sensitive)
                                        <span class="text-xs text-amber-600" title="Chaque usage est journalisé">journalisée</span>
                                    @endif
                                </div>
                            @empty
                                <p class="px-3 py-4 text-sm text-gray-500">Aucune permission dans ce domaine.</p>
                            @endforelse
                        </div>
                        @error('selectedPermissions') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
