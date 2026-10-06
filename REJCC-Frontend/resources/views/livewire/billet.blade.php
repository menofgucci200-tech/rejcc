@php
    $e = $billet['event'];
    $date = \Illuminate\Support\Carbon::parse($e['starts_at'])->setTimezone(config('app.timezone'))->locale('fr');
@endphp

<div>
    <section class="bg-cloud py-12 sm:py-16">
        <div class="mx-auto max-w-sm px-5">
            <div class="overflow-hidden rounded-3xl border border-brand/10 bg-white text-center shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)]">
                <div class="bg-brand px-6 py-5 text-white">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-white/70">Billet · REJCC</p>
                    <h1 class="mt-1 text-lg font-extrabold">{{ $e['title'] }}</h1>
                    <p class="mt-1 text-[12.5px] text-white/85">{{ ucfirst($date->isoFormat('dddd D MMMM YYYY [à] HH[h]mm')) }}</p>
                    @if ($e['location'])
                        <p class="text-[12.5px] text-white/85">{{ $e['location'] }}</p>
                    @endif
                </div>
                <div class="p-6" x-data x-init="$nextTick(() => window.QRCode && window.QRCode.toCanvas($refs.qr, $el.dataset.code, { width: 220, margin: 1, color: { dark: '#031D59' } }))" data-code="{{ $billet['code'] }}">
                    @if (($e['statut'] ?? '') === 'annule')
                        <p class="mb-3 rounded-xl bg-accent/10 px-3 py-2 text-[12.5px] font-semibold text-accent">Cet événement est annulé.{{ $e['motif_annulation'] ? ' Motif : '.$e['motif_annulation'] : '' }}</p>
                    @endif
                    <p class="text-[15px] font-bold text-brand">{{ $billet['nom'] }}</p>
                    <canvas x-ref="qr" class="mx-auto my-3"></canvas>
                    <p class="font-mono text-base font-bold tracking-wider text-brand">{{ $billet['code'] }}</p>
                    @if ($billet['present'])
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3 py-1 text-[12px] font-bold text-[#1C8F4C]"><x-ui.icon name="check-circle" class="size-3.5" /> Présence enregistrée</p>
                    @else
                        <p class="mt-3 text-[12px] text-[#5B677A]">Présentez ce QR code à l'accueil le jour de l'événement.</p>
                    @endif
                </div>
            </div>
            <p class="mt-5 text-center text-[12.5px] text-[#5B677A]">Envie de rejoindre le réseau ? <a href="{{ route('adhesion') }}" class="font-bold text-brand hover:underline">Devenir membre du REJCC</a></p>
        </div>
    </section>
</div>
