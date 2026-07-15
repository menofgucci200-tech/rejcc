<div>
    <x-member-light.topbar title="Emploi & Stage" />

    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Emploi &amp; Stage</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Offres d'emploi, stages et annonces partagés par le réseau. Publiez la vôtre et touchez tous les membres.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['tous' => 'Tous', 'emploi' => 'Emplois', 'stage' => 'Stages', 'annonce' => 'Annonces'] as $value => $label)
                    <button wire:click="setFiltre('{{ $value }}')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">{{ $label }}</button>
                @endforeach
                <button wire:click="openForm" class="btn-tap rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-accent-600 hover:shadow-md">+ Publier une offre</button>
            </div>
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        @if ($showForm)
            <div class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[16px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
                <div class="flex items-center justify-between sm:col-span-2">
                    <p class="text-sm font-bold text-brand">Publier une offre d'emploi ou de stage</p>
                    <button wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud hover:text-brand"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Intitulé du poste / stage</label>
                    <input wire:model="title" type="text" placeholder="Ex : Développeur web junior" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('title') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Type</label>
                    <select wire:model="type" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
                        <option value="emploi">Emploi</option>
                        <option value="stage">Stage</option>
                        <option value="annonce">Annonce</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Entreprise / structure</label>
                    <input wire:model="entreprise" type="text" placeholder="Ex : Ivoire Tech SARL" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('entreprise') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Lieu (ville / à distance)</label>
                    <input wire:model="lieu" type="text" placeholder="Ex : Abidjan, Cocody" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('lieu') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Description de l'offre</label>
                    <textarea wire:model="description" rows="4" placeholder="Missions, profil recherché, conditions…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                    @error('description') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Site web de l'entreprise (optionnel)</label>
                    <input wire:model="site_url" type="url" placeholder="https://…" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('site_url') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Contact pour postuler (e-mail ou téléphone)</label>
                    <input wire:model="contact" type="text" placeholder="rh@entreprise.ci ou 0700000000" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('contact') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Date limite de candidature (optionnel)</label>
                    <input wire:model="deadline" type="date" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                    @error('deadline') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <x-ui.media-field label="Fiche de poste / affiche (PDF, image ou lien — optionnel)" hint="Ajoutez la fiche de poste, une affiche ou un visuel de l'offre (20 Mo max), ou collez un lien." :media-url="$mediaUrl" :media-name="$mediaName" :media-size="$mediaSize" />
                </div>
                <button wire:click="publier" wire:loading.attr="disabled" wire:target="publier" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">
                    <span wire:loading.remove wire:target="publier">Publier l'offre</span>
                    <span wire:loading wire:target="publier">Publication…</span>
                </button>
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($offres as $o)
                <article class="card-hover rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:p-5">
                    <div class="flex flex-wrap items-start gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl text-white" style="background: linear-gradient(135deg, #4F6FBF, #AC0100)">
                            <x-ui.icon name="nav-briefcase" class="size-5" />
                        </span>
                        <div class="min-w-[200px] flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[14px] font-bold text-brand">{{ $o['titre'] }}</p>
                                <span class="rounded-full px-2.5 py-0.5 text-[10.5px] font-bold {{ $o['type'] === 'emploi' ? 'bg-brand/10 text-brand' : ($o['type'] === 'stage' ? 'bg-[#F5A623]/15 text-[#B27007]' : 'bg-azure/10 text-azure') }}">{{ ucfirst($o['type']) }}</span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] font-semibold text-[#5B677A]">
                                @if ($o['entreprise'])
                                    <span class="inline-flex items-center gap-1"><x-ui.icon name="store" class="size-3 text-azure" /> {{ $o['entreprise'] }}</span>
                                @endif
                                @if ($o['lieu'])
                                    <span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3 text-azure" /> {{ $o['lieu'] }}</span>
                                @endif
                            </p>
                            <p class="mt-1.5 line-clamp-3 text-xs leading-relaxed text-[#5B677A]">{{ $o['description'] }}</p>
                            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[#9AA6B8]">
                                <span>Par {{ $o['auteur'] }}</span>
                                <span>{{ $o['date'] }}</span>
                                @if ($o['deadline'])
                                    <span class="inline-flex items-center gap-1 font-semibold text-accent"><x-ui.icon name="clock" class="size-3" /> Avant le {{ $o['deadline'] }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-cloud-200 pt-3">
                        @if ($o['contact'])
                            @if (str_contains($o['contact'], '@'))
                                <a href="mailto:{{ $o['contact'] }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-1.5 text-xs font-bold text-white hover:bg-brand/90"><x-ui.icon name="send" class="size-3.5" /> Postuler</a>
                            @else
                                <a href="tel:{{ $o['contact'] }}" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-1.5 text-xs font-bold text-white hover:bg-brand/90"><x-ui.icon name="phone" class="size-3.5" /> {{ $o['contact'] }}</a>
                            @endif
                        @endif
                        @if ($o['site_url'])
                            <a href="{{ $o['site_url'] }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-1.5 text-xs font-semibold text-brand hover:bg-cloud"><x-ui.icon name="globe" class="size-3.5" /> Site de l'entreprise</a>
                        @endif
                        @if ($o['media'])
                            <a href="{{ $o['media'] }}" target="_blank" rel="noopener" class="btn-tap inline-flex items-center gap-1.5 rounded-full border border-brand/15 bg-white px-4 py-1.5 text-xs font-semibold text-brand hover:bg-cloud"><x-ui.icon name="file-text" class="size-3.5" /> Fiche de poste</a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Aucune offre{{ $filtre !== 'tous' ? ' dans cette catégorie' : '' }} pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
