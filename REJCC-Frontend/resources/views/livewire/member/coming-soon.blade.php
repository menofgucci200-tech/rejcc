<div>
    <x-member-light.topbar :title="$title" />

    <div class="mx-auto flex max-w-[1280px] flex-col items-center justify-center px-8 py-24 text-center">
        <span class="mb-5 flex size-16 items-center justify-center rounded-2xl bg-brand/10 text-brand">
            <x-ui.icon :name="$icon" class="size-8" />
        </span>
        <h1 class="mb-2 text-xl font-bold text-brand">{{ $title }}</h1>
        <p class="max-w-md text-sm text-[#5B677A]">{{ $description }}</p>
        <p class="mt-6 inline-flex items-center gap-2 rounded-full bg-cloud px-4 py-2 text-xs font-semibold text-brand">
            <x-ui.icon name="clock" class="size-3.5" />
            Bientôt disponible
        </p>
    </div>
</div>
