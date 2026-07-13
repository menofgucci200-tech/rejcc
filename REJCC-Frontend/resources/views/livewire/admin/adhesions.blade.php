@php
    $candBadge = fn ($s) => match ($s ?? 'en_attente') {
        'accepte' => ['#22A85A', '#EAF6EE', 'Approuvée'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Rejetée'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
@endphp
<div>
    <x-admin-light.topbar title="Adhésions" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes d'adhésion</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
            </div>
            <span class="text-xs text-[#5B677A]">{{ $candidatures->count() }} demande{{ $candidatures->count() > 1 ? 's' : '' }} · réponses complètes consultables via « Voir »</span>
        </div>
        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($candidatures as $c)
                @php $b = $candBadge($c->statut ?? null); @endphp
                <div class="row-hover -mx-5 flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] px-5 py-4 first:border-t-0">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #031D59, #4F6FBF)">
                        {{ mb_substr($c->prenom, 0, 1) }}{{ mb_substr($c->nom, 0, 1) }}
                    </span>
                    <div class="min-w-[180px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $c->prenom }} {{ $c->nom }}</p>
                        <p class="mt-0.5 text-xs text-[#5B677A]">{{ $c->ville }} · soumise le {{ $c->created_at->format('d/m/Y') }}</p>
                    </div>
                    <span class="w-24 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                    <div class="flex shrink-0 items-center gap-2">
                        <button wire:click="toggleDetail({{ $c->id }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-azure/25 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                            <x-ui.icon name="eye" class="size-3.5" /> {{ $expanded === $c->id ? 'Masquer' : 'Voir' }}
                        </button>
                        @if (($c->statut ?? 'en_attente') === 'en_attente')
                            <button wire:click="accept({{ $c->id }})" wire:confirm="Approuver la demande de {{ $c->prenom }} {{ $c->nom }} ? Son compte membre sera créé et un email de bienvenue lui sera envoyé." class="btn-tap inline-flex items-center gap-1.5 rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">
                                <x-ui.icon name="check" class="size-3.5" /> Approuver
                            </button>
                            <button wire:click="openReject({{ $c->id }})" class="btn-tap inline-flex items-center gap-1.5 rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">
                                <x-ui.icon name="x" class="size-3.5" /> Rejeter
                            </button>
                        @endif
                    </div>
                </div>
                @if ($rejectingId === $c->id)
                    <div class="panel-enter mb-4 rounded-[12px] border border-[#F0C9C9] bg-[#FDF7F7] p-4">
                        <p class="mb-2 text-[13px] font-bold text-accent">Rejeter la demande de {{ $c->prenom }} {{ $c->nom }}</p>
                        <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Motif du rejet (communiqué au candidat par email — optionnel)</label>
                        <textarea wire:model="motif" rows="2" placeholder="Ex : dossier incomplet, informations à préciser…" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure"></textarea>
                        @error('motif') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                        <div class="mt-3 flex gap-2">
                            <button wire:click="confirmReject" wire:loading.attr="disabled" class="btn-tap rounded-lg bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600 disabled:opacity-60">Confirmer le rejet</button>
                            <button wire:click="cancelReject" class="btn-tap rounded-lg border border-[#C9D3E6] px-4 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Annuler</button>
                        </div>
                    </div>
                @endif
                @if ($expanded === $c->id)
                    @php
                        $rows = [
                            'Sexe' => $c->sexe, "Tranche d'âge" => $c->tranche_age, 'Contact' => $c->whatsapp.' · '.$c->email,
                            'Diocèse / Paroisse' => trim(($c->diocese ?? '').' — '.($c->paroisse ?? ''), ' —'),
                            'Ville' => $c->ville ?? null,
                            'Statut actuel' => implode(', ', $c->statut_actuel ?? []), "Niveau d'études" => $c->niveau_etudes,
                            "Domaines de formation" => $c->domaines_formation, 'Compétences' => implode(', ', $c->competences ?? []),
                            'Description des compétences' => $c->description_competences ?? null,
                            'A une activité ?' => $c->a_activite, 'Activité' => $c->nom_activite,
                            "Secteurs d'activité" => implode(', ', $c->secteurs_activite ?? []),
                            'Ancienneté' => $c->anciennete ?? null,
                            'Domaines futurs' => implode(', ', $c->domaines_futurs ?? []),
                            'Attentes' => implode(', ', $c->attentes ?? []),
                            'Formations souhaitées' => implode(', ', $c->formations_interet ?? []),
                            'Défi principal' => $c->defi_principal,
                            'Revenu mensuel' => $c->revenu_mensuel,
                            'Motif du rejet' => $c->reject_reason ?? null,
                        ];
                    @endphp
                    <div class="panel-enter mb-3.5 grid grid-cols-1 gap-x-6 gap-y-2.5 rounded-xl bg-[#F8FAFC] p-4 sm:grid-cols-2">
                        @foreach ($rows as $label => $value)
                            @if (! empty($value))
                                <div>
                                    <p class="text-[11px] font-bold text-[#5B677A]">{{ $label }}</p>
                                    <p class="mt-0.5 text-[12.5px] text-ink">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @empty
                <p class="py-10 text-center text-sm text-[#5B677A]">Aucune demande d'adhésion pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
