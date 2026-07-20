@php
    use Illuminate\Support\Carbon;

    $accepts = $event['accepts'] ?? false;
    $isOpen = $event['is_open'] ?? false;
    $isFull = $event['is_full'] ?? false;
    $remaining = $event['remaining'] ?? null;
    $capacity = $event['capacity'] ?? null;
    $date = ($event['starts_at'] ?? null) ? Carbon::parse($event['starts_at']) : null;
@endphp

<div>
    <section class="relative overflow-hidden bg-brand py-16 text-white sm:py-20">
        <div class="pointer-events-none absolute inset-0 bg-dots opacity-40 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
        <div class="relative mx-auto max-w-2xl px-5 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-white/70">Inscription · REJCC</p>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">{{ $event['title'] ?? 'Événement' }}</h1>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[13px] font-semibold text-white/85">
                @if ($date)
                    <span class="inline-flex items-center gap-1.5">
                        <x-ui.icon name="calendar" class="size-4" /> {{ $date->locale('fr')->translatedFormat('l j F Y') }}
                        @if ($date->format('H:i') !== '00:00') · {{ $date->format('H\hi') }} @endif
                    </span>
                @endif
                @if ($event['location'] ?? null)
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="map-pin" class="size-4" /> {{ $event['location'] }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-cloud py-12 sm:py-16">
        <div class="mx-auto max-w-lg px-5">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-8">

                @if ($event['description'] ?? null)
                    <p class="mb-6 text-[14px] leading-relaxed text-ink/75">{{ $event['description'] }}</p>
                @endif

                {{-- ══════════ Confirmation ══════════ --}}
                @if ($submitted)
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-[#22A85A]/10 text-[#22A85A]">
                            <x-ui.icon name="check-circle" class="size-8" />
                        </span>
                        <p class="text-lg font-bold text-brand">Inscription confirmée !</p>
                        <p class="max-w-sm text-sm text-ink/70">Merci {{ $prenom }}, votre place est réservée pour <strong>{{ $event['title'] ?? 'l\'événement' }}</strong>. Nous avons hâte de vous accueillir. Pensez à noter la date.</p>
                    </div>

                {{-- ══════════ Inscriptions fermées ══════════ --}}
                @elseif (! $isOpen)
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-[#9AA6B8]/10 text-[#5B677A]">
                            <x-ui.icon name="clock" class="size-7" />
                        </span>
                        <p class="text-lg font-bold text-brand">Inscriptions fermées</p>
                        <p class="max-w-sm text-sm text-ink/70">Les inscriptions pour cet événement ne sont plus ouvertes. Merci de votre intérêt !</p>
                    </div>

                {{-- ══════════ Complet ══════════ --}}
                @elseif ($isFull)
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
                            <x-ui.icon name="users" class="size-7" />
                        </span>
                        <p class="text-lg font-bold text-brand">Complet</p>
                        <p class="max-w-sm text-sm text-ink/70">Toutes les places ont été réservées. Les inscriptions sont complètes — merci de votre engouement !</p>
                    </div>

                {{-- ══════════ Formulaire d'inscription ══════════ --}}
                @else
                    @if ($remaining !== null && $remaining <= 30)
                        <p class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3.5 py-1.5 text-xs font-bold text-accent">
                            <x-ui.icon name="flame" class="size-3.5" /> Plus que {{ $remaining }} place{{ $remaining > 1 ? 's' : '' }} !
                        </p>
                    @endif

                    <p class="mb-4 text-[13px] font-semibold text-brand">Remplissez ce formulaire pour réserver votre place :</p>

                    <form wire:submit="register" class="flex flex-col gap-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-[#5B677A]">Prénom</label>
                                <input wire:model="prenom" type="text" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                                @error('prenom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-[#5B677A]">Nom</label>
                                <input wire:model="nom" type="text" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                                @error('nom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-[#5B677A]">Téléphone / WhatsApp</label>
                            <input wire:model="telephone" type="tel" inputmode="tel" placeholder="Ex : 0700000000" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                            @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-[#5B677A]">E-mail <span class="font-normal text-[#9AA6B8]">(optionnel)</span></label>
                            <input wire:model="email" type="email" placeholder="vous@exemple.com" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                            @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <label class="flex items-center gap-2.5 rounded-xl border border-brand/10 bg-cloud/50 px-3.5 py-3 text-sm text-ink/80">
                            <input wire:model="is_member" type="checkbox" class="size-4 rounded border-brand/25 text-brand focus:ring-accent/25" />
                            Je suis déjà membre du REJCC
                        </label>

                        <button type="submit" wire:loading.attr="disabled" wire:target="register" class="btn-tap mt-1 inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                            <span wire:loading.remove wire:target="register">Je réserve ma place</span>
                            <span wire:loading wire:target="register">Inscription…</span>
                        </button>
                        <p class="text-center text-[11px] text-[#9AA6B8]">Vos informations servent uniquement à l'organisation de l'événement.</p>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>
