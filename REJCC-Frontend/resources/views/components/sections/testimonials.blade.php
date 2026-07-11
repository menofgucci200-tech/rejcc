@php $testimonials = collect(\App\Support\Api::get('/testimonials')['testimonials'] ?? [])->map(fn ($t) => (object) $t); @endphp
@if ($testimonials->isNotEmpty())

<section class="bg-white py-24 sm:py-32">
    <x-ui.container>
        <x-ui.section-heading
            eyebrow="Témoignages"
            title="Des membres qui réussissent"
            subtitle="Ils ont rejoint le réseau et font grandir leurs projets, ensemble."
        />

        <div class="mt-16 grid gap-6 lg:grid-cols-3">
            @foreach ($testimonials as $i => $t)
                <x-ui.reveal :delay="$i * 0.1" class="h-full">
                    <figure class="group relative flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="size-9 text-accent/30 transition-colors duration-500 group-hover:text-accent/60"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
                        <blockquote class="mt-5 flex-1 font-serif text-lg italic leading-relaxed text-brand">{{ $t->quote }}</blockquote>
                        <figcaption class="mt-7 flex items-center gap-3.5 border-t border-brand/10 pt-6">
                            <span class="inline-flex size-12 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">{{ $t->initials }}</span>
                            <div>
                                <p class="font-semibold text-brand">{{ $t->name }}</p>
                                <p class="text-sm text-ink/60">{{ $t->role }}</p>
                            </div>
                        </figcaption>
                    </figure>
                </x-ui.reveal>
            @endforeach
        </div>
    </x-ui.container>
</section>
@endif
