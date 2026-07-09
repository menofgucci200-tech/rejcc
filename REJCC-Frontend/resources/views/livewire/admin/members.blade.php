<div>
    <x-admin-light.topbar title="Membres" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-8">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Vérification des pièces d'identité</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @foreach ($verifications as $v)
                    <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, {{ $v['from'] }}, {{ $v['to'] }})">{{ $v['initiales'] }}</span>
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $v['nom'] }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">{{ $v['document'] }} · soumis {{ $v['date'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $v['statutColor'] }}; background: {{ $v['statutBg'] }}">{{ $v['statutLabel'] }}</span>
                        @if ($v['enAttente'])
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="validerVerif({{ $v['index'] }})" class="rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">Valider</button>
                                <button wire:click="rejeterVerif({{ $v['index'] }})" class="rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Rejeter</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Tous les membres</h2>
                    <div class="h-[3px] w-9 rounded bg-accent"></div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button wire:click="setFiltre('tous')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                    <button wire:click="setFiltre('actifs')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'actifs' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Actifs</button>
                    <button wire:click="setFiltre('suspendus')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'suspendus' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Suspendus</button>
                    <button wire:click="toggleFormulaire" class="ml-1.5 rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white hover:bg-accent-600">{{ $showForm ? 'Fermer le formulaire' : '+ Créer un compte' }}</button>
                </div>
            </div>

            @if ($showForm)
                <div class="mb-4 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prénom</label>
                        <input wire:model="prenom" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('prenom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom</label>
                        <input wire:model="nom" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('nom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">E-mail</label>
                        <input wire:model="email" type="email" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('email') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone</label>
                        <input wire:model="telephone" type="text" inputmode="numeric" placeholder="0700000000" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('telephone') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Mot de passe temporaire</label>
                        <input wire:model="password" type="password" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                        @error('password') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Rôle</label>
                        <select wire:model="role" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                            <option value="member">Membre</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="flex gap-2.5 sm:col-span-2">
                        <button wire:click="save" wire:loading.attr="disabled" class="rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60">Créer le compte</button>
                        <button wire:click="toggleFormulaire" class="rounded-[9px] border border-[#C9D3E6] bg-white px-5 py-2.5 text-sm font-bold text-brand hover:bg-cloud">Annuler</button>
                    </div>
                </div>
            @endif

            @if ($messageCreation)
                <p class="mb-3 text-xs font-semibold text-[#22A85A]">{{ $messageCreation }}</p>
            @endif

            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($members as $m)
                    @php
                        $actif = $m->is_active ?? true;
                        $estAdmin = $m->role === 'admin';
                    @endphp
                    <div class="flex flex-wrap items-center gap-3.5 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] text-xs font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ mb_substr($m->prenom, 0, 1) }}{{ mb_substr($m->nom, 0, 1) }}
                        </span>
                        <div class="min-w-[180px] flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[13.5px] font-bold text-brand">{{ $m->prenom }} {{ $m->nom }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="color: {{ $estAdmin ? '#AC0100' : '#5B677A' }}; background: {{ $estAdmin ? '#F9E9E9' : '#EEF1F5' }}">{{ $estAdmin ? 'Admin' : 'Membre' }}</span>
                            </div>
                            <p class="text-xs text-[#5B677A]">{{ $m->profil ?? '—' }} · {{ $m->ville ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $actif ? '#22A85A' : '#AC0100' }}; background: {{ $actif ? '#EAF6EE' : '#F9E9E9' }}">{{ $actif ? 'Actif' : 'Suspendu' }}</span>
                        <div class="ml-auto flex shrink-0 gap-2">
                            <button wire:click="toggleRole({{ $m->id }})" class="whitespace-nowrap rounded-[9px] border border-[#C9D6F0] px-3 py-1.5 text-[11.5px] font-bold text-azure hover:bg-[#E8EDF8]">{{ $estAdmin ? 'Rétrograder' : 'Promouvoir admin' }}</button>
                            <button wire:click="toggleStatut({{ $m->id }})" class="whitespace-nowrap rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">{{ $actif ? 'Suspendre' : 'Réactiver' }}</button>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun membre trouvé.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
