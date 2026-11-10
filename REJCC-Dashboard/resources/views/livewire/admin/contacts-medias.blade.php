<div>
    <x-admin-light.topbar title="Contacts médias" />

    @php
        $input = 'w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure';
        $lab = 'mb-1 block text-xs font-semibold text-[#5B677A]';
        $teintes = ['presse' => '#4F6FBF', 'radio' => '#B27007', 'tv' => '#AC0100', 'web' => '#1C8F4C', 'influenceur' => '#8A4FBF', 'institution' => '#031D59', 'autre' => '#5B677A'];
    @endphp
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-8 sm:py-8">
        <x-admin-light.onglets groupe="ressources" />
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Fichier des contacts médias</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 max-w-2xl text-xs text-[#5B677A]">Journalistes, radios, télévisions, médias web, influenceurs et institutions. Ce sont des données personnelles : réservées à l'équipe Communication, à ne partager qu'avec l'accord des personnes concernées.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.export', array_filter(['dataset' => 'contacts-medias', 'type' => $type])) }}" data-test="exporter-contacts" class="btn-tap inline-flex items-center gap-1.5 rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud"><x-ui.icon name="download" class="size-3.5" /> Exporter (CSV)</a>
                <button wire:click="openCreate" data-test="ajouter-contact" class="btn-tap rounded-[10px] bg-accent px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-accent-600">+ Ajouter un contact</button>
            </div>
        </div>

        @if ($message)
            <p wire:key="flash-ok" class="panel-enter mb-4 flex items-start gap-1.5 rounded-[12px] bg-[#22A85A]/10 px-3.5 py-2 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="mt-px size-3.5 shrink-0" /> {{ $message }}</p>
        @endif
        @if ($erreur && ! $showForm)
            <p wire:key="flash-ko" class="panel-enter mb-4 rounded-[12px] bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent">{{ $erreur }}</p>
        @endif

        @if ($showForm)
            <form wire:key="form-contact" wire:submit="save" data-test="form-contact" class="panel-enter mb-6 grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-5 shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex items-center justify-between sm:col-span-2 lg:col-span-3">
                    <p class="text-sm font-bold text-brand">{{ $editingId ? 'Modifier le contact' : 'Nouveau contact' }}</p>
                    <button type="button" wire:click="closeForm" class="icon-btn rounded-lg p-1 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div><label class="{{ $lab }}">Nom *</label><input wire:model="form.nom" type="text" data-test="contact-nom" maxlength="150" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Média ou organisation</label><input wire:model="form.media" type="text" data-test="contact-media" maxlength="150" class="{{ $input }}" /></div>
                <div>
                    <label class="{{ $lab }}">Type *</label>
                    <select wire:model="form.type" data-test="contact-type" class="{{ $input }}">
                        @foreach ($types as $k => $v) <option value="{{ $k }}">{{ $v }}</option> @endforeach
                    </select>
                </div>
                <div><label class="{{ $lab }}">Fonction</label><input wire:model="form.fonction" type="text" placeholder="Journaliste, rédacteur en chef…" maxlength="150" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">E-mail</label><input wire:model="form.email" type="email" data-test="contact-email" maxlength="150" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Téléphone / WhatsApp</label><input wire:model="form.telephone" type="tel" placeholder="+225 07 00 00 00 00" maxlength="40" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Ville</label><input wire:model="form.ville" type="text" maxlength="100" class="{{ $input }}" /></div>
                <div><label class="{{ $lab }}">Date du dernier contact</label><input wire:model="form.dernier_contact" type="date" max="{{ now()->toDateString() }}" class="{{ $input }}" /></div>
                <div class="sm:col-span-2 lg:col-span-3"><label class="{{ $lab }}">Notes (sujets couverts, échanges, préférences)</label><textarea wire:model="form.notes" rows="2" maxlength="3000" class="{{ $input }}"></textarea></div>
                @if ($erreur) <p class="rounded-[9px] bg-accent/10 px-3 py-2 text-xs font-semibold text-accent sm:col-span-2 lg:col-span-3">{{ $erreur }}</p> @endif
                <button type="submit" wire:loading.attr="disabled" data-test="enregistrer-contact" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60 sm:w-fit">Enregistrer</button>
            </form>
        @endif

        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Tous'] + $types as $cle => $libelle)
                <button wire:click="setType('{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $type === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }} <span class="{{ $type === $cle ? 'text-white/70' : 'text-[#5B677A]' }}">{{ $compteurs[$cle] ?? 0 }}</span></button>
            @endforeach
            <input wire:model.live.debounce.300ms="recherche" type="search" data-test="recherche-contacts" placeholder="Nom, média, ville…" class="ml-auto w-full rounded-full border border-brand/15 bg-white px-4 py-2 text-xs outline-none focus:border-azure sm:w-60" />
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($contacts as $c)
                @php
                    $teinte = $teintes[$c['type']] ?? '#5B677A';
                    $chiffres = preg_replace('/\D/', '', (string) $c['telephone']);
                    $ilYa = $c['dernier_contact'] ? \Illuminate\Support\Carbon::parse($c['dernier_contact']) : null;
                @endphp
                <div wire:key="c-{{ $c['id'] }}" data-test="ligne-contact" class="border-t border-[#EDF0F5] px-4 py-3.5 first:border-t-0 sm:px-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white" style="background: {{ $teinte }}">{{ preg_match('/\p{L}/u', preg_replace('/^\[TEST\]\s*/', '', $c['nom']), $lettre) ? mb_strtoupper($lettre[0]) : '?' }}</span>
                        <button type="button" wire:click="basculer({{ $c['id'] }})" class="min-w-[200px] flex-1 text-left">
                            <p class="flex flex-wrap items-center gap-1.5 text-[13.5px] font-bold text-brand">{{ $c['nom'] }}
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: {{ $teinte }}1A; color: {{ $teinte }}">{{ $c['type_label'] }}</span>
                            </p>
                            <p class="text-[11.5px] text-[#5B677A]">{{ collect([$c['fonction'], $c['media'], $c['ville']])->filter()->join(' · ') ?: '—' }}</p>
                        </button>
                        <span class="text-[11px] {{ $ilYa && $ilYa->lt(now()->subMonths(6)) ? 'text-[#B27007]' : 'text-[#5B677A]' }}" title="Dernier contact">{{ $ilYa ? 'Contacté le '.$ilYa->translatedFormat('j M Y') : 'Jamais contacté' }}</span>
                        <div class="flex shrink-0 items-center gap-1">
                            @if ($c['email'])<a href="mailto:{{ $c['email'] }}" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="{{ $c['email'] }}"><x-ui.icon name="send" class="size-3.5" /></a>@endif
                            @if ($chiffres)
                                <a href="tel:{{ $chiffres }}" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="Appeler {{ $c['telephone'] }}"><x-ui.icon name="phone" class="size-3.5" /></a>
                                <a href="https://wa.me/{{ $chiffres }}" target="_blank" rel="noopener" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-[#1C8F4C]/10 hover:text-[#1C8F4C]" title="WhatsApp"><x-ui.icon name="message-circle" class="size-3.5" /></a>
                            @endif
                            <button wire:click="openEdit({{ $c['id'] }})" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-brand/10 hover:text-brand" title="Modifier"><x-ui.icon name="pencil" class="size-3.5" /></button>
                            <button wire:click="delete({{ $c['id'] }})" wire:confirm="Supprimer le contact « {{ $c['nom'] }} » ?" class="icon-btn rounded-lg p-1.5 text-[#5B677A] hover:bg-accent/10 hover:text-accent" title="Supprimer"><x-ui.icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    </div>
                    @if ($ouvert === $c['id'])
                        <div wire:key="detail-{{ $c['id'] }}" class="panel-enter mt-2.5 grid gap-1.5 rounded-[12px] bg-cloud/50 p-3 pl-[52px] text-[12.5px] sm:grid-cols-2">
                            <p><span class="text-[#5B677A]">E-mail :</span> {{ $c['email'] ?: '—' }}</p>
                            <p><span class="text-[#5B677A]">Téléphone / WhatsApp :</span> {{ $c['telephone'] ?: '—' }}</p>
                            @if ($c['notes'])<p class="whitespace-pre-line sm:col-span-2"><span class="text-[#5B677A]">Notes :</span> {{ $c['notes'] }}</p>@endif
                            <p class="sm:col-span-2"><button wire:click="contacteAujourdhui({{ $c['id'] }})" class="text-[12px] font-bold text-azure hover:underline">Noter un contact aujourd'hui</button></p>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">{{ $recherche || $type ? 'Aucun contact ne correspond.' : 'Aucun contact pour l\'instant : ajoutez vos premiers contacts presse.' }}</p>
            @endforelse
        </div>
    </div>
</div>
