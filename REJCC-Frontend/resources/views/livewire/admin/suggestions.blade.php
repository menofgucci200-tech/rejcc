<div>
    <x-admin-light.topbar title="Retours des testeurs" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Retours des comptes de test</h2>
                <div class="mb-2 h-[3px] w-9 rounded bg-accent"></div>
                <p class="max-w-2xl text-xs text-[#5B677A]">
                    Bugs et suggestions envoyés par les départements avec le bouton « Signaler · Suggérer », visible seulement des comptes de test. Ils servent à corriger le site et à décider s'il faut un espace dédié par département.
                </p>
            </div>
            <a href="{{ route('admin.export', ['dataset' => 'suggestions']) }}" class="btn-tap rounded-[10px] border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">Exporter (CSV)</a>
        </div>

        {{-- Synthèse par département --}}
        <div class="mb-6 flex flex-wrap gap-2">
            <button wire:click="$set('departement', '')" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $departement === '' ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                Tous ({{ $departements->sum('nombre') }})
            </button>
            @foreach ($departements as $d)
                @php $cle = $d['departement'] ?? '_aucun'; @endphp
                <button wire:click="$set('departement', @js($cle))" class="btn-tap rounded-full px-3.5 py-1.5 text-xs font-bold {{ $departement === $cle ? 'bg-brand text-white' : 'border border-brand/15 bg-white text-brand hover:bg-cloud' }}">
                    {{ $d['departement'] ?? 'Sans département' }} ({{ $d['nombre'] }})
                </button>
            @endforeach
            <select wire:model.live="statut" class="ml-auto rounded-full border-brand/15 py-1.5 text-xs">
                <option value="">Tous les statuts</option>
                @foreach ($statuts as $cle => $libelle)
                    <option value="{{ $cle }}">{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($suggestions as $s)
                <div class="row-hover -mx-5 border-t border-[#EDF0F5] px-5 py-4 first:border-t-0" wire:key="s-{{ $s->id }}">
                    <div class="flex flex-wrap items-start gap-3.5">
                        <div class="min-w-[220px] flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-azure">
                                {{ $s->departement ?? 'Sans département' }} · {{ $s->categorie_label }}
                            </p>
                            <p class="mt-1 whitespace-pre-line text-[13.5px] text-ink">{{ $s->message }}</p>
                            <p class="mt-1.5 text-xs text-[#5B677A]">
                                {{ $s->auteur ?? 'Auteur inconnu' }} · {{ $s->created_at->diffForHumans() }}
                                @if ($s->page) · depuis <span class="font-mono">{{ \Illuminate\Support\Str::limit($s->page, 50) }}</span> @endif
                                @if ($s->navigateur) · <span title="{{ $s->navigateur }}">{{ \Illuminate\Support\Str::limit($s->navigateur, 40) }}</span> @endif
                            </p>
                            @if ($s->reponse && $ouverte !== $s->id)
                                <p class="mt-2 rounded-lg bg-[#F8FAFC] px-3 py-2 text-xs text-ink"><span class="font-bold">Réponse :</span> {{ $s->reponse }}</p>
                            @endif
                        </div>
                        <select wire:change="changerStatut({{ $s->id }}, $event.target.value)" class="shrink-0 rounded-lg border-[#C9D6F0] py-1.5 text-xs font-bold">
                            @foreach ($statuts as $cle => $libelle)
                                <option value="{{ $cle }}" @selected($s->statut === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        <button wire:click="ouvrir({{ $s->id }}, @js($s->reponse))" class="btn-tap shrink-0 rounded-lg border border-[#C9D6F0] px-3.5 py-1.5 text-xs font-bold text-azure hover:bg-[#E8EDF8]">
                            {{ $ouverte === $s->id ? 'Annuler' : ($s->reponse ? 'Modifier la réponse' : 'Répondre') }}
                        </button>
                    </div>
                    @if ($ouverte === $s->id)
                        <div class="panel-enter mt-3 flex flex-col gap-2">
                            <textarea wire:model="reponse" rows="3" maxlength="2000" class="w-full rounded-xl border-[#C9D6F0] text-sm" placeholder="Note interne ou réponse à transmettre au département"></textarea>
                            <button wire:click="enregistrerReponse({{ $s->id }})" class="btn-tap self-end rounded-lg bg-brand px-4 py-1.5 text-xs font-bold text-white hover:bg-azure">Enregistrer</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-10 text-center text-sm text-[#5B677A]">Aucun retour pour l'instant.</p>
            @endforelse
        </div>
    </div>
</div>
