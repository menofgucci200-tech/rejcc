@php
    $statutLabel = fn ($s) => match ($s) {
        'success' => ['#22A85A', '#EAF6EE', 'Payé'],
        'failed' => ['#AC0100', '#F9E9E9', 'Échoué'],
        default => ['#F5A623', '#FCF1DD', 'En attente'],
    };
@endphp

<div>
    <x-member-light.topbar title="Mon abonnement" />

    <div class="mx-auto max-w-[860px] px-8 py-8">
        <div class="mb-6">
            <h1 class="mb-1 text-[17px] font-bold text-brand">Mon abonnement annuel</h1>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">
                L'abonnement de {{ number_format($amount, 0, ',', ' ') }} F CFA par an donne accès à la carte de
                membre officielle, à l'annuaire, à la messagerie, à la publication sur la Marketplace et à la soumission de projets.
            </p>
        </div>

        @if (session('abonnement_message'))
            <p class="panel-enter mb-5 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                <x-ui.icon name="check-circle" class="size-3.5" /> {{ session('abonnement_message') }}
            </p>
        @endif

        <div class="rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $active ? 'bg-[#22A85A]/10 text-[#22A85A]' : 'bg-[#F5A623]/10 text-[#B27007]' }}">
                        <x-ui.icon :name="$active ? 'shield-check' : 'shield'" class="size-5" />
                    </span>
                    <div>
                        <p class="text-[14px] font-bold text-brand">{{ $active ? 'Abonnement actif' : 'Abonnement non actif' }}</p>
                        <p class="text-[12.5px] text-[#5B677A]">
                            @if ($active && $expiresAt)
                                Valable jusqu'au {{ \Carbon\Carbon::parse($expiresAt)->translatedFormat('d F Y') }}
                            @else
                                Réglez votre cotisation pour débloquer les fonctionnalités premium.
                            @endif
                        </p>
                    </div>
                </div>

                <button
                    wire:click="payer"
                    wire:loading.attr="disabled"
                    wire:target="payer"
                    class="btn-tap inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="payer" class="inline-flex items-center gap-2">
                        <x-ui.icon name="shield-check" class="size-4" />
                        {{ $active ? 'Renouveler mon abonnement' : 'Payer '.number_format($amount, 0, ',', ' ').' F' }}
                    </span>
                    <span wire:loading wire:target="payer" class="inline-flex items-center gap-2">
                        <x-ui.icon name="loader-2" class="size-4 animate-spin" /> Redirection…
                    </span>
                </button>
            </div>

            @if ($erreur)
                <p class="mt-4 rounded-xl bg-[#F9E9E9] px-4 py-2.5 text-[12.5px] font-semibold text-[#AC0100]">{{ $erreur }}</p>
            @endif

            <p class="mt-4 text-[11.5px] text-[#9AA6B8]">Paiement sécurisé via CinetPay — Wave, Orange Money, MTN Money, Moov Money ou carte bancaire.</p>
        </div>

        @if (! empty($history))
            <div class="mt-6 rounded-[16px] border border-brand/10 bg-white p-6 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                <p class="mb-4 text-[13px] font-bold text-brand">Historique des paiements</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[12.5px]">
                        <thead>
                            <tr class="border-b border-cloud-200 text-[11px] font-bold uppercase tracking-[0.05em] text-[#9AA6B8]">
                                <th class="py-2 pr-3">Référence</th>
                                <th class="py-2 pr-3">Moyen</th>
                                <th class="py-2 pr-3">Montant</th>
                                <th class="py-2 pr-3">Statut</th>
                                <th class="py-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                @php [$color, $bg, $label] = $statutLabel($h['status'] ?? null); @endphp
                                <tr class="border-b border-cloud-200 last:border-0">
                                    <td class="py-2.5 pr-3 font-semibold text-ink">{{ $h['reference'] }}</td>
                                    <td class="py-2.5 pr-3 capitalize text-[#5B677A]">{{ $h['provider'] }}</td>
                                    <td class="py-2.5 pr-3 text-[#5B677A]">{{ number_format($h['amount'], 0, ',', ' ') }} {{ $h['currency'] }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold" style="background: {{ $bg }}; color: {{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td class="py-2.5 text-[#5B677A]">{{ \Carbon\Carbon::parse($h['created_at'])->translatedFormat('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
