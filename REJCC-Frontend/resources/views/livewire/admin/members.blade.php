<div>
    <x-admin-light.topbar title="Membres" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Base de données des membres</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">{{ $total }} compte{{ $total > 1 ? 's' : '' }} — classés par rôle</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Rechercher nom ou email…" class="w-56 rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure" />
                <button wire:click="setFiltre('tous')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Tous</button>
                <button wire:click="setFiltre('actifs')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'actifs' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Actifs</button>
                <button wire:click="setFiltre('suspendus')" class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $filtre === 'suspendus' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Suspendus</button>
                <a href="{{ route('admin.inscription') }}" wire:navigate class="rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600">+ Nouvelle inscription</a>
            </div>
        </div>

        <div class="space-y-7">
            @forelse ($groupes as $titre => $liste)
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-[#9AA6B8]">{{ $titre }} ({{ $liste->count() }})</p>
                    <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @foreach ($liste as $m)
                            <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0 {{ $m['actif'] ? '' : 'opacity-55' }}">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, {{ $m['role'] === 'admin' ? '#AC0100, #D95B5A' : ($m['role'] === 'mentor' ? '#F5A623, #F7C873' : '#031D59, #4F6FBF') }})">{{ $m['initiales'] }}</span>
                                <div class="min-w-[200px] flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-[13.5px] font-bold text-brand">{{ $m['nom'] }}</p>
                                        @if ($m['restreint'])
                                            <span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[9.5px] font-bold text-[#B87A0D]">ACCÈS LIMITÉ</span>
                                        @endif
                                        @unless ($m['actif'])
                                            <span class="rounded-full bg-[#EEF1F5] px-2 py-0.5 text-[9.5px] font-bold text-[#9AA6B8]">SUSPENDU</span>
                                        @endunless
                                    </div>
                                    <p class="text-xs text-[#5B677A]">{{ $m['email'] }} · {{ $m['telephone'] }}@if ($m['ville']) · {{ $m['ville'] }}@endif</p>
                                    <p class="mt-0.5 text-[11px] text-[#9AA6B8]">Inscrit en {{ $m['depuis'] }}</p>
                                </div>
                                <button wire:click="toggleStatut({{ $m['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $m['actif'] ? 'Suspendre' : 'Réactiver' }}</button>
                                @if ($m['role'] !== 'admin')
                                    <button wire:click="delete({{ $m['id'] }})" wire:confirm="Supprimer définitivement le compte de {{ $m['nom'] }} ? Cette action est irréversible." class="shrink-0 rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                                        <x-ui.icon name="trash-2" class="size-3.5" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="rounded-[18px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucun compte ne correspond à cette recherche.</p>
            @endforelse
        </div>
    </div>
</div>
