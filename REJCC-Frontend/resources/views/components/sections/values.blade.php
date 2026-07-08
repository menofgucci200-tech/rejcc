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
                        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-white text-accent shadow-sm transition-transform duration-500 group-hover:scale-110">
                            <x-ui.icon :name="$v->icon" class="size-5.5" />
                        </span>
                        <h3 class="mt-5 font-display text-xl uppercase tracking-tight text-brand transition-colors duration-500 group-hover:text-white">{{ $v->title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink/70 transition-colors duration-500 group-hover:text-white/80">{{ $v->text }}</p>
                    </article>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
