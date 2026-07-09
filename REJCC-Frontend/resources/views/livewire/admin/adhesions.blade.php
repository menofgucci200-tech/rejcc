@php
    $candBadge = fn ($s) => match ($s ?? 'en_attente') {
        'accepte' => ['#22A85A', '#EAF6EE', 'Acceptée'],
        'refuse' => ['#AC0100', '#F9E9E9', 'Refusée'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
    $demBadge = fn ($s) => match ($s ?? 'en_attente') {
        'valide' => ['#22A85A', '#EAF6EE', 'Validé'],
        'rejete' => ['#AC0100', '#F9E9E9', 'Rejeté'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
@endphp
<div>
    <x-admin-light.topbar title="Adhésions & candidatures" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <section class="mb-8">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="mb-1 text-[17px] font-bold text-brand">Candidatures (formulaire enrichi)</h2>
                    <div class="h-[3px] w-9 rounded bg-accent"></div>
                </div>
                <span class="text-xs text-[#5B677A]">21 questions par candidature · réponses consultables en détail</span>
            </div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($candidatures as $c)
                    @php $b = $candBadge($c->statut ?? null); @endphp
                    <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-4 first:border-t-0">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #031D59, #4F6FBF)">
                            {{ collect(explode(' ', $c->nom_prenoms))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                        </span>
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $c->nom_prenoms }}</p>
                            <p class="mt-0.5 text-xs text-[#5B677A]">{{ $c->ville }} · soumise le {{ $c->created_at->format('d/m/Y') }}</p>
                        </div>
                        <button wire:click="toggleDetail({{ $c->id }})" class="shrink-0 rounded-lg border border-azure/25 px-3.5 py-1.5 text-xs font-semibold text-azure hover:bg-azure/10">
                            {{ $expanded === $c->id ? 'Masquer le détail' : 'Voir le détail' }}
                        </button>
                        <span class="w-24 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                        @if (($c->statut ?? 'en_attente') === 'en_attente')
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="accept({{ $c->id }})" class="rounded-lg bg-[#22A85A] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#1C8F4C]">Accepter</button>
                                <button wire:click="reject({{ $c->id }})" wire:confirm="Refuser cette candidature ?" class="rounded-lg border border-[#F0C9C9] px-3.5 py-1.5 text-xs font-bold text-accent hover:bg-[#F9E9E9]">Refuser</button>
                            </div>
                        @endif
                    </div>
                    @if ($expanded === $c->id)
                        @php
                            $rows = [
                                'Sexe' => $c->sexe, "Tranche d'âge" => $c->tranche_age, 'Contact' => $c->whatsapp.' · '.$c->email,
                                'Statut actuel' => implode(', ', $c->statut_actuel ?? []), "Niveau d'études" => $c->niveau_etudes,
                                "Domaines de formation" => $c->domaines_formation, 'Compétences' => implode(', ', $c->competences ?? []),
                                'A une activité ?' => $c->a_activite, "Secteur d'activité" => $c->nom_activite,
                                'Attentes' => implode(', ', $c->attentes ?? []), 'Défi principal' => $c->defi_principal,
                                'Revenu mensuel' => $c->revenu_mensuel,
                            ];
                        @endphp
                        <div class="mb-3.5 grid grid-cols-1 gap-x-6 gap-y-2.5 rounded-xl bg-[#F8FAFC] p-4 sm:grid-cols-2">
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
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucune candidature pour le moment.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="mb-1 text-[17px] font-bold text-brand">Demandes d'adhésion (ancien formulaire)</h2>
            <div class="mb-4 h-[3px] w-9 rounded bg-accent"></div>
            <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                @forelse ($demandes as $d)
                    @php $b = $demBadge($d->statut ?? null); @endphp
                    <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                        <div class="min-w-[180px] flex-1">
                            <p class="text-[13.5px] font-bold text-brand">{{ $d->prenom }} {{ $d->nom }}</p>
                            <p class="text-xs text-[#5B677A]">{{ $d->email }} · soumise le {{ $d->created_at->format('d/m/Y') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold" style="color: {{ $b[0] }}; background: {{ $b[1] }}">{{ $b[2] }}</span>
                        <select onchange="event.stopPropagation()" wire:change="updateStatut({{ $d->id }}, $event.target.value)" class="shrink-0 rounded-lg border border-brand/10 px-2.5 py-1.5 text-xs text-ink outline-none">
                            <option value="en_attente" @selected(($d->statut ?? 'en_attente') === 'en_attente')>En attente</option>
                            <option value="valide" @selected(($d->statut ?? '') === 'valide')>Validé</option>
                            <option value="rejete" @selected(($d->statut ?? '') === 'rejete')>Rejeté</option>
                        </select>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-[#5B677A]">Aucune demande.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
