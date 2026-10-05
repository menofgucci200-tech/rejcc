<div>
    <x-admin-light.topbar title="Signalements" />

    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Conversations signalées</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 max-w-2xl text-xs text-[#9AA6B8]">Les messages privés ne sont consultables ici que parce que l'un des deux participants a signalé la conversation. Classez sans suite ou adressez un avertissement ; une suspension se fait depuis « Comptes membres ».</p>
        </div>

        @if ($message)
            <p data-test="message-admin" class="panel-enter mb-4 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}</p>
        @endif

        <div class="mb-4 flex gap-1.5">
            @foreach (['nouveau' => 'À examiner', 'traite' => 'Traités'] as $cle => $libelle)
                <button type="button" wire:click="$set('statut', '{{ $cle }}')" class="rounded-full px-3.5 py-1.5 text-[12px] font-bold {{ $statut === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">{{ $libelle }}</button>
            @endforeach
        </div>

        @if (! $ok)
            <p class="rounded-[16px] border border-brand/10 bg-white py-10 text-center text-sm text-[#5B677A]">Signalements indisponibles (accès à la section « Signalements messagerie » requis).</p>
        @else
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($signalements as $s)
                    <div data-test="ligne-signalement" wire:key="sig-{{ $s['id'] }}" class="-mx-5 flex flex-wrap items-center gap-3 border-t border-[#EDF0F5] px-5 py-3.5 first:border-t-0">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent/10 text-accent"><x-ui.icon name="alert-circle" class="size-4" /></span>
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[13px] text-[#5B677A]"><span class="font-bold text-brand">{{ $s['auteur'] }}</span> signale <span class="font-bold text-brand">{{ $s['signale'] }}</span>@unless ($s['signale_actif'])<span class="ml-1 rounded-full bg-accent/10 px-2 text-[10.5px] font-bold text-accent">Suspendu</span>@endunless</p>
                            @if ($s['motif'])<p class="mt-0.5 text-[12.5px] italic text-ink">« {{ $s['motif'] }} »</p>@endif
                            <p class="mt-0.5 text-[11px] text-[#9AA6B8]">{{ \Illuminate\Support\Carbon::parse($s['date'])->locale('fr')->diffForHumans() }} · {{ $s['messages'] }} message{{ $s['messages'] > 1 ? 's' : '' }}@if ($s['decision']) · {{ $s['decision'] === 'averti' ? 'Membre averti' : 'Classé sans suite' }}@endif</p>
                        </div>
                        <button type="button" wire:click="ouvrir({{ $s['id'] }})" data-test="examiner" class="btn-tap shrink-0 rounded-full border border-brand/15 px-3.5 py-1.5 text-[12px] font-bold text-brand hover:bg-cloud">{{ $statut === 'nouveau' ? 'Examiner' : 'Revoir' }}</button>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-[#5B677A]">{{ $statut === 'nouveau' ? 'Aucune conversation signalée : rien à examiner.' : 'Aucun signalement traité.' }}</p>
                @endforelse
            </div>
            @if (($meta['last_page'] ?? 1) > 1)
                <x-ui.pager :meta="$meta" />
            @endif
        @endif
    </div>

    @if ($detail)
        @php $sig = $detail['signalement']; @endphp
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-brand/40 p-4" wire:click.self="fermer" x-on:keydown.escape.window="$wire.fermer()">
            <div data-test="conversation-signalee" role="dialog" aria-modal="true" class="panel-enter flex max-h-[90vh] w-full max-w-[640px] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-cloud-200 px-6 py-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">Conversation signalée</p>
                        <p class="text-[15px] font-bold text-brand">{{ $sig['auteur'] }} ↔ {{ $sig['signale'] }}</p>
                        @if ($sig['motif'])<p class="mt-0.5 text-[12.5px] italic text-[#5B677A]">Motif : « {{ $sig['motif'] }} »</p>@endif
                    </div>
                    <button wire:click="fermer" aria-label="Fermer" class="icon-btn rounded-lg p-1.5 hover:bg-cloud"><x-ui.icon name="x" class="size-4 text-[#5B677A]" /></button>
                </div>
                <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto bg-cloud/40 px-6 py-4">
                    @foreach ($detail['messages'] as $m)
                        <div class="flex flex-col {{ $m['de_signale'] ? 'items-start' : 'items-end' }}">
                            <span class="mb-0.5 px-1 text-[10.5px] font-bold {{ $m['de_signale'] ? 'text-accent' : 'text-[#9AA6B8]' }}">{{ $m['de_signale'] ? $sig['signale'] : $sig['auteur'] }} · {{ \Illuminate\Support\Carbon::parse($m['date'])->setTimezone(config('app.timezone'))->format('d/m H:i') }}</span>
                            <div class="max-w-[80%] whitespace-pre-line break-words rounded-[12px] px-3 py-2 text-[13px] {{ $m['de_signale'] ? 'border border-accent/25 bg-white text-ink' : 'bg-white/70 text-[#5B677A]' }}">{{ $m['body'] }}</div>
                        </div>
                    @endforeach
                </div>
                @if ($sig['statut'] === 'nouveau')
                    <div class="flex flex-wrap items-center gap-2 border-t border-cloud-200 px-6 py-4">
                        <button wire:click="traiter('averti')" data-test="avertir" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Avertir {{ $sig['signale'] }}</button>
                        <button wire:click="traiter('classe')" data-test="classer" class="btn-tap rounded-full border border-brand/15 px-4 py-2 text-[12.5px] font-bold text-brand hover:bg-cloud">Classer sans suite</button>
                        <a href="{{ route('admin.members') }}" wire:navigate class="ml-auto text-[12px] font-semibold text-[#5B677A] hover:text-brand">Suspendre le compte…</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
