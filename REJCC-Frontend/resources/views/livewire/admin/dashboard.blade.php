<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-brand">Bonjour, {{ \App\Support\Api::user()->prenom }} 👋</h1>
        <p class="mt-1 text-sm text-ink/60">Vue d'ensemble de la plateforme REJCC.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $c)
            <a href="{{ route($c['route']) }}" wire:navigate class="group flex items-center gap-4 rounded-2xl border border-brand/10 bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-[0_20px_50px_-30px_rgba(3,29,89,0.3)]">
                <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl {{ $c['color'] }} text-white">
                    <x-ui.icon :name="$c['icon']" class="size-5" />
                </span>
                <div>
                    <p class="text-2xl font-bold text-brand">{{ $c['value'] }}</p>
                    <p class="text-sm text-ink/60">{{ $c['label'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</div>
