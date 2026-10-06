@php
    use Illuminate\Support\Carbon;

    $accepts = $event['accepts'] ?? false;
    $isOpen = $event['is_open'] ?? false;
    $isFull = $event['is_full'] ?? false;
    $remaining = $event['remaining'] ?? null;
    $capacity = $event['capacity'] ?? null;
    $date = ($event['starts_at'] ?? null) ? Carbon::parse($event['starts_at']) : null;
    $deadline = ($event['registration_deadline'] ?? null) ? Carbon::parse($event['registration_deadline']) : null;
    $isPastDeadline = $event['is_past_deadline'] ?? false;
    $fields = $event['fields'] ?? [];
    $inputClass = 'w-full rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15';
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
            <div class="overflow-hidden rounded-3xl border border-brand/10 bg-white shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)]">
                @if ($event['poster'] ?? null)
                    <img src="{{ $event['poster'] }}" alt="Affiche — {{ $event['title'] ?? '' }}" class="max-h-80 w-full object-cover">
                @endif
                <div class="p-6 sm:p-8">

                @if ($event['description'] ?? null)
                    <p class="mb-6 text-[14px] leading-relaxed text-ink/75">{{ $event['description'] }}</p>
                @endif

                @php
                    $etat = fn ($icone, $couleur, $titre, $texte) => ['icone' => $icone, 'couleur' => $couleur, 'titre' => $titre, 'texte' => $texte];
                    $bloc = match (true) {
                        $submitted => null,
                        ($event['statut'] ?? '') === 'annule' => $etat('x-circle', 'accent', 'Événement annulé', 'Cet événement est annulé.'.(($event['motif_annulation'] ?? null) ? ' Motif : '.$event['motif_annulation'] : '')),
                        $isPastDeadline => $etat('clock', 'gris', 'Inscriptions terminées', "La date limite d'inscription est dépassée. Merci de votre intérêt — à très bientôt pour un prochain événement !"),
                        ! $isOpen => $etat('clock', 'gris', 'Inscriptions fermées', "Les inscriptions pour cet événement ne sont plus ouvertes. Merci de votre intérêt !"),
                        $isFull => $etat('users', 'accent', 'Complet', 'Toutes les places ont été réservées. Les inscriptions sont complètes — merci de votre engouement !'),
                        ! $accepts => $etat('info', 'gris', 'Inscription indisponible', $event['refus'] ?? "Les inscriptions ne sont pas ouvertes."),
                        default => null,
                    };
                @endphp

                {{-- ══════════ Confirmation + billet ══════════ --}}
                @if ($submitted)
                    <div class="flex flex-col items-center gap-3 py-2 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-[#22A85A]/10 text-[#22A85A]">
                            <x-ui.icon name="check-circle" class="size-8" />
                        </span>
                        <p class="text-lg font-bold text-brand">Inscription confirmée !</p>
                        <p class="max-w-sm text-sm text-ink/70">Merci {{ $prenom }}, votre place est réservée pour <strong>{{ $event['title'] ?? "l'événement" }}</strong>.</p>
                        @if ($billet)
                            <div class="mt-2 w-full max-w-xs rounded-2xl border border-brand/10 bg-cloud/50 p-4" x-data x-init="$nextTick(() => window.QRCode && window.QRCode.toCanvas($refs.qr, $el.dataset.code, { width: 180, margin: 1, color: { dark: '#031D59' } }))" data-code="{{ $billet }}">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#9AA6B8]">Votre billet</p>
                                <canvas x-ref="qr" class="mx-auto my-2 rounded-lg bg-white p-1"></canvas>
                                <p class="font-mono text-sm font-bold text-brand">{{ $billet }}</p>
                                <p class="mt-1 text-[11.5px] text-[#5B677A]">Présentez ce QR code à l'entrée. Faites une capture d'écran ou gardez ce lien :</p>
                                <a href="{{ url('/billet/'.$billet) }}" class="mt-1 inline-block break-all text-[12px] font-semibold text-azure hover:underline">{{ url('/billet/'.$billet) }}</a>
                            </div>
                        @endif
                        @if ($email)
                            <p class="text-[12px] text-[#9AA6B8]">Une confirmation avec votre billet a été envoyée à {{ $email }}.</p>
                        @endif
                        <a href="{{ route('adhesion') }}" class="mt-2 text-[12.5px] font-semibold text-brand hover:underline">Rejoindre le REJCC pour profiter de tout le réseau →</a>
                    </div>

                @elseif ($bloc)
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full {{ $bloc['couleur'] === 'accent' ? 'bg-accent/10 text-accent' : 'bg-[#9AA6B8]/10 text-[#5B677A]' }}">
                            <x-ui.icon :name="$bloc['icone']" class="size-7" />
                        </span>
                        <p class="text-lg font-bold text-brand">{{ $bloc['titre'] }}</p>
                        <p class="max-w-sm text-sm text-ink/70">{{ $bloc['texte'] }}</p>
                        @if ($membreConnecte && ($event['id'] ?? null))
                            <a href="{{ url('/espace-membre/evenements?evenement='.$event['id']) }}" class="mt-1 text-[12.5px] font-semibold text-brand hover:underline">Voir l'événement dans mon espace →</a>
                        @endif
                    </div>

                {{-- ══════════ Membre connecté : inscription depuis son espace ══════════ --}}
                @elseif ($membreConnecte && ($event['id'] ?? null))
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-brand/[.06] text-brand"><x-ui.icon name="calendar-days" class="size-7" /></span>
                        <p class="text-lg font-bold text-brand">Vous êtes membre</p>
                        <p class="max-w-sm text-sm text-ink/70">Inscrivez-vous depuis votre espace : votre billet sera rattaché à votre compte et vous recevrez le rappel la veille.</p>
                        <a href="{{ url('/espace-membre/evenements?evenement='.$event['id']) }}" class="btn-tap mt-1 inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-bold text-white hover:bg-accent-600">Je m'inscris depuis mon espace</a>
                    </div>

                {{-- ══════════ Formulaire d'inscription ══════════ --}}
                @else
                    @if ($remaining !== null && $remaining <= 30)
                        <p class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3.5 py-1.5 text-xs font-bold text-accent">
                            <x-ui.icon name="flame" class="size-3.5" /> Plus que {{ $remaining }} place{{ $remaining > 1 ? 's' : '' }} !
                        </p>
                    @endif

                    @if ($deadline)
                        <p class="mb-4 inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#5B677A]">
                            <x-ui.icon name="clock" class="size-3.5 text-azure" /> Inscriptions jusqu'au {{ $deadline->locale('fr')->translatedFormat('j F Y \à H\hi') }}
                        </p>
                    @endif

                    @if ($event['id'] ?? null)
                        <p class="mb-4 rounded-xl border border-brand/10 bg-cloud/60 px-3.5 py-2.5 text-[12.5px] text-ink/75">
                            Déjà membre du REJCC ? <a href="{{ url('/espace-membre/evenements?evenement='.$event['id']) }}" class="font-bold text-brand hover:underline">Connectez-vous pour vous inscrire depuis votre espace</a>.
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
                            <label class="text-xs font-semibold text-[#5B677A]">Téléphone</label>
                            <input wire:model="telephone" type="tel" inputmode="tel" placeholder="Ex : 0700000000" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                            @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-[#5B677A]">E-mail <span class="font-normal text-[#9AA6B8]">(optionnel)</span></label>
                            <input wire:model="email" type="email" placeholder="vous@exemple.com" class="rounded-xl border border-brand/15 bg-white px-3.5 py-2.5 text-sm text-brand outline-none focus:border-azure focus:ring-2 focus:ring-accent/15" />
                            @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                        </div>

                        {{-- ══════════ Champs personnalisés ══════════ --}}
                        @foreach ($fields as $f)
                            @php $key = $f['key']; $req = $f['required'] ?? false; @endphp
                            @if ($f['type'] === 'checkbox')
                                <label class="flex items-start gap-2.5 rounded-xl border border-brand/10 bg-cloud/50 px-3.5 py-3 text-sm text-ink/80">
                                    <input wire:model="answers.{{ $key }}" type="checkbox" class="mt-0.5 size-4 rounded border-brand/25 text-brand focus:ring-accent/25" />
                                    <span>{{ $f['label'] }} @if ($req) <span class="text-accent">*</span> @endif</span>
                                </label>
                                @error('answers.'.$key) <span class="-mt-2 text-xs font-medium text-accent">{{ $message }}</span> @enderror
                            @else
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-semibold text-[#5B677A]">{{ $f['label'] }} @if ($req) <span class="text-accent">*</span> @else <span class="font-normal text-[#9AA6B8]">(optionnel)</span> @endif</label>

                                    @if ($f['type'] === 'textarea')
                                        <textarea wire:model="answers.{{ $key }}" rows="3" class="{{ $inputClass }}"></textarea>
                                    @elseif ($f['type'] === 'select')
                                        <select wire:model="answers.{{ $key }}" class="{{ $inputClass }}">
                                            <option value="">— Choisir —</option>
                                            @foreach ($f['options'] ?? [] as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($f['type'] === 'file')
                                        <input wire:model="uploads.{{ $key }}" type="file" accept="image/*,application/pdf,video/*,.doc,.docx" class="text-xs text-[#5B677A] file:mr-3 file:rounded-full file:border-0 file:bg-brand/10 file:px-3.5 file:py-2 file:text-xs file:font-bold file:text-brand hover:file:bg-brand/15" />
                                        <span wire:loading wire:target="uploads.{{ $key }}" class="text-[11px] font-semibold text-azure">Envoi du fichier…</span>
                                        <span class="text-[11px] text-[#9AA6B8]">Image, PDF, Word ou vidéo — 20 Mo max.</span>
                                    @else
                                        <input wire:model="answers.{{ $key }}" type="text" class="{{ $inputClass }}" />
                                    @endif

                                    @error('answers.'.$key) <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                                    @error('uploads.'.$key) <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        @endforeach

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
        </div>
    </section>
</div>
