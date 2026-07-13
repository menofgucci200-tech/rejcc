<div>
    <x-member-light.topbar title="Mes formations" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Mes formations</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <a href="{{ route('espace-membre.catalogue') }}" wire:navigate class="btn-tap rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white hover:bg-brand/90">
                Parcourir le catalogue
            </a>
        </div>

        @if ($enCours)
            <div class="mb-7 overflow-hidden rounded-[18px] p-6 text-white shadow-[0_12px_28px_rgba(3,29,89,.18)]" style="background: linear-gradient(120deg, {{ $enCours['from'] }}, {{ $enCours['to'] }})">
                <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-white/70">Continuer ma formation</p>
                <h2 class="mb-2 text-lg font-bold">{{ $enCours['titre'] }}</h2>
                <p class="mb-5 max-w-lg text-[13px] text-white/85">{{ $enCours['detail'] }}</p>
                <div class="mb-2 h-2 w-full max-w-md rounded-full bg-white/25">
                    <div class="h-2 rounded-full bg-white" style="width: {{ $enCours['pct'] }}%"></div>
                </div>
                <p class="mb-4 text-xs font-semibold text-white/80">{{ $enCours['pct'] }}% complété</p>
                <button wire:click="validerModule({{ $enCours['id'] }})" wire:loading.attr="disabled" class="group btn-tap inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-bold text-brand disabled:opacity-60">
                    Valider le module en cours <x-ui.icon name="arrow-right" class="nudge-x size-3.5" />
                </button>
            </div>
        @endif

        <div class="mb-5 flex flex-wrap gap-2">
            <button wire:click="setFiltre('tous')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'tous' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Toutes</button>
            <button wire:click="setFiltre('encours')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'encours' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">En cours</button>
            <button wire:click="setFiltre('termine')" class="btn-tap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors duration-200 {{ $filtre === 'termine' ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">Terminées</button>
        </div>

        <div class="space-y-3">
            @foreach ($cours as $c)
                <article class="card-hover flex flex-wrap items-center gap-4 rounded-[16px] border border-brand/10 bg-white p-4 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl text-white" style="background: linear-gradient(135deg, {{ $c['from'] }}, {{ $c['to'] }})">
                        <x-ui.icon name="graduation-cap" class="size-6" />
                    </span>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13px] font-bold text-brand">{{ $c['titre'] }}</p>
                        <p class="mt-0.5 text-xs text-[#9AA6B8]">{{ $c['categorie'] }}</p>
                    </div>
                    <div class="w-full max-w-[220px] flex-1">
                        <div class="h-1.5 w-full rounded-full bg-cloud">
                            <div class="h-1.5 rounded-full {{ $c['etat'] === 'termine' ? 'bg-[#22A85A]' : 'bg-azure' }}" style="width: {{ $c['pct'] }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] font-semibold text-[#5B677A]">{{ $c['pct'] }}%</p>
                    </div>
                    @if ($c['etat'] === 'termine')
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1.5 text-xs font-semibold text-[#22A85A]">
                            <x-ui.icon name="check" class="size-3.5" /> Terminée
                        </span>
                    @else
                        <button wire:click="validerModule({{ $c['id'] }})" wire:loading.attr="disabled" class="btn-tap shrink-0 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/20 disabled:opacity-60">Valider le module</button>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</div>
