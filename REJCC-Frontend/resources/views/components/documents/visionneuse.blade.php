@props(['doc'])

{{-- Aperçu d'un document sans quitter la plateforme (PDF, image, vidéo,
     audio) ; les autres formats se téléchargent. Le fichier passe toujours
     par la route protégée (contrôle d'accès). --}}
@if ($doc)
    @php
        // Nom lisible en fin d'adresse : titre affiché par la visionneuse PDF du navigateur.
        $nom = (\Illuminate\Support\Str::slug($doc['title']) ?: 'document').($doc['type'] === 'PDF' ? '.pdf' : '');
        $src = route('espace-membre.documents.fichier', ['id' => $doc['id'], 'nom' => $nom]);
        $dl = route('espace-membre.documents.fichier', ['id' => $doc['id'], 'telecharger' => 1]);
        $dispo = $doc['disponible'] ?? true;
        $actions = ! $doc['verrouille'] && $dispo;
    @endphp
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/50 p-3 sm:p-6" wire:click.self="fermer" x-on:keydown.escape.window="$wire.fermer()">
        <div data-test="visionneuse" role="dialog" aria-modal="true" class="panel-enter flex h-full max-h-[94vh] w-full max-w-[1000px] flex-col overflow-hidden rounded-[18px] bg-white shadow-2xl">
            <div class="flex shrink-0 items-start gap-3 border-b border-[#EDF0F5] px-4 py-3 sm:px-5">
                <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent/10 text-accent"><x-ui.icon :name="$doc['verrouille'] ? 'lock' : 'file-text'" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <p data-test="visionneuse-titre" class="text-[15px] font-bold text-brand">{{ $doc['title'] }}</p>
                    <p class="text-[11.5px] text-[#5B677A]">{{ collect([$doc['category'], $doc['type'], $doc['taille'], $doc['fichier_maj_at'] ? 'mis à jour le '.\Illuminate\Support\Carbon::parse($doc['fichier_maj_at'])->locale('fr')->isoFormat('D MMMM YYYY') : null, $doc['contributeur'] ? 'proposé par '.$doc['contributeur'] : null])->filter()->join(' · ') }}</p>
                </div>
                @if ($actions)
                    <a href="{{ $dl }}" data-test="telecharger" @if ($doc['externe']) target="_blank" rel="noopener" @endif class="btn-tap hidden shrink-0 items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90 sm:inline-flex"><x-ui.icon name="download" class="size-3.5" /> {{ $doc['externe'] ? 'Ouvrir le lien' : 'Télécharger' }}</a>
                @endif
                <button type="button" wire:click="fermer" aria-label="Fermer" class="flex size-9 shrink-0 items-center justify-center rounded-full text-brand hover:bg-cloud"><x-ui.icon name="x" class="size-4" /></button>
            </div>

            @if ($doc['description'])
                <p class="shrink-0 border-b border-[#EDF0F5] px-4 py-2.5 text-[12.5px] leading-relaxed text-ink sm:px-5">{{ $doc['description'] }}</p>
            @endif

            <div class="relative min-h-0 flex-1 bg-[#F4F6FA]">
                @if ($doc['verrouille'])
                    <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-[#F5A623]/10 text-[#B27007]"><x-ui.icon name="lock" class="size-7" /></span>
                        <p class="max-w-sm text-[14px] font-bold text-brand">{{ $doc['raison'] }}</p>
                        @if ($doc['acces'] === 'abonnes')
                            <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap rounded-full bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-600">Activer mon abonnement</a>
                        @elseif ($doc['acces'] === 'groupe' && $doc['groupe'])
                            <a href="{{ route('espace-membre.groupes', ['rejoindre' => $doc['groupe']['id']]) }}" wire:navigate class="btn-tap rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">Rejoindre le groupe</a>
                        @endif
                    </div>
                @elseif (! $dispo)
                    <div data-test="doc-indisponible" class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-brand/[.06] text-brand"><x-ui.icon name="clock" class="size-7" /></span>
                        <p class="text-[14px] font-bold text-brand">Ce document sera bientôt disponible.</p>
                        <p class="max-w-sm text-[12.5px] text-[#5B677A]">L'équipe REJCC prépare le fichier : vous serez prévenu dès sa mise en ligne.</p>
                    </div>
                @elseif ($doc['type'] === 'PDF')
                    <iframe src="{{ $src }}#view=FitH" title="{{ $doc['title'] }}" data-test="apercu-pdf" class="h-full w-full border-0"></iframe>
                @elseif ($doc['type'] === 'Image')
                    <div class="flex h-full items-center justify-center overflow-auto p-4"><img src="{{ $src }}" alt="{{ $doc['title'] }}" class="max-h-full max-w-full rounded-lg object-contain shadow"></div>
                @elseif ($doc['type'] === 'Vidéo')
                    <div class="flex h-full items-center justify-center bg-black"><video src="{{ $src }}" controls preload="metadata" class="max-h-full max-w-full"></video></div>
                @elseif ($doc['type'] === 'Audio')
                    <div class="flex h-full items-center justify-center p-6"><audio src="{{ $src }}" controls class="w-full max-w-lg"></audio></div>
                @else
                    <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-brand/[.06] text-brand"><x-ui.icon name="file-text" class="size-7" /></span>
                        <p class="text-[14px] font-bold text-brand">{{ $doc['externe'] ? 'Ce document est un lien externe.' : 'Document '.$doc['type'].($doc['taille'] ? ' · '.$doc['taille'] : '') }}</p>
                        <p class="max-w-sm text-[12.5px] text-[#5B677A]">{{ $doc['externe'] ? 'Il s\'ouvre dans un nouvel onglet.' : "L'aperçu n'est pas disponible pour ce format : téléchargez-le pour l'ouvrir avec votre logiciel." }}</p>
                        <a href="{{ $dl }}" @if ($doc['externe']) target="_blank" rel="noopener" @endif class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90"><x-ui.icon name="download" class="size-4" /> {{ $doc['externe'] ? 'Ouvrir le lien' : 'Télécharger' }}</a>
                    </div>
                @endif
            </div>
            @if ($actions)
                <a href="{{ $dl }}" @if ($doc['externe']) target="_blank" rel="noopener" @endif class="flex shrink-0 items-center justify-center gap-1.5 border-t border-[#EDF0F5] py-3 text-[13px] font-bold text-brand sm:hidden"><x-ui.icon name="download" class="size-4" /> {{ $doc['externe'] ? 'Ouvrir le lien' : 'Télécharger' }}</a>
            @endif
        </div>
    </div>
@endif
