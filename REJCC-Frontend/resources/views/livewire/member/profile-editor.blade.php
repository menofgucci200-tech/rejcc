@php $user = \App\Support\Api::user(); @endphp

<x-member.dark-page title="Mon profil" subtitle="Gérez vos informations personnelles et votre visibilité dans le réseau." icon="user">
    <div class="max-w-[680px]">
        <!-- Avatar preview -->
        <div class="mb-8 flex items-center gap-[18px] rounded-2xl border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-6 py-5">
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full text-xl font-bold tracking-wide text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                {{ mb_substr($prenom ?: '?', 0, 1) }}{{ mb_substr($nom ?: '', 0, 1) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="m-0 text-base font-bold text-[var(--ms-text)]">{{ $prenom ?: 'Prénom' }} {{ $nom ?: 'Nom' }}</p>
                <p class="m-0 mt-1 text-[13px] text-[var(--ms-muted)]">{{ $user->email }}</p>
            </div>
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-4 rounded-[18px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-7 py-7 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="p-prenom" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Prénom</label>
                    <input wire:model="prenom" id="p-prenom" type="text" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                    @error('prenom') <span class="text-xs font-medium text-[#E84A43]">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-nom" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Nom</label>
                    <input wire:model="nom" id="p-nom" type="text" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                    @error('nom') <span class="text-xs font-medium text-[#E84A43]">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-tel" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Téléphone</label>
                    <input wire:model="telephone" id="p-tel" type="text" inputmode="numeric" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                    @error('telephone') <span class="text-xs font-medium text-[#E84A43]">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-genre" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Genre</label>
                    <select wire:model="genre" id="p-genre" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none">
                        <option value="">—</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-ville" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Ville</label>
                    <input wire:model="ville" id="p-ville" type="text" placeholder="Abidjan" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-secteur" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Domaine d'activité</label>
                    <input wire:model="secteur" id="p-secteur" type="text" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-profil" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Profil</label>
                    <select wire:model="profil" id="p-profil" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none">
                        <option value="">—</option>
                        @foreach ($profiles as $p)
                            <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="p-org" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Entreprise / projet</label>
                    <input wire:model="organisation" id="p-org" type="text" class="w-full rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none" />
                </div>

                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="p-bio" class="text-[12.5px] font-semibold text-[var(--ms-muted)]">Bio</label>
                    <textarea wire:model="bio" id="p-bio" placeholder="Décrivez-vous en quelques mots…" class="min-h-[110px] w-full resize-y rounded-[10px] border border-[var(--ms-bc2)] bg-[var(--ms-surf)] px-3.5 py-2.5 text-[13.5px] text-[var(--ms-text)] outline-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-1 sm:col-span-2">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-[11px] px-7 py-2.5 text-[13.5px] font-bold text-white transition-all"
                        style="background: {{ $status === 'saved' ? 'rgba(52,211,153,0.18)' : '#AC0100' }}; {{ $status === 'saved' ? 'color:#34D399;border:1px solid rgba(52,211,153,0.35)' : '' }}"
                    >
                        <span wire:loading.remove>{{ $status === 'saved' ? 'Enregistré !' : 'Enregistrer les modifications' }}</span>
                        <span wire:loading>Enregistrement…</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-member.dark-page>
