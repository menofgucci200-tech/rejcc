@php
    $badges = [
        'en_attente' => ['label' => 'En attente', 'cls' => 'bg-amber-100 text-amber-700', 'icon' => 'clock'],
        'valide' => ['label' => 'Validé', 'cls' => 'bg-emerald-100 text-emerald-700', 'icon' => 'check-circle'],
        'rejete' => ['label' => 'Rejeté', 'cls' => 'bg-red-100 text-red-700', 'icon' => 'x-circle'],
    ];
    $payLabels = ['wave' => 'Wave', 'orange' => 'Orange Money', 'djamo' => 'Djamo'];
@endphp

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand">Demandes d'adhésion</h1>
        <p class="text-sm text-ink/60">{{ $adhesions->count() }} demande{{ $adhesions->count() > 1 ? 's' : '' }}</p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-brand/10 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-brand/10 bg-cloud/60">
                <tr>
                    @foreach (['Réf.', 'Demandeur', 'Profil', 'Paiement', 'Statut', 'Date', 'Actions'] as $h)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink/50">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-brand/5">
                @foreach ($adhesions as $a)
                    @php $badge = $badges[$a->statut] ?? $badges['en_attente']; @endphp
                    <tr class="hover:bg-cloud/50">
                        <td class="px-4 py-3 font-mono text-xs text-ink/60">{{ $a->reference }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-brand">{{ $a->prenom }} {{ $a->nom }}</p>
                            <p class="text-xs text-ink/50">{{ $a->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink/60">{{ $a->profil ?? '—' }}</td>
                        <td class="px-4 py-3 text-ink/60">{{ $payLabels[$a->paiement] ?? $a->paiement }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge['cls'] }}">
                                <x-ui.icon :name="$badge['icon']" class="size-3" /> {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ink/50">{{ $a->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                @if ($a->statut !== 'valide')
                                    <button wire:click="updateStatut({{ $a->id }}, 'valide')" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                        Valider
                                    </button>
                                @endif
                                @if ($a->statut !== 'rejete')
                                    <button wire:click="updateStatut({{ $a->id }}, 'rejete')" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                        Rejeter
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($adhesions->isEmpty())
            <p class="py-12 text-center text-sm text-ink/50">Aucune demande.</p>
        @endif
    </div>
</div>
