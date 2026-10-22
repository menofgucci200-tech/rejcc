<div>
    <x-admin-light.topbar title="Pages du site" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Pages du site vitrine</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 text-xs text-[#9AA6B8]">Modifiez les textes, affichez ou masquez des sections, ajustez le référencement Google — publié immédiatement.</p>
        </div>

        {{-- Sélecteur de page --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($pages as $slug => $p)
                <button wire:click="selectPage('{{ $slug }}')" class="btn-tap rounded-full border px-4 py-2 text-[12.5px] font-bold transition-colors duration-200 {{ $page === $slug ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">{{ $p['label'] }}</button>
            @endforeach
        </div>

        @if ($message)
            <p class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-[1.5fr_1fr]">
            {{-- Sections de la page --}}
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-bold text-brand">Sections de « {{ $current['label'] }} »</p>
                    <a href="{{ url($current['path']) }}" target="_blank" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-azure/25 bg-azure/10 px-3 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20">
                        <x-ui.icon name="external-link" class="size-3" /> Voir la page
                    </a>
                </div>

                <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    @foreach ($sections as $s)
                        <div class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0 {{ $s['visible'] ? '' : 'opacity-55' }}">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="min-w-[180px] flex-1">
                                    <p class="text-[13.5px] font-bold text-brand">
                                        {{ $s['label'] }}
                                        @if ($s['overridden'])
                                            <span class="ml-1.5 rounded-full bg-azure/10 px-2 py-0.5 text-[10px] font-bold text-azure">Modifiée</span>
                                        @endif
                                        @if (! $s['visible'])
                                            <span class="ml-1.5 rounded-full bg-[#EEF1F5] px-2 py-0.5 text-[10px] font-bold text-[#9AA6B8]">Masquée</span>
                                        @endif
                                    </p>
                                    @if ($s['hint'])
                                        <p class="mt-0.5 text-[11px] text-[#9AA6B8]">{{ $s['hint'] }}</p>
                                    @endif
                                </div>

                                @if ($s['editable'])
                                    <button wire:click="openEdit('{{ $s['key'] }}')" class="btn-tap shrink-0 rounded-lg border border-azure/25 px-3 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                                        Modifier les textes
                                    </button>
                                @endif

                                @if (! $s['locked'])
                                    <button
                                        wire:click="toggleSection('{{ $s['key'] }}')"
                                        title="{{ $s['visible'] ? 'Masquer cette section sur le site' : 'Réafficher cette section' }}"
                                        class="relative h-6 w-[42px] shrink-0 rounded-full transition-colors duration-200 active:scale-95"
                                        style="background: {{ $s['visible'] ? '#22A85A' : '#E6EAF0' }}"
                                    >
                                        <span class="absolute top-[3px] size-[18px] rounded-full bg-white shadow transition-all duration-200 ease-out" style="left: {{ $s['visible'] ? '21px' : '3px' }}"></span>
                                    </button>
                                @else
                                    <span class="shrink-0 text-[10.5px] font-semibold text-[#9AA6B8]">Toujours visible</span>
                                @endif
                            </div>

                            {{-- Formulaire d'édition de la section --}}
                            @if ($editingSection === $s['key'])
                                <div class="panel-enter mt-3.5 flex flex-col gap-3 rounded-xl bg-[#F8FAFC] p-4">
                                    @foreach ($s['fields'] as $fieldKey => $field)
                                        <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">{{ $field['label'] }}
                                            @if ($field['type'] === 'textarea')
                                                <textarea wire:model="fields.{{ $fieldKey }}" rows="3" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                                            @else
                                                <input wire:model="fields.{{ $fieldKey }}" type="text" class="rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                                            @endif
                                            @if ($field['default'] !== '')
                                                <span class="font-normal text-[#9AA6B8]">Texte d'origine : « {{ \Illuminate\Support\Str::limit($field['default'], 90) }} » — videz le champ pour y revenir.</span>
                                            @endif
                                        </label>
                                    @endforeach
                                    @error('fields') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                                    <div class="flex gap-2">
                                        <button wire:click="saveSection" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                                        <button wire:click="closeEdit" class="btn-tap rounded-[9px] border border-[#C9D3E6] px-5 py-2 text-sm font-bold text-brand hover:bg-cloud">Annuler</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- SEO de la page --}}
            <section class="rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-1 text-sm font-bold text-brand">Référencement Google (SEO)</p>
                <p class="mb-4 text-[11.5px] text-[#9AA6B8]">Titre et description affichés dans les résultats de recherche et lors des partages WhatsApp / Facebook. Laissez vide pour garder les valeurs automatiques.</p>
                <div class="flex flex-col gap-3">
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Titre (70 caractères max)
                        <input wire:model="seoTitle" type="text" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure" />
                        @error('seoTitle') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-[#5B677A]">Description (170 caractères max)
                        <textarea wire:model="seoDescription" rows="3" class="rounded-[9px] border border-brand/15 px-3 py-2 text-sm font-normal outline-none focus:border-azure"></textarea>
                        @error('seoDescription') <span class="font-medium text-accent">{{ $message }}</span> @enderror
                    </label>
                    <button wire:click="saveSeo" wire:loading.attr="disabled" class="btn-tap w-fit rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60">Publier</button>
                </div>

                {{-- Aperçu Google --}}
                <div class="mt-5 rounded-xl border border-brand/10 bg-cloud/50 p-4">
                    <p class="mb-2 text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#9AA6B8]">Aperçu Google</p>
                    <p class="truncate text-[15px] font-medium text-[#1a0dab]">{{ $seoTitle ?: $current['label'].' · REJCC' }}</p>
                    <p class="text-[12px] text-[#006621]">rejcc.site{{ $current['path'] === '/' ? '' : $current['path'] }}</p>
                    <p class="line-clamp-2 text-[12.5px] leading-snug text-[#545454]">{{ $seoDescription ?: 'Description automatique de la page (générée à partir du contenu actuel).' }}</p>
                </div>
            </section>
        </div>
    </div>
</div>
