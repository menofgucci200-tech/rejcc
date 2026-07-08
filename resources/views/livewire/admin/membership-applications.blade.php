<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-brand">Candidatures d'adhésion</h1>
            <p class="text-sm text-ink/60">{{ $applications->count() }} candidature{{ $applications->count() > 1 ? 's' : '' }}</p>
        </div>
        <div class="relative max-w-xs flex-1">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
            <input wire:model.live.debounce.300ms="query" type="text" placeholder="Rechercher…" class="w-full rounded-full border border-brand/15 bg-white py-2 pl-10 pr-4 text-sm outline-none focus:border-brand" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-brand/10 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-brand/10 bg-cloud/60">
                <tr>
                    @foreach (['Nom', 'Contact', 'Statut actuel', 'Défi principal', 'Date', 'Traité', 'Actions'] as $h)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink/50">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-brand/5">
                @foreach ($applications as $a)
                    <tr class="hover:bg-cloud/50">
                        <td class="px-4 py-3 font-semibold text-brand">{{ $a->nom_prenoms }}</td>
                        <td class="px-4 py-3 text-ink/70">
                            <p>{{ $a->email }}</p>
                            <p class="text-ink/50">{{ $a->whatsapp }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink/60">{{ implode(', ', $a->statut_actuel) }}</td>
                        <td class="px-4 py-3 text-ink/60">{{ $a->defi_principal }}</td>
                        <td class="px-4 py-3 text-ink/50">{{ $a->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <button
                                wire:click="toggleTraite({{ $a->id }})"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold transition {{ $a->traite ? 'bg-green-100 text-green-700' : 'bg-brand/10 text-brand' }}"
                            >
                                @if ($a->traite)
                                    <x-ui.icon name="check-circle" class="size-3" />
                                @endif
                                {{ $a->traite ? 'Traité' : 'À traiter' }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <button
                                wire:click="view({{ $a->id }})"
                                title="Voir le détail"
                                class="rounded-lg p-1.5 text-ink/40 transition hover:bg-brand/10 hover:text-brand"
                            >
                                <x-ui.icon name="external-link" class="size-4" />
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($applications->isEmpty())
            <p class="py-12 text-center text-sm text-ink/50">Aucune candidature pour le moment.</p>
        @endif
    </div>

    @if ($viewingApplication)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeView">
            <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 sm:p-8">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-brand">{{ $viewingApplication->nom_prenoms }}</h2>
                        <p class="text-sm text-ink/60">{{ $viewingApplication->email }} · {{ $viewingApplication->whatsapp }}</p>
                    </div>
                    <button wire:click="closeView" class="rounded-lg p-1.5 text-ink/40 transition hover:bg-brand/10 hover:text-brand">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    @php
                        $rows = [
                            'Sexe' => $viewingApplication->sexe,
                            "Tranche d'âge" => $viewingApplication->tranche_age,
                            'Connotation religieuse' => $viewingApplication->connotation_religieuse,
                            'Paroisse' => $viewingApplication->paroisse,
                            'Statut actuel' => implode(', ', $viewingApplication->statut_actuel),
                            "Niveau d'études" => $viewingApplication->niveau_etudes,
                            'Domaines de formation' => $viewingApplication->domaines_formation,
                            'Compétences' => implode(', ', $viewingApplication->competences),
                            'Description compétences' => $viewingApplication->description_competences,
                            'A une activité ?' => $viewingApplication->a_activite,
                            'Nom activité' => $viewingApplication->nom_activite,
                            "Secteurs d'activité" => $viewingApplication->secteurs_activite ? implode(', ', $viewingApplication->secteurs_activite) : null,
                            'Ancienneté' => $viewingApplication->anciennete,
                            'Domaines futurs' => $viewingApplication->domaines_futurs ? implode(', ', $viewingApplication->domaines_futurs) : null,
                            'Attentes' => implode(', ', $viewingApplication->attentes),
                            'Formations intéressantes' => implode(', ', $viewingApplication->formations_interet),
                            'Défi principal' => $viewingApplication->defi_principal,
                            'Revenu mensuel' => $viewingApplication->revenu_mensuel,
                        ];
                    @endphp
                    @foreach ($rows as $label => $value)
                        @if (! empty($value))
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink/40">{{ $label }}</dt>
                                <dd class="mt-1 text-sm text-brand">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
    @endif
</div>
