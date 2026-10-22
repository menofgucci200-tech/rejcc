<div>
    <x-admin-light.topbar :title="$departement['label']" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        @if ($adminGeneral)
            <a href="{{ route('departements.index') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#5B677A] hover:text-brand"><x-ui.icon name="chevron-right" class="size-3.5 rotate-180" /> Tous les départements</a>
        @endif

        <section class="mb-8 flex flex-wrap items-center gap-5 rounded-[22px] border border-[#F3E3D3] bg-gradient-to-br from-[#FFF8F1] via-[#FFFDF8] to-[#F2FAF5] p-6">
            <x-ui.illustration :name="\App\Support\Espace::ILLUSTRATIONS[$departement['slug']] ?? 'etoile'" size="size-16" />
            <div class="min-w-[220px] flex-1">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#E07A5F]">Département</p>
                <h1 class="mt-1 text-[24px] font-bold text-[#3B3F5C]">{{ $departement['label'] }}</h1>
                <p class="mt-2 max-w-3xl text-[14px] leading-relaxed text-[#6B6F86]">{{ $departement['mission'] }}</p>
            </div>
        </section>

        @if ($cockpit)
            @php
                $c = $cockpit['chiffres'];
                $tuiles = [
                    ['Sessions à venir', $c['sessions_a_venir'], 'agenda', '#FFF3E8'],
                    ['Inscrits aux sessions', $c['inscrits_a_venir'], 'equipe', '#EFFAF4'],
                    ['Présence (30 jours)', $c['presence_30j'] === null ? '—' : $c['presence_30j'].' %', 'visio', '#EEF6FD'],
                    ['Apprenants en cours', $c['apprenants'], 'livre', '#F3EFFC'],
                    ['Certificats (30 jours)', $c['certificats_30j'], 'diplome', '#FFF8E1'],
                ];
                $teintes = ['urgent' => 'bg-[#FDEEEE] text-[#A23B3B]', 'attention' => 'bg-[#FDF2E3] text-[#8A5A12]', 'info' => 'bg-[#EEF6FD] text-[#2D5C8F]'];
            @endphp
            <section class="mb-8" data-test="cockpit-formation">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    @foreach ($tuiles as [$libelle, $valeur, $icone, $fond])
                        <div class="flex items-center gap-3 rounded-[16px] border border-[#EDE9F3] bg-white p-3.5">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-[12px]" style="background: {{ $fond }}"><x-ui.illustration :name="$icone" size="size-7" /></span>
                            <span class="min-w-0"><span class="block font-display text-[24px] leading-none text-brand">{{ $valeur }}</span><span class="mt-1 block text-[11.5px] font-semibold leading-tight text-[#5B677A]">{{ $libelle }}</span></span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 rounded-[18px] border border-[#EDE9F3] bg-white p-4">
                    <p class="mb-2.5 flex items-center gap-2 text-[14px] font-bold text-[#3B3F5C]"><x-ui.illustration name="cahier" size="size-6" /> À faire</p>
                    @forelse ($cockpit['a_faire'] as $t)
                        <a href="{{ $t['session_id'] ? route('admin.lives') : route('admin.formations') }}" wire:navigate class="mb-1.5 flex flex-wrap items-center gap-2 rounded-[12px] px-3 py-2 text-[12.5px] hover:brightness-95 {{ $teintes[$t['niveau']] ?? $teintes['info'] }}">
                            <span class="size-1.5 shrink-0 rounded-full bg-current"></span>
                            <span class="font-bold">{{ $t['titre'] }}</span>
                            @if ($t['quand'])<span class="opacity-80">· {{ $t['quand'] }}</span>@endif
                            <span class="flex flex-wrap gap-1">
                                @foreach ($t['problemes'] as $p)<span class="rounded-full bg-white/70 px-2 py-0.5 text-[11.5px] font-semibold">{{ $p }}</span>@endforeach
                            </span>
                        </a>
                    @empty
                        <p class="text-[12.5px] text-[#5B677A]">Rien à signaler : tout est en ordre.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <section class="mb-8">
            <h2 class="mb-4 text-[17px] font-bold text-[#3B3F5C]">Vos rubriques</h2>
            @if ($rubriques->isEmpty())
                <p class="rounded-[18px] border border-brand/10 bg-white px-5 py-10 text-center text-sm text-[#5B677A]">Aucune rubrique n'est encore ouverte à ce compte. L'administrateur général peut les ajouter.</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rubriques as $r)
                        @php $n = $pastilles[$r['route']] ?? 0; @endphp
                        @php $fond = ['#FFF3E8', '#EFFAF4', '#F3EFFC', '#FFF8E1', '#EEF6FD'][$loop->index % 5]; @endphp
                        <a href="{{ route($r['route']) }}" wire:navigate data-test="rubrique-{{ \Illuminate\Support\Str::slug($r['label']) }}"
                            class="group flex items-center gap-3.5 rounded-[18px] border border-[#EDE9F3] bg-white px-4 py-3.5 shadow-[0_2px_10px_-6px_rgba(59,63,92,.2)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(59,63,92,.35)]">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px]" style="background: {{ $fond }}"><x-ui.illustration :name="\App\Support\Espace::ILLUSTRATIONS_RUBRIQUES[$r['route']] ?? 'etoile'" size="size-8" /></span>
                            <span class="min-w-0 flex-1 truncate text-[14px] font-bold text-[#3B3F5C]">{{ $r['label'] }}</span>
                            @if ($n > 0)
                                <span class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[11px] font-bold text-white">{{ $n }} à traiter</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section id="acces" data-test="acces-departement">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <h2 class="flex flex-1 items-center gap-2.5 text-[17px] font-bold text-[#3B3F5C]"><x-ui.illustration name="equipe" size="size-8" /> {{ $adminGeneral ? 'Accès au département' : "L'équipe du département" }}</h2>
                @if ($adminGeneral)
                    <button type="button" wire:click="ouvrirAjout('membre')" data-test="acces-membre-existant" class="btn-tap rounded-full border border-[#E4E1EC] bg-white px-4 py-2 text-[12.5px] font-bold text-[#3B3F5C] hover:bg-[#F7F5FB]">Donner l'accès à un membre</button>
                    <button type="button" wire:click="ouvrirAjout('nouveau')" data-test="acces-nouveau-compte" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Créer un compte</button>
                @endif
            </div>

            @if ($message)
                <p class="mb-4 rounded-[14px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-3 text-[13px] font-semibold text-[#2E7D5B]">{{ $message }}</p>
            @endif
            @if ($erreur)
                <p class="mb-4 rounded-[14px] bg-[#FDEEEE] px-4 py-3 text-[13px] font-semibold text-[#C24E4E]">{{ $erreur }}</p>
            @endif

            @if ($adminGeneral && $ajout === 'membre')
                <div class="panel-enter mb-4 rounded-[18px] border border-[#E4E1EC] bg-white p-5">
                    <p class="mb-2 text-[13.5px] font-bold text-[#3B3F5C]">Donner l'accès à un membre du réseau</p>
                    <p class="mb-3 text-[12.5px] text-[#5B677A]">Il garde son compte et son mot de passe ; il se connecte sur le dashboard et arrive dans l'espace {{ $departement['label'] }}.</p>
                    <input wire:model.live.debounce.300ms="recherche" type="search" placeholder="Nom, prénom ou e-mail du membre" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                    @if ($resultats)
                        <div class="mt-2 overflow-hidden rounded-[12px] border border-[#E4E1EC]">
                            @foreach ($resultats as $m)
                                <div class="flex flex-wrap items-center gap-3 border-t border-[#F1EEF6] px-3.5 py-2.5 first:border-t-0">
                                    <span class="min-w-0 flex-1"><span class="block text-[13px] font-semibold text-[#3B3F5C]">{{ $m['prenom'] }} {{ $m['nom'] }}</span><span class="text-[12px] text-[#5B677A]">{{ $m['email'] }}@if (! empty($m['bureau_departement_label'])) · déjà dans {{ $m['bureau_departement_label'] }}@elseif ($m['role'] === 'admin') · administrateur @endif</span></span>
                                    @if ($m['role'] === 'admin' && empty($m['bureau_departement']))
                                        <span class="text-[11.5px] font-semibold text-[#9A9DB0]">Administrateur général</span>
                                    @else
                                        <button type="button" wire:click="donnerAcces({{ $m['id'] }}, @js(trim($m['prenom'].' '.$m['nom'])))" wire:confirm="Donner à {{ $m['prenom'] }} {{ $m['nom'] }} l'accès à l'espace {{ $departement['label'] }} ?" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90">Donner l'accès</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif (mb_strlen(trim($recherche)) >= 2)
                        <p class="mt-2 text-[12.5px] text-[#5B677A]">Aucun membre trouvé.</p>
                    @endif
                </div>
            @endif

            @if ($adminGeneral && $ajout === 'nouveau')
                <div class="panel-enter mb-4 rounded-[18px] border border-[#E4E1EC] bg-white p-5">
                    <p class="mb-3 text-[13.5px] font-bold text-[#3B3F5C]">Créer un compte pour le département {{ $departement['label'] }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['prenom' => 'Prénom', 'nom' => 'Nom', 'email' => 'E-mail', 'telephone' => 'Téléphone (10 chiffres)'] as $champ => $libelle)
                            <div>
                                <label for="acces-{{ $champ }}" class="mb-1 block text-xs font-semibold text-[#5B677A]">{{ $libelle }}</label>
                                <input id="acces-{{ $champ }}" wire:model="{{ $champ }}" type="{{ $champ === 'email' ? 'email' : 'text' }}" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 text-sm outline-none focus:border-azure">
                                @error($champ) <span class="text-xs text-accent">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                        <div class="sm:col-span-2">
                            <label for="acces-mdp" class="mb-1 block text-xs font-semibold text-[#5B677A]">Mot de passe (à transmettre à la personne)</label>
                            <input id="acces-mdp" wire:model="motDePasse" type="text" class="w-full rounded-[11px] border border-[#E4E1EC] bg-[#FCFBFE] px-3.5 py-2.5 font-mono text-sm outline-none focus:border-azure sm:w-80">
                            @error('motDePasse') <span class="block text-xs text-accent">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <p class="mt-3 text-[12px] text-[#5B677A]">Rubriques ouvertes : {{ collect($departement['sections'])->map(fn ($s) => $sectionsDisponibles[$s] ?? $s)->implode(', ') }}. Vous pourrez les ajuster ensuite.</p>
                    <button type="button" wire:click="creerCompte" class="btn-tap mt-4 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">Créer l'accès</button>
                </div>
            @endif

            <div class="rounded-[18px] border border-[#EDE9F3] bg-white px-5 shadow-[0_2px_10px_-6px_rgba(59,63,92,.2)]">
                @forelse ($departement['membres'] as $m)
                    <div class="border-t border-[#EDF0F5] py-3.5 first:border-t-0" wire:key="acces-{{ $m['id'] }}">
                        <div class="flex flex-wrap items-center gap-3.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-[12px] font-bold text-[#3B3F5C]" style="background: {{ ['#FFD9A8', '#B8E6D1', '#D9CFF5', '#C3E3F7'][$loop->index % 4] }}">{{ mb_strtoupper(mb_substr($m['prenom'] ?? '', 0, 1).mb_substr($m['nom'] ?? '', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-bold text-[#3B3F5C]">{{ $m['prenom'] }} {{ $m['nom'] }} @unless ($m['is_active'])<span class="ml-1 rounded-full bg-cloud px-2 py-0.5 align-middle text-[10.5px] font-bold text-[#5B677A]">Suspendu</span>@endunless</p>
                                <p class="truncate text-xs text-[#5B677A]">{{ $m['email'] }}@if ($adminGeneral) · {{ count($m['permissions'] ?? []) }} rubrique{{ count($m['permissions'] ?? []) > 1 ? 's' : '' }}@endif</p>
                            </div>
                            @if ($adminGeneral)
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="modifierRubriques({{ $m['id'] }}, @js($m['permissions'] ?? []))" class="btn-tap rounded-full border border-[#E4E1EC] px-3 py-1.5 text-[12px] font-bold text-[#5B677A] hover:bg-[#F7F5FB]">Rubriques</button>
                                    <button type="button" wire:click="suspendre({{ $m['id'] }}, {{ $m['is_active'] ? 'true' : 'false' }})" class="btn-tap rounded-full border border-[#E4E1EC] px-3 py-1.5 text-[12px] font-bold text-[#5B677A] hover:bg-[#F7F5FB]">{{ $m['is_active'] ? 'Suspendre' : 'Réactiver' }}</button>
                                    <button type="button" wire:click="retirer({{ $m['id'] }})" wire:confirm="Retirer l'accès de {{ $m['prenom'] }} {{ $m['nom'] }} ? Il garde son compte de membre." class="btn-tap rounded-full px-3 py-1.5 text-[12px] font-bold text-accent hover:bg-accent/5">Retirer l'accès</button>
                                </div>
                            @endif
                        </div>
                        @if ($rubriquesDe === $m['id'])
                            <div class="panel-enter mt-3 rounded-[14px] bg-[#FBFAFD] p-4">
                                <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
                                    @foreach ($sectionsDisponibles as $cle => $libelle)
                                        <label class="inline-flex items-center gap-2 text-[12.5px] text-[#3B3F5C]"><input type="checkbox" wire:model="rubriquesChoisies" value="{{ $cle }}" class="size-3.5 rounded"> {{ $libelle }}</label>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="enregistrerRubriques" class="btn-tap mt-3 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">Enregistrer les rubriques</button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucun accès pour ce département.@if ($adminGeneral) Utilisez « Donner l'accès à un membre » ou « Créer un compte ».@endif</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
