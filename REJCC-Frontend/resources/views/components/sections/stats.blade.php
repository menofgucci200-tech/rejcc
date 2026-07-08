@php $stats = collect(\App\Support\Api::get('/home-content')['stats'] ?? [])->map(fn ($s) => (object) $s); @endphp

<section class="relative overflow-hidden bg-brand py-20">
    <div class="pointer-events-none absolute inset-0 bg-dots opacity-[0.07]"></div>
    <div class="pointer-events-none absolute left-1/2 top-0 h-px w-2/3 -translate-x-1/2 bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
    <x-ui.container>
        <dl class="grid grid-cols-2 gap-y-12 lg:grid-cols-4">
            @foreach ($stats as $i => $s)
                <x-ui.reveal :delay="$i * 0.08" class="text-center">
                    <dt class="font-display text-[clamp(2.75rem,6vw,4.5rem)] leading-none text-white">
                        <x-ui.counter :value="$s->value" :suffix="$s->suffix" />
                    </dt>
                    <dd class="mt-3 text-sm font-medium uppercase tracking-[0.12em] text-white/60">{{ $s->label }}</dd>
                </x-ui.reveal>
            @endforeach
        </dl>
    </x-ui.container>
</section>
