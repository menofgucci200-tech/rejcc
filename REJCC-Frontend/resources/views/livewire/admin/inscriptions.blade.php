<div>
    <x-admin-light.topbar title="Inscriptions" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Inscriptions aux événements</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Générez un QR code par événement : les participants scannent et s'inscrivent en ligne. Suivez le nombre d'inscrits, fermez les inscriptions si les places sont limitées, et exportez la liste des participants.</p>
            </div>
            <button wire:click="openCreate" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Créer un événement</button>
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        {{-- ══════════ Formulaire création / édition ══════════ --}}
        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier l\'événement' : 'Nouvel événement' }}</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Titre de l'événement</label>
                    <input wire:model="title" type="text" placeholder="Ex : Lancement officiel du REJCC" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date &amp; heure</label>
                    <input wire:model="date" type="datetime-local" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('date') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Lieu</label>
                    <input wire:model="location" type="text" placeholder="Ex : Abidjan, Cocody" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date limite d'inscription (optionnel)</label>
                    <input wire:model="deadline" type="datetime-local" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('deadline') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                    <p class="mt-1 text-[11px] text-[#9AA6B8]">Passé cette date, les inscriptions se ferment automatiquement.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description (optionnel)</label>
                    <textarea wire:model="description" rows="2" placeholder="Programme, informations pratiques…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nombre de places (optionnel)</label>
                    <input wire:model="capacity" type="number" min="1" placeholder="Laisser vide = illimité" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('capacity') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <label class="flex items-center gap-2.5 self-end rounded-[9px] border border-brand/10 bg-cloud/50 px-3 py-2.5 text-sm text-ink/80">
                    <input wire:model="is_open" type="checkbox" class="size-4 rounded border-brand/25 text-brand focus:ring-accent/25" />
                    Inscriptions ouvertes
                </label>

                {{-- Affiche de l'événement --}}
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Affiche de l'événement (optionnel)" hint="Image affichée en haut du formulaire d'inscription (JPG, PNG). Uploadez un fichier ou collez un lien." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                    @error('mediaFile') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>

                {{-- Constructeur de champs personnalisés --}}
                <div class="sm:col-span-2">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[13px] font-bold text-brand">Questions personnalisées</p>
                        <button type="button" wire:click="addField" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-3 py-1.5 text-[11.5px] font-bold text-brand hover:bg-cloud">
                            <x-ui.icon name="plus" class="size-3.5" /> Ajouter un champ
                        </button>
                    </div>
                    <p class="mb-3 text-[11px] text-[#9AA6B8]">Ajoutez les informations à demander aux participants : domaine de formation, statut social, upload d'un document, etc.</p>

                    @forelse ($fields as $i => $f)
                        <div class="mb-2.5 rounded-[12px] border border-brand/10 bg-cloud/40 p-3" wire:key="field-{{ $i }}">
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[1fr_170px_auto]">
                                <input wire:model="fields.{{ $i }}.label" type="text" placeholder="Intitulé (ex : Domaine de formation)" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                <select wire:model.live="fields.{{ $i }}.type" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure">
                                    <option value="text">Texte court</option>
                                    <option value="textarea">Paragraphe</option>
                                    <option value="select">Liste déroulante</option>
                                    <option value="checkbox">Case à cocher</option>
                                    <option value="file">Fichier à uploader</option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <label class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#5B677A]">
                                        <input wire:model="fields.{{ $i }}.required" type="checkbox" class="size-3.5 rounded border-brand/25 text-brand" /> Requis
                                    </label>
                                    <button type="button" wire:click="removeField({{ $i }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer ce champ"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                                </div>
                            </div>
                            @if (($f['type'] ?? '') === 'select')
                                <textarea wire:model="fields.{{ $i }}.options" rows="3" placeholder="Une option par ligne (ex : Étudiant / Salarié / Entrepreneur…)" class="mt-2.5 w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                            @elseif (($f['type'] ?? '') === 'file')
                                <p class="mt-2 text-[11px] text-[#9AA6B8]">Le participant pourra uploader une image, une vidéo ou un PDF (20 Mo max).</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-[10px] border border-dashed border-brand/15 py-4 text-center text-[12px] text-[#9AA6B8]">Aucune question personnalisée. Le formulaire ne demandera que nom, téléphone et e-mail.</p>
                    @endforelse
                </div>

                <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Enregistrer</button>
            </div>
        @endif

        {{-- ══════════ Liste des événements ══════════ --}}
        <div class="space-y-4">
            @forelse ($events as $e)
                <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]" wire:key="ev-{{ $e['id'] }}">
                    <div class="flex flex-wrap items-center gap-4 p-5">
                        <div class="min-w-[220px] flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[15px] font-bold text-brand">{{ $e['title'] }}</p>
                                @if ($e['is_past_deadline'] ?? false)
                                    <span class="rounded-full bg-[#9AA6B8]/15 px-2.5 py-0.5 text-[10.5px] font-bold text-[#5B677A]">Clôturé (date limite)</span>
                                @elseif ($e['is_open'] && ! $e['is_full'])
                                    <span class="rounded-full bg-[#22A85A]/10 px-2.5 py-0.5 text-[10.5px] font-bold text-[#1C8F4C]">Ouvert</span>
                                @elseif ($e['is_full'])
                                    <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-[10.5px] font-bold text-accent">Complet</span>
                                @else
                                    <span class="rounded-full bg-[#9AA6B8]/15 px-2.5 py-0.5 text-[10.5px] font-bold text-[#5B677A]">Fermé</span>
                                @endif
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] font-semibold text-[#5B677A]">
                                @if ($e['date_label']) <span class="inline-flex items-center gap-1"><x-ui.icon name="calendar" class="size-3 text-azure" /> {{ $e['date_label'] }}</span> @endif
                                @if ($e['location']) <span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3 text-azure" /> {{ $e['location'] }}</span> @endif
                                @if ($e['deadline_label']) <span class="inline-flex items-center gap-1 {{ ($e['is_past_deadline'] ?? false) ? 'text-accent' : '' }}"><x-ui.icon name="clock" class="size-3" /> Limite : {{ $e['deadline_label'] }}</span> @endif
                            </p>
                        </div>

                        {{-- Compteur inscrits --}}
                        <div class="w-full sm:w-52">
                            <div class="mb-1 flex items-baseline justify-between">
                                <span class="text-[20px] font-extrabold text-brand">{{ $e['count'] }}</span>
                                <span class="text-[11px] font-semibold text-[#9AA6B8]">{{ $e['capacity'] ? 'sur '.$e['capacity'].' places' : 'inscrit'.($e['count'] > 1 ? 's' : '') }}</span>
                            </div>
                            @if ($e['percent'] !== null)
                                <div class="h-2 overflow-hidden rounded-full bg-cloud">
                                    <div class="h-full rounded-full transition-all" style="width: {{ $e['percent'] }}%; background: {{ $e['is_full'] ? '#AC0100' : 'linear-gradient(90deg,#4F6FBF,#22A85A)' }}"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-1.5 border-t border-[#EDF0F5] px-5 py-2.5">
                        <button wire:click="openDetail({{ $e['id'] }}, 'qr')" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold {{ $detailId === $e['id'] && $detailTab === 'qr' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                            <x-ui.icon name="qr-code" class="size-3.5" /> QR &amp; lien
                        </button>
                        <button wire:click="openDetail({{ $e['id'] }}, 'participants')" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold {{ $detailId === $e['id'] && $detailTab === 'participants' ? 'bg-brand text-white' : 'text-brand hover:bg-cloud' }}">
                            <x-ui.icon name="users" class="size-3.5" /> Participants ({{ $e['count'] }})
                        </button>
                        <button wire:click="toggle({{ $e['id'] }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-[#5B677A] hover:bg-cloud">
                            <x-ui.icon name="{{ $e['is_open'] ? 'x-circle' : 'check-circle' }}" class="size-3.5" /> {{ $e['is_open'] ? 'Fermer les inscriptions' : 'Rouvrir' }}
                        </button>
                        <div class="ml-auto flex items-center gap-1.5">
                            <button wire:click="openEdit({{ $e['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $e['id'] }})" wire:confirm="Supprimer « {{ $e['title'] }} » et tous ses inscrits ? Action irréversible." class="icon-btn rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>

                    {{-- ══════════ Panneau détail ══════════ --}}
                    @if ($detailId === $e['id'])
                        <div class="panel-enter border-t border-[#EDF0F5] bg-[#F8FAFC] p-5">
                            {{-- Onglet QR & lien --}}
                            @if ($detailTab === 'qr')
                                <div class="flex flex-col gap-6 sm:flex-row sm:items-center" wire:key="qr-{{ $e['id'] }}"
                                     x-data="{
                                        url: @js($e['url']),
                                        copied: false,
                                        download() {
                                            const c = $refs.qr; if (!c) return;
                                            const a = document.createElement('a');
                                            a.href = c.toDataURL('image/png');
                                            a.download = 'qr-{{ $e['slug'] }}.png';
                                            a.click();
                                        },
                                        copy() { navigator.clipboard.writeText(this.url); this.copied = true; setTimeout(() => this.copied = false, 1800); }
                                     }">
                                    <div wire:ignore class="shrink-0 self-center rounded-2xl border border-brand/10 bg-white p-3 shadow-sm">
                                        <canvas x-init="window.QRCode && window.QRCode.toCanvas($refs.qr, url, { width: 190, margin: 1, color: { dark: '#031D59' } })" x-ref="qr"></canvas>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] font-bold text-brand">Lien public d'inscription</p>
                                        <p class="mt-1 break-all rounded-lg border border-brand/10 bg-white px-3 py-2 text-[12.5px] text-azure">{{ $e['url'] }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button" @click="download()" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                                                <x-ui.icon name="download" class="size-3.5" /> Télécharger le QR (PNG)
                                            </button>
                                            <button type="button" @click="copy()" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                                <span x-show="!copied" class="inline-flex items-center gap-1.5"><x-ui.icon name="external-link" class="size-3.5" /> Copier le lien</span>
                                                <span x-show="copied" x-cloak class="text-[#1C8F4C]">✓ Lien copié</span>
                                            </button>
                                            <a href="{{ $e['url'] }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                                <x-ui.icon name="eye" class="size-3.5" /> Aperçu
                                            </a>
                                        </div>
                                        <p class="mt-3 text-[11px] text-[#9AA6B8]">Imprimez ce QR sur vos affiches et flyers. En le scannant, chacun accède au formulaire d'inscription.</p>
                                    </div>
                                </div>

                            {{-- Onglet Participants --}}
                            @else
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <input wire:model.live.debounce.400ms="q" type="search" placeholder="Rechercher un participant…" class="w-56 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure" />
                                    <a href="{{ route('admin.export', ['dataset' => 'participants', 'event' => $e['id']]) }}" class="btn-tap ml-auto inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                                        <x-ui.icon name="download" class="size-3.5" /> Exporter (CSV/Excel)
                                    </a>
                                </div>

                                @if ($participants->isEmpty())
                                    <p class="rounded-[12px] border border-brand/10 bg-white py-8 text-center text-sm text-[#5B677A]">{{ trim($q) !== '' ? 'Aucun participant ne correspond à cette recherche.' : 'Aucun inscrit pour le moment.' }}</p>
                                @else
                                    <div class="overflow-x-auto rounded-[12px] border border-brand/10 bg-white">
                                        <table class="w-full min-w-[560px] text-left">
                                            <thead>
                                                <tr class="border-b border-[#EDF0F5] text-[10.5px] font-bold uppercase tracking-[0.06em] text-[#9AA6B8]">
                                                    <th class="px-4 py-2.5">Nom et prénom</th>
                                                    <th class="px-3 py-2.5">Téléphone</th>
                                                    <th class="px-3 py-2.5">Email</th>
                                                    <th class="px-3 py-2.5">Membre</th>
                                                    <th class="px-4 py-2.5">Inscrit le</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($participants as $p)
                                                    <tr class="border-b border-[#EDF0F5] last:border-b-0 hover:bg-[#FAFBFD]">
                                                        <td class="px-4 py-2.5 text-[13px] font-semibold text-brand">
                                                            <span class="flex items-center gap-1.5">
                                                                {{ $p['prenom'] }} {{ $p['nom'] }}
                                                                @if (! empty($p['answers']) && count($detailFields))
                                                                    <button wire:click="toggleParticipant({{ $p['id'] }})" class="icon-btn rounded p-0.5 text-azure hover:bg-azure/10" title="Voir les réponses"><x-ui.icon name="{{ $expandedParticipant === $p['id'] ? 'chevron-right' : 'plus' }}" class="size-3.5" /></button>
                                                                @endif
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2.5 text-[12.5px] text-[#5B677A]">{{ $p['telephone'] }}</td>
                                                        <td class="px-3 py-2.5 text-[12.5px] text-[#5B677A]">{{ $p['email'] ?: '—' }}</td>
                                                        <td class="px-3 py-2.5">
                                                            @if ($p['is_member'])
                                                                <span class="rounded-full bg-brand/10 px-2 py-0.5 text-[10.5px] font-bold text-brand">Membre</span>
                                                            @else
                                                                <span class="text-[11.5px] text-[#9AA6B8]">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2.5 text-[12px] text-[#9AA6B8]">{{ $p['date_label'] }}</td>
                                                    </tr>
                                                    @if ($expandedParticipant === $p['id'] && count($detailFields))
                                                        <tr class="bg-[#F8FAFC]">
                                                            <td colspan="5" class="px-4 py-3">
                                                                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                                                                    @foreach ($detailFields as $f)
                                                                        @php $val = $p['answers'][$f['key']] ?? null; @endphp
                                                                        <div>
                                                                            <p class="text-[11px] font-bold text-[#5B677A]">{{ $f['label'] }}</p>
                                                                            @if ($f['type'] === 'file' && $val)
                                                                                <a href="{{ $val }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[12.5px] font-semibold text-azure hover:underline"><x-ui.icon name="file-text" class="size-3.5" /> Ouvrir le fichier</a>
                                                                            @elseif ($f['type'] === 'checkbox')
                                                                                <p class="text-[12.5px] text-ink">{{ $val ? 'Oui' : 'Non' }}</p>
                                                                            @else
                                                                                <p class="text-[12.5px] text-ink">{{ $val !== null && $val !== '' ? $val : '—' }}</p>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <x-ui.pager :meta="$meta" />
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-[18px] border border-dashed border-brand/20 bg-white py-14 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-brand/[.06] text-brand"><x-ui.icon name="qr-code" class="size-6" /></span>
                    <p class="mt-3 text-sm font-bold text-brand">Aucun événement pour le moment</p>
                    <p class="mt-1 text-xs text-[#9AA6B8]">Créez votre premier événement pour générer un QR code d'inscription.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
