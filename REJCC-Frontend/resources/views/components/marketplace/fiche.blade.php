@props(['fiche', 'abonnementActif' => false, 'info' => null])

{{-- Fiche complète d'une annonce : visuel, description, prix, vendeur
     (note des membres, groupes), contact par la messagerie, partage, signalement. --}}
@if ($fiche)
    @php
        $l = $fiche;
        $v = $l['seller'] ?? [];
        $url = $l['photo'] ?? null;
        $ext = strtolower(pathinfo(parse_url((string) $url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $prix = ($l['price'] ?? null) !== null && preg_match('/^\s*\d[\d\s.]*$/', $l['price'])
            ? number_format((int) preg_replace('/\D/', '', $l['price']), 0, ',', ' ').' F'
            : ($l['price'] ?? null);
        $lien = route('espace-membre.marketplace', ['annonce' => $l['id']]);
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermerFiche" x-on:keydown.escape.window="$wire.fermerFiche()">
        <div data-test="fiche-annonce" role="dialog" aria-modal="true" class="panel-enter flex max-h-[92vh] w-full max-w-[720px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
            <div class="relative shrink-0 bg-cloud">
                @if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v'], true))
                    <video controls preload="metadata" class="max-h-[300px] w-full bg-black object-contain"><source src="{{ $url }}"></video>
                @elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true))
                    <img src="{{ $url }}" alt="{{ $l['title'] }}" class="max-h-[300px] w-full object-contain">
                @elseif ($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="flex h-28 items-center justify-center gap-2 text-[13px] font-bold text-white" style="background: linear-gradient(135deg,#0B2E7A,#AC0100)"><x-ui.icon name="video" class="size-5" /> Voir la vidéo de présentation</a>
                @else
                    <div class="flex h-24 items-center justify-center text-brand/40" style="background: linear-gradient(135deg,#031D59,#4F6FBF)"><x-ui.icon :name="$l['type'] === 'produit' ? 'shopping-bag' : 'nav-briefcase'" class="size-9 text-white/70" /></div>
                @endif
                <button type="button" wire:click="fermerFiche" aria-label="Fermer" class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-white/90 text-brand shadow hover:bg-white"><x-ui.icon name="x" class="size-4" /></button>
            </div>

            <div class="overflow-y-auto p-6">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold {{ $l['type'] === 'service' ? 'bg-azure/10 text-azure' : 'bg-[#F5A623]/10 text-[#B87A0D]' }}">{{ ucfirst($l['type']) }}</span>
                    <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $l['category'] }} · publiée {{ \Illuminate\Support\Carbon::parse($l['created_at'])->locale('fr')->diffForHumans() }}</span>
                </div>
                <h2 data-test="fiche-titre" class="text-[19px] font-extrabold leading-snug text-brand">{{ $l['title'] }}</h2>
                @if ($prix)<p data-test="fiche-prix" class="mt-1 text-[16px] font-extrabold text-accent">{{ $prix }}</p>@endif
                <p class="mt-4 whitespace-pre-line text-[13.5px] leading-relaxed text-ink">{{ $l['description'] }}</p>

                @if ($l['est_vendeur'] ?? false)
                    <div data-test="fiche-stats" class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-[12px] bg-cloud/70 p-3"><p class="text-[18px] font-extrabold text-brand">{{ $l['vues'] ?? 0 }}</p><p class="text-[11.5px] text-[#5B677A]">vue{{ ($l['vues'] ?? 0) > 1 ? 's' : '' }} par les membres</p></div>
                        <div class="rounded-[12px] bg-cloud/70 p-3"><p class="text-[18px] font-extrabold text-brand">{{ $l['contacts'] ?? 0 }}</p><p class="text-[11.5px] text-[#5B677A]">membre{{ ($l['contacts'] ?? 0) > 1 ? 's' : '' }} vous {{ ($l['contacts'] ?? 0) > 1 ? 'ont' : 'a' }} écrit</p></div>
                    </div>
                @endif

                {{-- Vendeur --}}
                <section class="mt-5 rounded-[14px] border border-brand/10 p-4">
                    <p class="mb-2.5 text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">{{ $l['type'] === 'service' ? 'Proposé par' : 'Vendu par' }}</p>
                    <div class="flex items-center gap-3">
                        <x-messagerie.avatar :personne="$v" taille="size-12" texte="text-sm" />
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-1.5 text-[14px] font-bold text-brand">{{ $v['prenom'] ?? '' }} {{ $v['nom'] ?? '' }}
                                @if (($v['role'] ?? '') === 'mentor')<span class="rounded-full bg-accent px-1.5 text-[9px] font-bold uppercase leading-4 text-white">Mentor</span>@endif
                            </p>
                            <p class="text-[12px] text-[#5B677A]">{{ collect([$v['titre'] ?? null, $v['ville'] ?? null])->filter()->join(' · ') }}</p>
                            <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">
                                @if (($v['avis']['nombre'] ?? 0) > 0)<span data-test="fiche-vendeur-note" class="font-bold text-[#B7790F]">★ {{ number_format($v['avis']['moyenne'], 1, ',', ' ') }}</span> ({{ $v['avis']['nombre'] }} avis) · @endif
                                {{ $v['annonces'] ?? 0 }} annonce{{ ($v['annonces'] ?? 0) > 1 ? 's' : '' }} en ligne
                            </p>
                        </div>
                    </div>
                    @if (! empty($v['groupes']))
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($v['groupes'] as $g)
                                <a href="{{ route('espace-membre.groupes.membres', $g['id']) }}" wire:navigate class="rounded-full bg-brand/[.06] px-2.5 py-1 text-[11.5px] font-semibold text-brand hover:bg-brand hover:text-white">{{ $g['nom'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if ($info)<p data-test="fiche-info" class="mt-4 text-[12.5px] font-semibold text-azure">{{ $info }}</p>@endif

                {{-- Actions --}}
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if ($l['est_vendeur'] ?? false)
                        <p class="rounded-[10px] bg-cloud px-4 py-2.5 text-[12.5px] font-semibold text-[#5B677A]">C'est votre annonce.</p>
                    @elseif ($abonnementActif)
                        <a href="{{ route('espace-membre.messaging', ['to' => $v['id'], 'annonce' => $l['id']]) }}" wire:navigate data-test="fiche-contacter" class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="message-circle" class="size-4" /> Contacter {{ $v['prenom'] ?? 'le vendeur' }}</a>
                        @if ($l['contact'] ?? null)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $l['contact']) }}" data-test="fiche-appeler" class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud"><x-ui.icon name="phone" class="size-4" /> {{ $l['contact'] }}</a>
                        @endif
                    @else
                        <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600"><x-ui.icon name="lock" class="size-4" /> S'abonner pour contacter {{ $v['prenom'] ?? 'le vendeur' }}</a>
                    @endif
                    <button type="button" data-test="fiche-partager" x-data="{ copie: false }"
                        x-on:click="navigator.clipboard?.writeText(@js($lien)); copie = true; setTimeout(() => copie = false, 2000)"
                        class="btn-tap inline-flex items-center gap-2 rounded-full border border-brand/15 px-4 py-2.5 text-[13px] font-bold text-brand hover:bg-cloud">
                        <x-ui.icon name="external-link" class="size-4" /> <span x-text="copie ? 'Lien copié !' : 'Partager'">Partager</span>
                    </button>
                    @unless ($l['est_vendeur'] ?? false)
                        @if ($l['deja_signalee'] ?? false)
                            <span class="ml-auto text-[11.5px] text-[#9AA6B8]">Annonce signalée</span>
                        @else
                            <button type="button" data-test="fiche-signaler"
                                x-on:click="const motif = prompt('Pourquoi signalez-vous cette annonce ? (arnaque, contenu inapproprié, hors sujet…)'); if (motif !== null) $wire.signaler(motif)"
                                class="ml-auto text-[11.5px] font-semibold text-[#9AA6B8] hover:text-accent">Signaler</button>
                        @endif
                    @endunless
                </div>
            </div>
        </div>
    </div>
@endif
