<div>
    <x-member-light.topbar title="Catalogue des formations" />

    <div class="mx-auto max-w-[1000px] px-4 py-6 sm:px-8 sm:py-8">
        <a href="{{ route('espace-membre.catalogue') }}" wire:navigate class="mb-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
            <x-ui.icon name="arrow-left" class="size-3.5" /> Catalogue
        </a>

        @if (! $f)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Cette formation n'est pas disponible.</p>
        @else
            {{-- En-tête --}}
            <header data-test="fiche-entete" class="overflow-hidden rounded-[20px] text-white shadow-[0_12px_28px_rgba(3,29,89,.18)]" style="background: linear-gradient(120deg, rgba(3,29,89,.75), rgba(3,29,89,.25)), {{ $f['image_url'] ? 'url('.e($f['image_url']).') center/cover' : 'linear-gradient(120deg, '.$palette['from'].', '.$palette['to'].')' }}">
                <div class="p-6 sm:p-8">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-bold">{{ $f['category'] }}</span>
                        @if ($f['certifiante'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-bold"><x-ui.icon name="award" class="size-3" /> Certifiante</span>
                        @endif
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-bold">{{ $f['is_free'] ? 'Gratuite' : ($accesLibre ? 'Accès libre' : 'Incluse dans l\'abonnement') }}</span>
                    </div>
                    <h1 class="text-[24px] font-extrabold leading-tight sm:text-[28px]">{{ $f['title'] }}</h1>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-[13px] text-white/85">
                        @if ($f['duration']) <span class="inline-flex items-center gap-1.5"><x-ui.icon name="clock" class="size-4" /> {{ $f['duration'] }}</span> @endif
                        @if ($f['level']) <span class="inline-flex items-center gap-1.5"><x-ui.icon name="target" class="size-4" /> {{ $f['level'] }}</span> @endif
                        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="book-open" class="size-4" /> {{ count($f['programme']) }} module{{ count($f['programme']) > 1 ? 's' : '' }}</span>
                        <span class="inline-flex items-center gap-1.5" data-test="fiche-inscrits"><x-ui.icon name="users" class="size-4" /> {{ $f['inscrits'] }} membre{{ $f['inscrits'] > 1 ? 's' : '' }} inscrit{{ $f['inscrits'] > 1 ? 's' : '' }}</span>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        @if ($f['completed'])
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-[13px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-4" /> Formation terminée</span>
                            @if ($f['certifiante'])
                                <a href="{{ route('espace-membre.certificats') }}" wire:navigate class="rounded-full border border-white/40 px-4 py-2 text-[13px] font-bold text-white hover:bg-white/10">Voir mon certificat</a>
                            @endif
                        @elseif ($f['enrolled'])
                            <a href="{{ route('espace-membre.formations.detail', $f['id']) }}" wire:navigate data-test="fiche-continuer" class="btn-tap inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-[13px] font-bold text-brand">Continuer la formation ({{ (int) $f['progress'] }} %) <x-ui.icon name="arrow-right" class="size-4" /></a>
                        @elseif (count($f['programme']))
                            <button wire:click="inscrire" wire:loading.attr="disabled" data-test="fiche-inscrire" class="btn-tap inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-[13px] font-bold text-brand disabled:opacity-60">S'inscrire et commencer <x-ui.icon name="arrow-right" class="size-4" /></button>
                        @else
                            <button wire:click="inscrire" wire:loading.attr="disabled" class="btn-tap rounded-full bg-white px-5 py-2.5 text-[13px] font-bold text-brand disabled:opacity-60">S'inscrire</button>
                            <span class="text-[12px] text-white/80">Contenu en préparation : vous serez prêt dès sa mise en ligne.</span>
                        @endif
                    </div>
                </div>
            </header>

            @if ($erreur)
                <div data-test="erreur-inscription" class="mt-4 flex flex-wrap items-center gap-3 rounded-[14px] border border-[#F5A623]/40 bg-[#FFF8EC] px-4 py-3 text-[13px] text-brand">
                    <x-ui.icon name="shield" class="size-4 text-[#B97400]" /> <span class="flex-1">{{ $erreur }}</span>
                    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="font-bold text-accent hover:underline">Activer mon abonnement</a>
                </div>
            @endif

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1.6fr_1fr]">
                <div class="flex min-w-0 flex-col gap-6">
                    @if ($f['description'])
                        <section class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]" data-test="fiche-description">
                            <h2 class="text-[15px] font-extrabold text-brand">Présentation</h2>
                            <div class="mb-3 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                            <p class="whitespace-pre-line text-[14px] leading-relaxed text-ink/85">{{ $f['description'] }}</p>
                        </section>
                    @endif

                    <section class="rounded-[18px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]" data-test="fiche-programme">
                        <h2 class="text-[15px] font-extrabold text-brand">Programme</h2>
                        <div class="mb-4 mt-1 h-[3px] w-9 rounded bg-accent"></div>
                        @forelse ($f['programme'] as $i => $m)
                            <div class="flex gap-3 border-t border-cloud-200 py-3 first:border-t-0 first:pt-0">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-azure/10 text-[12px] font-bold text-azure">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13.5px] font-bold text-brand">{{ $m['titre'] }}</p>
                                    @if ($m['description'])
                                        <p class="mt-0.5 text-[12.5px] text-[#5B677A]">{{ $m['description'] }}</p>
                                    @endif
                                    <p class="mt-1 flex flex-wrap gap-x-3 text-[11.5px] text-[#9AA6B8]">
                                        @if ($m['duree']) <span>{{ $m['duree'] }}</span> @endif
                                        @if ($m['quiz']) <span class="font-semibold text-azure">Quiz de validation</span> @endif
                                        @if ($m['ressources']) <span>{{ $m['ressources'] }} ressource{{ $m['ressources'] > 1 ? 's' : '' }}</span> @endif
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-[13px] text-[#5B677A]">Le contenu de cette formation est en cours de préparation par le REJCC.</p>
                        @endforelse
                    </section>

                    @if ($f['support'])
                        <section class="overflow-hidden rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]" data-test="fiche-support">
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-cloud/60 px-5 py-3">
                                <p class="inline-flex items-center gap-1.5 text-[13px] font-bold text-brand"><x-ui.icon name="file-text" class="size-4" /> {{ $f['support']['nom'] }}</p>
                                @if ($f['support']['telechargeable'] && $f['support']['url'])
                                    <a href="{{ $f['support']['url'] }}" download class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3 py-1 text-[11.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-3.5" /> Télécharger</a>
                                @else
                                    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#8A5A00]"><x-ui.icon name="shield" class="size-3.5" /> Téléchargement réservé aux abonnés</a>
                                @endif
                            </div>
                            @if ($f['support']['pdf'] && $f['support']['url'])
                                <iframe src="{{ $f['support']['url'] }}#toolbar={{ $f['support']['telechargeable'] ? 1 : 0 }}&navpanes=0" title="{{ $f['support']['nom'] }}" class="h-[60vh] w-full bg-white"></iframe>
                            @elseif (! $f['support']['url'])
                                <p class="px-5 py-3 text-[12.5px] text-[#5B677A]">Ce document est disponible au téléchargement pour les membres abonnés.</p>
                            @endif
                        </section>
                    @endif
                </div>

                <aside class="flex min-w-0 flex-col gap-4">
                    <section class="rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]" data-test="fiche-evaluation">
                        <p class="mb-3 text-[13px] font-extrabold text-brand">Comment se passe la formation ?</p>
                        <ul class="flex flex-col gap-2.5 text-[12.5px] text-[#5B677A]">
                            <li class="flex gap-2"><x-ui.icon name="play" class="mt-0.5 size-4 shrink-0 text-azure" /> Tout se suit sur la plateforme : vidéos, leçons et supports, module après module.</li>
                            @if (collect($f['programme'])->contains('quiz', true))
                                <li class="flex gap-2"><x-ui.icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-azure" /> Certains modules se valident par un quiz ({{ $f['seuil_reussite'] ?? 70 }} % de bonnes réponses).</li>
                            @endif
                            @if ($f['examen'])
                                <li class="flex gap-2"><x-ui.icon name="award" class="mt-0.5 size-4 shrink-0 text-[#B27007]" /> Examen final de {{ $f['examen']['nb_questions'] }} questions, à réussir à {{ $f['examen']['seuil'] }} %.</li>
                            @endif
                            @if ($f['certifiante'])
                                <li class="flex gap-2"><x-ui.icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-[#22A85A]" /> Certificat REJCC délivré à la réussite, visible sur votre page publique.</li>
                            @endif
                            <li class="flex gap-2"><x-ui.icon name="download" class="mt-0.5 size-4 shrink-0 text-azure" /> Ressources téléchargeables réservées aux membres abonnés.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        @endif
    </div>
</div>
