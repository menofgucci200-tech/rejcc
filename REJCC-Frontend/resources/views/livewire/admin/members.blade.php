<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-brand">Membres</h1>
            <p class="text-sm text-ink/60">{{ $members->count() }} compte{{ $members->count() > 1 ? 's' : '' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative max-w-xs flex-1">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
                <input wire:model.live.debounce.300ms="query" type="text" placeholder="Rechercher…" class="w-full rounded-full border border-brand/15 bg-white py-2 pl-10 pr-4 text-sm outline-none focus:border-brand" />
            </div>
            <button wire:click="openCreate" class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand/90">
                <x-ui.icon name="plus" class="size-4" /> Ajouter
            </button>
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-2xl border border-brand/15 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <p class="font-semibold text-brand">Nouveau compte</p>
                <button wire:click="closeForm"><x-ui.icon name="x" class="size-4 text-ink/50" /></button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Prénom</label>
                    <input wire:model="prenom" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('prenom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Nom</label>
                    <input wire:model="nom" type="text" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('nom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">E-mail</label>
                    <input wire:model="email" type="email" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('email') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Téléphone</label>
                    <input wire:model="telephone" type="text" inputmode="numeric" placeholder="0700000000" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('telephone') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Mot de passe (8 caractères min.)</label>
                    <input wire:model="password" type="password" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" />
                    @error('password') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-brand">Rôle</label>
                    <select wire:model="role" class="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand">
                        <option value="member">Membre</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
            </div>
            <button wire:click="save" wire:loading.attr="disabled" class="mt-4 inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2 text-sm font-semibold text-white transition hover:bg-accent-600 disabled:opacity-60">
                <x-ui.icon name="check" class="size-3.5" /> Créer le compte
            </button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-brand/10 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-brand/10 bg-cloud/60">
                <tr>
                    @foreach (['Nom', 'E-mail', 'Profil', 'Ville', 'Rôle', 'Depuis', 'Actions'] as $h)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink/50">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-brand/5">
                @foreach ($members as $m)
                    <tr class="hover:bg-cloud/50">
                        <td class="px-4 py-3 font-semibold text-brand">{{ $m->prenom }} {{ $m->nom }}</td>
                        <td class="px-4 py-3 text-ink/70">{{ $m->email }}</td>
                        <td class="px-4 py-3 text-ink/60">{{ $m->profil ?? '—' }}</td>
                        <td class="px-4 py-3 text-ink/60">{{ $m->ville ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $m->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-brand/10 text-brand' }}">
                                @if ($m->role === 'admin')
                                    <x-ui.icon name="shield" class="size-3" />
                                @endif
                                {{ $m->role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ink/50">{{ $m->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="toggleRole({{ $m->id }})"
                                    title="{{ $m->role === 'admin' ? 'Rétrograder en membre' : 'Promouvoir admin' }}"
                                    class="rounded-lg p-1.5 text-ink/40 transition hover:bg-brand/10 hover:text-brand"
                                >
                                    <x-ui.icon name="user-check" class="size-4" />
                                </button>
                                <button
                                    wire:click="deleteMember({{ $m->id }})"
                                    wire:confirm="Supprimer {{ $m->prenom }} {{ $m->nom }} ?"
                                    title="Supprimer"
                                    @disabled($m->role === 'admin')
                                    class="rounded-lg p-1.5 text-ink/40 transition hover:bg-accent/10 hover:text-accent disabled:opacity-30"
                                >
                                    <x-ui.icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($members->isEmpty())
            <p class="py-12 text-center text-sm text-ink/50">Aucun membre trouvé.</p>
        @endif
    </div>
</div>
