@php
    use App\Support\DemandesCom;
    $champ = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
    $niveau = $jours === null ? null : ($jours < 7 ? 'court' : ($jours < 14 ? 'juste' : 'confortable'));
@endphp
<div>
    <x-admin-light.topbar title="Mes demandes de communication" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Mes demandes de communication</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-[12.5px] text-[#5B677A]">Un visuel, une annonce, un rappel ? Adressez votre demande à la Communication ici plutôt que sur WhatsApp, <strong class="text-ink">1 à 2 semaines avant</strong> la date de publication souhaitée. Vous êtes prévenu par e-mail à chaque étape.</p>
            </div>
            <button wire:click="ouvrirFormulaire" data-test="nouvelle-demande" class="btn-tap inline-flex shrink-0 items-center gap-1.5 rounded-[10px] bg-brand px-4 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">
                <x-ui.icon name="{{ $formulaire ? 'x' : 'plus' }}" class="size-4" /> {{ $formulaire ? 'Fermer le formulaire' : 'Demander une communication' }}
            </button>
        </div>

        @if ($message)
            <p class="mb-4 rounded-[12px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-2.5 text-[13px] font-semibold text-[#2E7D5B]" data-test="message">{{ $message }}</p>
        @endif
        @if ($avertissement)
            <p class="mb-4 flex items-start gap-2 rounded-[12px] border border-[#F0C9C9] bg-[#FDEEEE] px-4 py-2.5 text-[13px] font-semibold text-[#8F0100]" data-test="avertissement-envoi"><x-ui.icon name="alert-circle" class="mt-0.5 size-4 shrink-0" /> {{ $avertissement }}</p>
        @endif
        @if ($erreur)
            <p class="mb-4 rounded-[12px] border border-[#F0C9C9] bg-[#FDF7F7] px-4 py-2.5 text-[13px] font-semibold text-accent" data-test="erreur">{{ $erreur }}</p>
        @endif

        @if ($formulaire)
            <div class="mb-8 grid gap-5 lg:grid-cols-[1fr_400px]">
                <form wire:submit="envoyer" class="panel-enter grid gap-4 self-start rounded-[18px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2 sm:p-5" data-test="formulaire-demande">
                    <p class="text-[12.5px] text-[#5B677A] sm:col-span-2">Département demandeur : <strong class="text-ink" data-test="departement-demandeur">{{ $departementLabel }}</strong></p>

                    <div class="sm:col-span-2">
                        <label for="dm-type" class="mb-1 block text-xs font-semibold text-[#5B677A]">Type d'information</label>
                        <select wire:model.live="type" id="dm-type" data-test="champ-type" class="{{ $champ }}">
                            <option value="">Choisissez…</option>
                            @foreach ($plan['types'] ?? [] as $t)
                                <option value="{{ $t['cle'] }}">{{ $t['label'] }}</option>
                            @endforeach
                        </select>
                        @error('type') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="dm-titre" class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre</label>
                        <input wire:model="titre" id="dm-titre" type="text" maxlength="150" placeholder="Ex. : Session live « Bâtir son business plan »" data-test="champ-titre" class="{{ $champ }}">
                        @error('titre') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="dm-contenu" class="mb-1 block text-xs font-semibold text-[#5B677A]">Message / contenu à faire passer</label>
                        <textarea wire:model="contenu" id="dm-contenu" rows="5" maxlength="5000" placeholder="Ce qu'il faut annoncer, les informations pratiques (lieu, heure, lien d'inscription), le public visé…" data-test="champ-contenu" class="{{ $champ }}"></textarea>
                        @error('contenu') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="dm-date" class="mb-1 block text-xs font-semibold text-[#5B677A]">Date de publication souhaitée</label>
                        <input wire:model.live="dateSouhaitee" id="dm-date" type="date" min="{{ now()->toDateString() }}" data-test="champ-date" class="{{ $champ }}">
                        @error('dateSouhaitee') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="dm-evt" class="mb-1 block text-xs font-semibold text-[#5B677A]">Date de l'événement <span class="font-normal">(s'il y en a un)</span></label>
                        <input wire:model="dateEvenement" id="dm-evt" type="date" class="{{ $champ }}">
                        @error('dateEvenement') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    @if ($niveau === 'court')
                        <p class="flex items-start gap-2 rounded-[10px] border border-[#F0C9C9] bg-[#FDEEEE] px-3 py-2.5 text-[12.5px] font-semibold text-[#8F0100] sm:col-span-2" data-test="avertissement-delai">
                            <x-ui.icon name="alert-circle" class="mt-0.5 size-4 shrink-0" />
                            <span>La Communication demande 1 à 2 semaines de délai ; votre demande risque de ne pas être traitée à temps. <span class="font-normal">Vous pouvez tout de même l'envoyer.</span></span>
                        </p>
                    @elseif ($niveau === 'juste')
                        <p class="rounded-[10px] bg-[#FDF2E3] px-3 py-2 text-[12.5px] text-[#8A5A12] sm:col-span-2" data-test="delai-juste">Délai d'une semaine environ : c'est juste, mais possible. L'idéal est de prévenir deux semaines avant.</p>
                    @elseif ($niveau === 'confortable')
                        <p class="rounded-[10px] bg-[#EFFAF4] px-3 py-2 text-[12.5px] text-[#2E7D5B] sm:col-span-2" data-test="delai-ok">Délai confortable : merci d'anticiper.</p>
                    @endif

                    <fieldset class="sm:col-span-2">
                        <legend class="mb-1 text-xs font-semibold text-[#5B677A]">Canaux souhaités <span class="font-normal">(pré-cochés d'après le plan de diffusion)</span></legend>
                        <div class="flex flex-wrap gap-2" data-test="champ-canaux">
                            @foreach ($plan['canaux'] ?? [] as $c)
                                <label wire:key="canal-{{ $c['cle'] }}" class="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-[12.5px] font-semibold transition has-[:checked]:border-azure has-[:checked]:bg-[#EEF6FD] has-[:checked]:text-[#2D5C8F] border-brand/15 text-[#5B677A]" title="{{ $c['detail'] }}">
                                    <input type="checkbox" wire:model="canaux" value="{{ $c['cle'] }}" data-test="canal-{{ $c['cle'] }}" class="size-3.5 accent-[#4F6FBF]"> {{ $c['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @error('canaux') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </fieldset>

                    <div class="sm:col-span-2">
                        <p class="mb-1 text-xs font-semibold text-[#5B677A]">Fichiers joints <span class="font-normal">(facultatif : images ou PDF, 3 au plus, 8 Mo chacun)</span></p>
                        @if ($fichiers)
                            <ul class="mb-2 space-y-1.5" data-test="fichiers-joints">
                                @foreach ($fichiers as $i => $f)
                                    <li wire:key="fj-{{ $i }}" class="flex items-center gap-2 rounded-[10px] border border-brand/10 px-3 py-1.5 text-[12.5px]">
                                        <x-ui.icon name="{{ str_starts_with($f['mime'], 'image/') ? 'image' : 'file-text' }}" class="size-4 shrink-0 text-azure" />
                                        <span class="min-w-0 flex-1 truncate font-semibold text-ink">{{ $f['nom'] }}</span>
                                        <span class="shrink-0 text-[11px] text-[#5B677A]">{{ number_format($f['octets'] / 1024, 0, ',', ' ') }} Ko</span>
                                        <button type="button" wire:click="retirerFichier({{ $i }})" aria-label="Retirer {{ $f['nom'] }}" class="shrink-0 rounded-md p-1 text-[#5B677A] hover:bg-accent/5 hover:text-accent"><x-ui.icon name="x" class="size-3.5" /></button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (count($fichiers) < 3)
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border border-dashed border-brand/20 px-3 py-3 text-[12.5px] font-semibold text-azure hover:bg-[#F3F6FC]">
                                <x-ui.icon name="plus" class="size-4" /> Ajouter des fichiers
                                <input type="file" wire:model="envois" multiple accept="image/jpeg,image/png,image/webp,application/pdf" data-test="champ-fichiers" class="hidden">
                            </label>
                        @endif
                        <span wire:loading wire:target="envois" class="mt-1 block text-[11.5px] font-semibold text-azure">Envoi du fichier…</span>
                        @error('envois') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                        @error('envois.*') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="dm-contact" class="mb-1 block text-xs font-semibold text-[#5B677A]">Contact du demandeur</label>
                        <input wire:model="contact" id="dm-contact" type="text" maxlength="150" placeholder="Nom, téléphone ou WhatsApp, e-mail" data-test="champ-contact" class="{{ $champ }}">
                        @error('contact') <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap gap-2 sm:col-span-2">
                        <button type="submit" wire:loading.attr="disabled" wire:target="envoyer,envois" data-test="envoyer-demande" class="btn-tap inline-flex items-center gap-1.5 rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60"><x-ui.icon name="send" class="size-4" /> Envoyer à la Communication</button>
                        <button type="button" wire:click="annulerFormulaire" class="rounded-[9px] border border-brand/15 px-4 py-2.5 text-sm font-semibold text-[#5B677A] hover:bg-cloud">Annuler</button>
                    </div>
                </form>

                <div class="self-start">
                    @include('livewire.admin.partials.plan-diffusion', ['plan' => $plan, 'compact' => true])
                </div>
            </div>
        @endif

        <h3 class="mb-3 text-[15px] font-bold text-ink">Suivi de mes demandes</h3>
        <div class="rounded-[18px] border border-brand/10 bg-white px-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:px-5" data-test="mes-demandes">
            @forelse ($demandes as $d)
                @php [$fg, $bg] = DemandesCom::teinte($d['statut']); @endphp
                <div wire:key="md-{{ $d['id'] }}" class="-mx-4 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:-mx-5 sm:px-5" data-test="ma-demande-{{ $d['id'] }}">
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $d['titre'] }}</p>
                        <p class="mt-0.5 text-xs text-[#5B677A]">{{ $d['type_label'] }} · souhaitée le {{ DemandesCom::date($d['date_souhaitee']) }} · envoyée le {{ DemandesCom::date($d['created_at']) }}</p>
                        @if ($d['derniere_reponse'])
                            <p class="mt-1 rounded-[8px] bg-[#F4F6F8] px-2.5 py-1.5 text-[12px] text-ink" data-test="reponse-com"><span class="font-bold text-azure">Communication :</span> {{ \Illuminate\Support\Str::limit($d['derniere_reponse']['texte'], 160) }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $fg }}; background: {{ $bg }}" data-test="statut-{{ $d['id'] }}">{{ $d['statut_label'] }}@if ($d['statut'] === 'planifiee' && $d['date_publication_prevue']) · {{ DemandesCom::date($d['date_publication_prevue'], 'j M') }}@endif</span>
                    <button wire:click="ouvrir({{ $d['id'] }})" data-test="voir-{{ $d['id'] }}" class="btn-tap shrink-0 rounded-lg border border-azure/25 px-3 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">{{ $ouverte === $d['id'] ? 'Fermer' : 'Détails' }}</button>
                    @if ($d['annulable'])
                        <button wire:click="annuler({{ $d['id'] }})" wire:confirm="Annuler cette demande ? La Communication ne la traitera pas." data-test="annuler-{{ $d['id'] }}" class="btn-tap shrink-0 rounded-lg border border-accent/20 px-3 py-1.5 text-xs font-semibold text-accent hover:bg-accent/5">Annuler</button>
                    @endif
                </div>

                @if ($ouverte === $d['id'] && $fiche)
                    <div class="panel-enter mb-4 space-y-3 rounded-xl bg-[#F8FAFC] p-4 text-[12.5px]" data-test="ma-fiche">
                        <p class="whitespace-pre-line leading-relaxed text-ink">{{ $fiche['message'] }}</p>
                        <p class="text-[#5B677A]">Canaux souhaités : <span class="text-ink">{{ implode(', ', $fiche['canaux_labels']) }}</span>@if ($fiche['date_evenement']) · Événement le {{ DemandesCom::date($fiche['date_evenement']) }}@endif · Contact : {{ $fiche['contact'] }}</p>
                        @if ($fiche['fichiers'])
                            <p class="flex flex-wrap gap-2">
                                @foreach ($fiche['fichiers'] as $f)
                                    <a href="{{ route('admin.demandes-communication.fichier', ['id' => $fiche['id'], 'n' => $f['index']]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full border border-brand/10 bg-white px-3 py-1 font-semibold text-azure hover:bg-cloud"><x-ui.icon name="{{ $f['image'] ? 'image' : 'file-text' }}" class="size-3.5" /> {{ $f['nom'] }}</a>
                                @endforeach
                            </p>
                        @endif
                        @if ($fiche['lien_publication'])
                            <a href="{{ $fiche['lien_publication'] }}" target="_blank" rel="noopener noreferrer" class="inline-block font-semibold text-azure hover:underline">Voir la publication</a>
                        @endif
                        <ol class="space-y-1.5 border-t border-[#EDF0F5] pt-3" data-test="mon-historique">
                            @foreach (array_reverse($fiche['historique']) as $h)
                                <li>
                                    <span class="font-semibold text-ink">{{ ($h['type'] ?? '') === 'statut' && ($h['statut'] ?? '') === 'nouvelle' ? 'Demande envoyée' : $h['label'] }}</span>
                                    <span class="text-[#5B677A]">· {{ DemandesCom::date($h['at'] ?? null, 'j M Y, H\hi') }}@if (! empty($h['par'])) · {{ $h['par'] }}@endif</span>
                                    @if (! empty($h['texte']))<span class="mt-0.5 block whitespace-pre-line pl-3 text-[#3D4A60]">« {{ $h['texte'] }} »</span>@endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            @empty
                <div class="px-2 py-12 text-center">
                    <x-ui.illustration name="megaphone" size="size-14" class="mx-auto" />
                    <p class="mt-3 text-[13px] font-semibold text-brand">Vous n'avez encore adressé aucune demande.</p>
                    <p class="mt-1 text-[12px] text-[#5B677A]">Cliquez sur « Demander une communication » pour commencer.</p>
                </div>
            @endforelse
        </div>

        @unless ($formulaire)
            <div class="mt-8">@include('livewire.admin.partials.plan-diffusion', ['plan' => $plan, 'replie' => true])</div>
        @endunless
    </div>
</div>
