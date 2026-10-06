<div>
    <x-admin-light.topbar title="Pointage" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <a href="{{ route('admin.evenements') }}" wire:navigate class="mb-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand"><x-ui.icon name="arrow-left" class="size-3.5" /> Événements</a>
        @if (! $ok || ! $evenement)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Événement introuvable.</p>
        @else
            <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Pointage · {{ $evenement['title'] }}</h2>
                    <div class="h-[3px] w-9 rounded bg-accent"></div>
                    <p class="mt-2 text-xs text-[#9AA6B8]">{{ ucfirst(\Illuminate\Support\Carbon::parse($evenement['starts_at'])->setTimezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY, HH[h]mm')) }}</p>
                </div>
                <div data-test="compteur-presence" class="rounded-[14px] border border-brand/10 bg-white px-5 py-3 text-center shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <p class="text-[22px] font-extrabold leading-none text-brand">{{ $presents }} <span class="text-[14px] font-bold text-[#9AA6B8]">/ {{ $nbInscrits }}</span></p>
                    <p class="mt-1 text-[11px] font-semibold text-[#5B677A]">présents / inscrits</p>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,420px)_1fr]">
                {{-- Scanner --}}
                <div class="space-y-4">
                    <div x-data="window.lecteurQr()" x-on:livewire:navigating.window="arreter()" class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        <div class="relative aspect-[4/3] bg-brand">
                            <video x-ref="video" playsinline muted class="size-full object-cover" x-show="actif"></video>
                            <div x-show="! actif" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-white">
                                <x-ui.icon name="qr-code" class="size-10 text-white/70" />
                                <p class="text-[13px] text-white/80">Scannez le billet du participant ou sa carte membre.</p>
                                <button type="button" x-on:click="demarrer((code) => $wire.pointer(code))" data-test="demarrer-scan" class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">Ouvrir la caméra</button>
                                <p x-show="erreur" x-text="erreur" class="text-[12px] font-semibold text-[#FFB4B0]"></p>
                            </div>
                            <button type="button" x-show="actif" x-on:click="arreter()" class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[12px] font-bold text-brand">Arrêter</button>
                        </div>
                        <form wire:submit="pointer" class="flex gap-2 border-t border-cloud-200 p-3">
                            <input wire:model="code" type="text" data-test="code-manuel" placeholder="Code du billet (B-XXXXXXXX) ou n° de carte" class="min-w-0 flex-1 rounded-[10px] border border-brand/15 px-3 py-2 text-sm uppercase outline-none placeholder:normal-case focus:border-azure" />
                            <button type="submit" data-test="pointer-manuel" class="btn-tap rounded-[10px] bg-brand px-4 py-2 text-[13px] font-bold text-white hover:bg-brand/90">Pointer</button>
                        </form>
                    </div>

                    @if ($resultat)
                        @php
                            $okR = $resultat['ok'] ?? false;
                            $deja = $resultat['deja'] ?? false;
                            $nonInscrit = ($resultat['code'] ?? null) === 'non_inscrit';
                        @endphp
                        <div data-test="resultat-pointage" class="panel-enter flex items-center gap-3 rounded-[16px] border p-4 {{ $okR ? ($deja ? 'border-[#F5A623]/40 bg-[#FFF8EC]' : 'border-[#22A85A]/30 bg-[#EAF6EE]') : 'border-accent/30 bg-accent/5' }}">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $okR ? ($deja ? 'bg-[#F5A623]/20 text-[#B27007]' : 'bg-[#22A85A]/20 text-[#1C8F4C]') : 'bg-accent/15 text-accent' }}">
                                <x-ui.icon :name="$okR ? ($deja ? 'info' : 'check-circle') : 'x-circle'" class="size-6" />
                            </span>
                            <div class="min-w-0 flex-1">
                                @if ($okR)
                                    <p class="text-[14px] font-extrabold text-brand">{{ $resultat['membre']['nom'] }}</p>
                                    <p class="text-[12.5px] {{ $deja ? 'text-[#8A5A08]' : 'text-[#1C8F4C]' }}">{{ $deja ? 'Déjà pointé(e) à '.\Illuminate\Support\Carbon::parse($resultat['present_at'])->setTimezone(config('app.timezone'))->format('H\hi') : 'Présence enregistrée' }}</p>
                                @else
                                    <p class="text-[13.5px] font-bold text-accent">{{ $resultat['message'] ?? 'Code non reconnu.' }}</p>
                                @endif
                            </div>
                            @if ($nonInscrit)
                                <button type="button" wire:click="pointer(@js($resultat['code_scanne']), true)" data-test="inscrire-sur-place" class="btn-tap shrink-0 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">Inscrire sur place</button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Liste des inscrits --}}
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-cloud-200 px-5 py-3">
                        <p class="text-[13px] font-bold text-brand">Inscrits</p>
                        <input wire:model.live.debounce.250ms="filtre" type="search" placeholder="Filtrer…" class="w-[220px] rounded-full border border-brand/15 px-3 py-1.5 text-xs outline-none focus:border-azure" />
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto px-5">
                        @forelse ($inscrits as $i)
                            <div data-test="ligne-inscrit-pointage" wire:key="pi-{{ $i['id'] }}" class="flex items-center gap-3 border-t border-[#EDF0F5] py-2.5 first:border-t-0">
                                <x-messagerie.avatar :personne="['prenom' => $i['nom'], 'nom' => '', 'photo' => $i['photo'], 'role' => $i['role']]" taille="size-8" texte="text-[10px]" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-bold text-brand">{{ $i['nom'] }}</p>
                                    <p class="truncate font-mono text-[11px] text-[#9AA6B8]">{{ $i['billet'] }}</p>
                                </div>
                                @if ($i['present_at'])
                                    <span class="shrink-0 rounded-full bg-[#22A85A]/10 px-2.5 py-1 text-[11px] font-bold text-[#1C8F4C]">Présent · {{ \Illuminate\Support\Carbon::parse($i['present_at'])->setTimezone(config('app.timezone'))->format('H\hi') }}</span>
                                @else
                                    <button type="button" wire:click="pointer('{{ $i['billet'] }}')" class="btn-tap shrink-0 rounded-full border border-brand/15 px-3 py-1 text-[11.5px] font-bold text-brand hover:bg-cloud">Pointer</button>
                                @endif
                            </div>
                        @empty
                            <p class="py-10 text-center text-sm text-[#5B677A]">Aucun inscrit.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
