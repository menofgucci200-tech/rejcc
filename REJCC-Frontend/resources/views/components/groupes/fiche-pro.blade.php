@props(['fiche'])

{{-- Fiche professionnelle d'un membre dans un groupe sectoriel : spécialité,
     services, zone, disponibilités, contact, profil et autres groupes. --}}
@if ($fiche)
    @php
        $m = $fiche['membre'];
        $pro = $fiche['pro'];
        $estMentor = ($m['role'] ?? '') === 'mentor';
        $degrade = $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100';
        $initiales = mb_strtoupper(mb_substr($m['prenom'] ?? '', 0, 1).mb_substr($m['nom'] ?? '', 0, 1));
        $moi = ($m['id'] ?? 0) === (\App\Support\Api::user()->id ?? -1);
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerProfil" @keydown.escape.window="$wire.fermerProfil()">
        <div data-test="fiche-pro" role="dialog" aria-modal="true" class="max-h-[90vh] w-full max-w-[620px] overflow-y-auto rounded-[20px] bg-white shadow-2xl">
            {{-- En-tête --}}
            <div class="relative rounded-t-[20px] bg-gradient-to-br from-brand to-[#0A2C6E] px-6 pb-5 pt-6 text-white">
                <button type="button" wire:click="fermerProfil" aria-label="Fermer" class="absolute right-4 top-4 flex size-8 items-center justify-center rounded-full bg-white/10 hover:bg-white/20"><x-ui.icon name="x" class="size-4" /></button>
                <p class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-white/60">{{ $fiche['groupe']['nom'] }}</p>
                <div class="mt-3 flex items-center gap-3.5">
                    <span x-data="{ erreur: false }" class="relative shrink-0">
                        @if ($m['photo'] ?? null)
                            <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $m['photo'] }}" alt="" class="size-16 rounded-2xl object-cover ring-2 ring-white/20">
                        @endif
                        <span @if ($m['photo'] ?? null) x-show="erreur" style="display: none; background: linear-gradient(135deg, {{ $degrade }})" @else style="background: linear-gradient(135deg, {{ $degrade }})" @endif class="flex size-16 items-center justify-center rounded-2xl text-xl font-bold text-white ring-2 ring-white/20">{{ $initiales }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[18px] font-extrabold leading-tight">{{ $m['prenom'] }} {{ $m['nom'] }}</p>
                        @if ($m['titre'] ?? null)<p class="mt-0.5 text-[13px] text-white/75">{{ $m['titre'] }}</p>@endif
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.08em] {{ $estMentor ? 'text-[#FF9C96]' : 'text-[#8FA3D9]' }}">{{ $m['role_label'] ?? 'Membre officiel' }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{-- Activité dans le groupe --}}
                <section data-test="fiche-pro-activite">
                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Spécialité</p>
                    <p class="mt-1 whitespace-pre-line text-[14px] leading-relaxed text-ink">{{ $pro['specialite'] }}</p>
                    @if (! empty($pro['services']))
                        <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Services proposés</p>
                        <ul class="mt-2 grid gap-1.5 sm:grid-cols-2">
                            @foreach ($pro['services'] as $service)
                                <li class="flex items-start gap-2 text-[13px] text-ink"><x-ui.icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-[#22A85A]" /> {{ $service }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['map-pin', "Zone d'intervention", $pro['zone'] ?: ($m['ville'] ?? null)],
                            ['clock', 'Disponibilités', $pro['disponibilites']],
                            ['store', 'Entreprise / activité', $m['organisation'] ?? null],
                            ['user', 'Résidence', $m['ville'] ?? null],
                        ] as [$icon, $label, $valeur])
                            @if ($valeur)
                                <div class="flex items-start gap-2.5 rounded-[12px] bg-cloud/60 p-3">
                                    <x-ui.icon :name="$icon" class="mt-0.5 size-4 shrink-0 text-brand" />
                                    <div><p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ $label }}</p><p class="text-[13px] font-semibold text-ink">{{ $valeur }}</p></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>

                {{ $slot ?? '' }}

                @if ($m['bio'] ?? null)
                    <section class="mt-5 border-t border-cloud-200 pt-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Présentation</p>
                        <p class="mt-1 whitespace-pre-line text-[13px] leading-relaxed text-[#5B677A]">{{ $m['bio'] }}</p>
                    </section>
                @endif

                @if (! empty($fiche['autres_groupes']))
                    <section class="mt-5 border-t border-cloud-200 pt-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Aussi présent dans</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($fiche['autres_groupes'] as $g)
                                <a href="{{ route('espace-membre.groupes.membres', $g['id']) }}" wire:navigate title="{{ $g['specialite'] }}" class="rounded-full bg-brand/[.06] px-2.5 py-1 text-[11.5px] font-semibold text-brand hover:bg-brand hover:text-white">{{ $g['nom'] }}</a>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Contact --}}
                <div class="mt-6 flex flex-wrap gap-2 border-t border-cloud-200 pt-5">
                    @if (! $moi)
                        <a href="{{ route('espace-membre.messaging', ['to' => $m['id']]) }}" wire:navigate data-test="fiche-pro-message" class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="message-circle" class="size-4" /> Envoyer un message</a>
                    @endif
                    @if ($m['telephone'] ?? null)
                        <a href="tel:{{ $m['telephone'] }}" data-test="fiche-pro-telephone" class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="phone" class="size-4" /> {{ $m['telephone'] }}</a>
                    @endif
                    @if ($m['email'] ?? null)
                        <a href="mailto:{{ $m['email'] }}" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="send" class="size-4" /> E-mail</a>
                    @endif
                    @if ($m['code'] ?? null)
                        <a href="{{ url('/carte/'.$m['code']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-2 py-2.5 text-[12.5px] font-bold text-azure hover:underline"><x-ui.icon name="external-link" class="size-3.5" /> Page complète</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
