@php $values = collect(\App\Support\Api::get('/home-content')['values'] ?? [])->map(fn ($v) => (object) $v); @endphp

<section class="bg-white py-24 sm:py-32">
    <x-ui.container>
        <x-ui.section-heading
            eyebrow="Nos valeurs"
            title="Ce qui nous fait avancer"
            subtitle="Cinq valeurs cardinales guident chaque action du réseau et de ses membres."
        />

        <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($values as $i => $v)
                <x-ui.reveal :delay="$i * 0.07" class="h-full">
                    <article class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-7 transition-all duration-500 hover:-translate-y-1.5 hover:bg-brand {{ $i === 0 ? 'sm:col-span-2 lg:col-span-1' : '' }}">
                        <h3 class="font-display text-[1.7rem] uppercase leading-none tracking-tight text-brand transition-colors duration-500 group-hover:text-white">{{ $v->title }}</h3>
                        <span class="mt-4 block h-0.5 w-8 bg-accent transition-all duration-500 group-hover:w-14"></span>
                        <p class="mt-4 text-sm leading-relaxed text-ink/70 transition-colors duration-500 group-hover:text-white/80">{{ $v->text }}</p>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
