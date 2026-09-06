@props(['member'])

@if ($member)
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerProfil">
        <div class="max-h-[88vh] w-full max-w-[560px] overflow-y-auto rounded-[20px] bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($member['photo'] ?? null)
                        <img src="{{ $member['photo'] }}" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
                    @else
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl text-lg font-bold text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            {{ mb_strtoupper(mb_substr(trim(($member['prenom'] ?? '').' '.($member['nom'] ?? '')), 0, 2)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-bold text-brand">{{ trim(($member['prenom'] ?? '').' '.($member['nom'] ?? '')) }}</p>
                        <p class="text-[11.5px] font-bold uppercase tracking-[0.06em] text-azure">{{ $member['role_label'] ?? 'Membre officiel' }}</p>
                    </div>
                </div>
                <button type="button" wire:click="fermerProfil" class="icon-btn shrink-0 rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
            </div>

            @if ($member['specialite'] ?? null)
                <p class="mb-4 rounded-[12px] bg-cloud/60 px-4 py-3 text-[13px] italic leading-relaxed text-ink">« {{ $member['specialite'] }} »</p>
            @endif

            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                @foreach ([
                    'Profil' => $member['profil_label'] ?? null, 'Secteur' => $member['secteur'] ?? null,
                    'Ville' => $member['ville'] ?? null, 'Entreprise / activité' => $member['organisation'] ?? null,
                    'Paroisse' => $member['paroisse'] ?? null, 'Membre depuis' => $member['membre_depuis'] ?? null,
                ] as $label => $value)
                    @if ($value)
                        <div>
                            <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">{{ $label }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-ink">{{ $value }}</p>
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($member['bio'] ?? null)
                <p class="mt-4 text-[13px] leading-relaxed text-[#5B677A]">{{ $member['bio'] }}</p>
            @endif

            @if (!empty($member['listings']))
                <div class="mt-4 border-t border-cloud-200 pt-4">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Services &amp; produits proposés</p>
                    <div class="flex flex-col gap-2">
                        @foreach ($member['listings'] as $l)
                            <div class="rounded-[10px] bg-cloud/50 px-3 py-2">
                                <p class="text-[12.5px] font-bold text-brand">{{ $l['title'] }} <span class="font-normal text-[#9AA6B8]">· {{ $l['category'] }}</span></p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (($member['telephone'] ?? null) || ($member['email'] ?? null))
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($member['telephone'] ?? null)
                        <a href="tel:{{ $member['telephone'] }}" class="inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">
                            <x-ui.icon name="phone" class="size-3.5" /> {{ $member['telephone'] }}
                        </a>
                    @endif
                    @if ($member['email'] ?? null)
                        <a href="mailto:{{ $member['email'] }}" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">
                            <x-ui.icon name="send" class="size-3.5" /> {{ $member['email'] }}
                        </a>
                    @endif
                </div>
            @endif

            <a
                href="{{ route('espace-membre.messaging', ['to' => $member['id']]) }}"
                wire:navigate
                class="btn-tap mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600"
            >
                <x-ui.icon name="message-circle" class="size-4" /> Envoyer un message
            </a>
        </div>
    </div>
@endif
