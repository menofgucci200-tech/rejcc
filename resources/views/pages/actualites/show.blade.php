<x-site-layout :title="$article->title">
    <x-page-header :eyebrow="$article->category" crumb="Actualités" :subtitle="$article->excerpt">
        {{ $article->title }}
    </x-page-header>

    <section class="bg-white py-20 sm:py-28">
        <x-ui.container class="max-w-3xl">
            <div class="flex items-center gap-4 text-sm text-ink/55">
                <span>{{ $article->author }}</span>
                <span>·</span>
                <span>{{ $article->published_at->locale('fr')->translatedFormat('d F Y') }}</span>
                <span>·</span>
                <span class="flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-3.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ $article->reading_time }}
                </span>
            </div>

            <div class="prose mt-8 max-w-none">
                @foreach ($article->body as $paragraph)
                    <p class="mb-5 text-pretty leading-relaxed text-ink/80">{{ $paragraph }}</p>
                @endforeach
            </div>

            <div class="mt-10">
                <x-ui.button href="/actualites" variant="outline">Retour aux actualités</x-ui.button>
            </div>
        </x-ui.container>
    </section>

    <x-sections.cta-band />
</x-site-layout>
