<div>
    <x-member-light.topbar title="Formation" />

    <div class="mx-auto max-w-[900px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-2">
            <a href="{{ route('espace-membre.formations') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#9AA6B8] hover:text-brand">
                <x-ui.icon name="arrow-left" class="size-3.5" /> Mes formations
            </a>
        </div>

        @if (! $ok)
            <p class="mt-6 rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Vous n'êtes pas inscrit à cette formation.</p>
        @else
            <div class="mb-5">
                <h1 class="mb-1 text-[17px] font-bold text-brand">{{ $formation['title'] }}</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                @if ($formation['description'])
                    <p class="mt-3 max-w-2xl text-[13px] text-[#5B677A]">{{ $formation['description'] }}</p>
                @endif
            </div>

            @if ($message)
                <p class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
            @endif

            <div class="mb-6 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <div class="mb-1.5 flex items-center justify-between">
                    <p class="text-[12px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Progression</p>
                    <p class="text-[12px] font-bold text-brand">{{ $progress }}%</p>
                </div>
                <div class="h-2 w-full rounded-full bg-cloud">
                    <div class="h-2 rounded-full {{ $completed ? 'bg-[#22A85A]' : 'bg-azure' }}" style="width: {{ $progress }}%"></div>
                </div>
                @if ($completed)
                    <p class="mt-3 inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#22A85A]"><x-ui.icon name="award" class="size-4" /> Formation terminée !
                        @if ($formation['is_certifying'])
                            <a href="{{ route('espace-membre.certificats') }}" wire:navigate data-test="lien-certificat" class="ml-1 underline">Voir mon certificat</a>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($modules as $m)
                    <div class="overflow-hidden rounded-[16px] border bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $m['verrouille'] ? 'border-brand/10 opacity-60' : 'border-brand/10' }}">
                        <button
                            type="button"
                            wire:click="{{ $m['verrouille'] ? '' : "toggleModule({$m['id']})" }}"
                            {{ $m['verrouille'] ? 'disabled' : '' }}
                            class="flex w-full items-center gap-3 p-4 text-left {{ $m['verrouille'] ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-cloud/40' }}"
                        >
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $m['termine'] ? 'bg-[#22A85A]/10 text-[#22A85A]' : ($m['verrouille'] ? 'bg-cloud text-[#9AA6B8]' : 'bg-azure/10 text-azure') }}">
                                <x-ui.icon :name="$m['termine'] ? 'check' : ($m['verrouille'] ? 'shield' : 'play')" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-bold text-brand">{{ $m['titre'] }}</p>
                                @if ($m['duree'])
                                    <p class="mt-0.5 text-[11.5px] text-[#9AA6B8]">{{ $m['duree'] }}</p>
                                @endif
                            </div>
                            @if (! $m['verrouille'])
                                <x-ui.icon name="chevron-right" class="size-4 shrink-0 text-[#9AA6B8]" />
                            @endif
                        </button>

                        @if ($moduleOuvert === $m['id'])
                            @php $video = \App\Support\VideoEmbed::from($m['video_url']); $estPdf = $m['document_url'] && preg_match('~\.pdf(\?.*)?$~i', $m['document_url']); @endphp
                            <div class="panel-enter border-t border-cloud-200 p-4 sm:p-5" data-test="module-ouvert">
                                @if ($m['description'])
                                    <p class="mb-4 text-[13px] leading-relaxed text-[#5B677A]">{{ $m['description'] }}</p>
                                @endif

                                {{-- Vidéo : lue directement sur la plateforme --}}
                                @if ($video && $video['type'] === 'iframe')
                                    <div class="mb-4 aspect-video overflow-hidden rounded-xl bg-black" data-test="video-integree">
                                        <iframe src="{{ $video['src'] }}" title="Vidéo — {{ $m['titre'] }}" class="size-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                    </div>
                                @elseif ($video && $video['type'] === 'fichier')
                                    <video controls preload="metadata" controlsList="{{ $telechargementAutorise ? '' : 'nodownload' }}" class="mb-4 aspect-video w-full rounded-xl bg-black" data-test="video-integree">
                                        <source src="{{ $video['src'] }}">
                                    </video>
                                @elseif ($video)
                                    <a href="{{ $video['src'] }}" target="_blank" rel="noopener" class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-[12px] font-semibold text-azure hover:bg-azure/20">
                                        <x-ui.icon name="play" class="size-3.5" /> Voir la vidéo
                                    </a>
                                @endif

                                {{-- Leçon rédigée --}}
                                @if ($m['contenu'])
                                    <div class="lecon mb-4" data-test="lecon">{!! \Illuminate\Support\Str::markdown($m['contenu'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                @endif

                                {{-- Document : consultable sur la plateforme --}}
                                @if ($m['document_url'])
                                    <div class="mb-4 overflow-hidden rounded-xl border border-brand/10">
                                        <div class="flex flex-wrap items-center justify-between gap-2 bg-cloud/60 px-3.5 py-2.5">
                                            <p class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-brand"><x-ui.icon name="file-text" class="size-4" /> Support du module</p>
                                            @if ($telechargementAutorise)
                                                <a href="{{ $m['document_url'] }}" download data-test="telecharger-document" class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3 py-1 text-[11.5px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-3.5" /> Télécharger</a>
                                            @else
                                                <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#8A5A00]"><x-ui.icon name="shield" class="size-3.5" /> Téléchargement réservé aux abonnés</a>
                                            @endif
                                        </div>
                                        @if ($estPdf)
                                            <iframe src="{{ $m['document_url'] }}#toolbar={{ $telechargementAutorise ? 1 : 0 }}&navpanes=0" title="Document — {{ $m['titre'] }}" class="h-[70vh] w-full bg-white" data-test="document-integre"></iframe>
                                        @elseif (! $telechargementAutorise)
                                            <p class="px-3.5 py-3 text-[12px] text-[#5B677A]">Ce document (format non consultable en ligne) est disponible au téléchargement pour les membres abonnés.</p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Ressources à télécharger --}}
                                @if (count($m['ressources']))
                                    <div class="mb-4" data-test="ressources">
                                        <p class="mb-2 text-[12px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">Ressources</p>
                                        <ul class="flex flex-col gap-2">
                                            @foreach ($m['ressources'] as $r)
                                                <li class="flex items-center gap-3 rounded-xl border border-brand/10 px-3.5 py-2.5">
                                                    <x-ui.icon name="folder-open" class="size-4 shrink-0 text-accent" />
                                                    <span class="min-w-0 flex-1 truncate text-[12.5px] font-semibold text-brand">{{ $r['nom'] }}@if ($r['taille']) <span class="font-normal text-[#9AA6B8]">· {{ $r['taille'] }}</span>@endif</span>
                                                    @if ($r['url'])
                                                        <a href="{{ $r['url'] }}" download class="inline-flex shrink-0 items-center gap-1 text-[12px] font-bold text-azure hover:underline"><x-ui.icon name="download" class="size-3.5" /> Télécharger</a>
                                                    @else
                                                        <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="inline-flex shrink-0 items-center gap-1 text-[11.5px] font-semibold text-[#8A5A00]"><x-ui.icon name="shield" class="size-3.5" /> Réservé aux abonnés</a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (! $m['termine'] && $m['quiz_requis'])
                                    {{-- Quiz de validation : corrigé par le serveur --}}
                                    <form wire:submit="validerModule({{ $m['id'] }})" class="rounded-xl border border-azure/25 bg-azure/[.04] p-4" data-test="quiz">
                                        <p class="text-[13px] font-bold text-brand">Quiz de validation</p>
                                        <p class="mb-3 text-[11.5px] text-[#5B677A]">{{ count($m['quiz']) }} question(s) — il faut au moins {{ $formation['seuil_reussite'] ?? 70 }} % de bonnes réponses pour valider le module.</p>
                                        <ol class="flex flex-col gap-3.5">
                                            @foreach ($m['quiz'] as $qi => $q)
                                                <li wire:key="q-{{ $m['id'] }}-{{ $qi }}">
                                                    <p class="mb-1.5 text-[13px] font-semibold text-ink">{{ $qi + 1 }}. {{ $q['question'] }}</p>
                                                    <div class="flex flex-col gap-1.5">
                                                        @foreach ($q['choix'] as $ci => $choix)
                                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-brand/10 bg-white px-3 py-2 text-[12.5px] text-ink hover:border-azure/40 has-[:checked]:border-azure has-[:checked]:bg-azure/10">
                                                                <input type="radio" wire:model="reponses.{{ $m['id'] }}.{{ $qi }}" name="q-{{ $m['id'] }}-{{ $qi }}" value="{{ $ci }}" class="accent-[#4F6FBF]">
                                                                {{ $choix }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                        @if ($erreur)
                                            <p class="mt-3 rounded-lg bg-[#F9E9E9] px-3 py-2 text-[12.5px] font-semibold text-accent" data-test="quiz-echec">{{ $erreur }}</p>
                                        @endif
                                        <button type="submit" wire:loading.attr="disabled" data-test="valider-quiz" class="btn-tap mt-3 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Valider mes réponses</button>
                                    </form>
                                @elseif (! $m['termine'])
                                    @if ($erreur)
                                        <p class="mb-2 text-[12.5px] font-semibold text-accent">{{ $erreur }}</p>
                                    @endif
                                    <button wire:click="validerModule({{ $m['id'] }})" wire:loading.attr="disabled" class="btn-tap rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Marquer ce module comme terminé</button>
                                @elseif ($m['quiz_requis'])
                                    <p class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-4" /> Quiz réussi — module validé</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Examen final de certification --}}
            @if ($examen)
                <section id="examen" data-test="examen" class="mt-6 rounded-[16px] border p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] {{ $examen['reussi'] ? 'border-[#22A85A]/30 bg-[#F2FBF5]' : 'border-brand/10 bg-white' }}">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $examen['reussi'] ? 'bg-[#22A85A]/15 text-[#1C8F4C]' : 'bg-[#F5A623]/15 text-[#B27007]' }}"><x-ui.icon name="award" class="size-5" /></span>
                        <div class="min-w-[200px] flex-1">
                            <p class="text-[14px] font-bold text-brand">Examen final{{ $formation['is_certifying'] ? ' de certification' : '' }}</p>
                            <p class="text-[12px] text-[#5B677A]">
                                @if ($examen['reussi'])
                                    Réussi avec {{ $examen['score'] }} %{{ $formation['is_certifying'] ? ' — certificat délivré.' : '.' }}
                                @elseif (! $examen['disponible'])
                                    {{ $examen['nb_questions'] }} questions · {{ $examen['seuil'] }} % requis. Accessible une fois tous les modules validés.
                                @elseif ($examen['bloque_jusqu'])
                                    Nouvel essai possible le {{ \Carbon\Carbon::parse($examen['bloque_jusqu'])->translatedFormat('j F à H\hi') }}.
                                @else
                                    {{ $examen['nb_questions'] }} questions · {{ $examen['seuil'] }} % de bonnes réponses requis{{ $formation['is_certifying'] ? ' pour obtenir le certificat' : '' }}. 3 essais, puis pause de 24 h.
                                @endif
                            </p>
                        </div>
                        @if ($examen['reussi'] && $formation['is_certifying'])
                            <a href="{{ route('espace-membre.certificats') }}" wire:navigate class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-[#22A85A] px-4 py-2 text-[12.5px] font-bold text-white">Voir mon certificat</a>
                        @elseif ($examen['disponible'] && ! $examen['bloque_jusqu'] && ! $examenOuvert)
                            <button wire:click="ouvrirExamen" data-test="ouvrir-examen" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Passer l'examen</button>
                        @endif
                    </div>

                    @if ($resultatExamen && ! $resultatExamen['reussi'] && $resultatExamen['message'])
                        <p data-test="examen-echec" class="mt-3 rounded-lg bg-[#F9E9E9] px-3 py-2 text-[12.5px] font-semibold text-accent">{{ $resultatExamen['message'] }}</p>
                    @endif

                    @if ($examenOuvert)
                        <form wire:submit="passerExamen" class="mt-4 border-t border-cloud-200 pt-4" data-test="formulaire-examen">
                            <ol class="flex flex-col gap-4">
                                @foreach ($examenQuestions as $qi => $q)
                                    <li wire:key="ex-{{ $qi }}">
                                        <p class="mb-1.5 text-[13px] font-semibold text-ink">{{ $qi + 1 }}. {{ $q['question'] }}</p>
                                        <div class="flex flex-col gap-1.5">
                                            @foreach ($q['choix'] as $ci => $choix)
                                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-brand/10 bg-white px-3 py-2 text-[12.5px] text-ink hover:border-azure/40 has-[:checked]:border-azure has-[:checked]:bg-azure/10">
                                                    <input type="radio" wire:model="reponsesExamen.{{ $qi }}" name="ex-{{ $qi }}" value="{{ $ci }}" class="accent-[#4F6FBF]">
                                                    {{ $choix }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                            <button type="submit" wire:loading.attr="disabled" wire:confirm="Valider vos réponses à l'examen ?" data-test="valider-examen" class="btn-tap mt-4 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60">Envoyer mes réponses</button>
                        </form>
                    @endif
                </section>
            @endif
        @endif
    </div>
</div>
