@php $all = $pending->concat($done); @endphp

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand">Messages de contact</h1>
        <p class="text-sm text-ink/60">{{ $pending->count() }} non traité{{ $pending->count() > 1 ? 's' : '' }} · {{ $done->count() }} traité{{ $done->count() > 1 ? 's' : '' }}</p>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($all as $c)
            <div class="rounded-2xl border bg-white transition-all {{ $c->traite ? 'border-brand/8 opacity-70' : 'border-brand/15' }}">
                <button wire:click="toggle({{ $c->id }})" class="flex w-full items-start gap-4 px-5 py-4 text-left">
                    <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-xl {{ $c->traite ? 'bg-emerald-100 text-emerald-600' : 'bg-brand/10 text-brand' }}">
                        <x-ui.icon name="message-circle" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <p class="truncate font-semibold text-brand">{{ $c->nom }}</p>
                            <span class="text-xs text-ink/40">{{ $c->created_at->format('d/m/Y') }}</span>
                            @if (! $c->traite)
                                <span class="rounded-full bg-accent/15 px-2 py-0.5 text-[0.65rem] font-bold text-accent">NOUVEAU</span>
                            @endif
                        </div>
                        <p class="truncate text-sm text-ink/60">{{ $c->sujet }}</p>
                    </div>
                </button>

                @if ($expanded === $c->id)
                    <div class="border-t border-brand/8 px-5 pb-4 pt-3">
                        <p class="mb-1 text-xs text-ink/50">E-mail : <span class="text-brand">{{ $c->email }}</span></p>
                        <p class="whitespace-pre-wrap text-sm text-ink/80">{{ $c->message }}</p>
                        @if (! $c->traite)
                            <button wire:click="markTraite({{ $c->id }})" class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <x-ui.icon name="check-circle" class="size-3.5" /> Marquer comme traité
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
        @if ($all->isEmpty())
            <p class="py-12 text-center text-sm text-ink/50">Aucun message.</p>
        @endif
    </div>
</div>
