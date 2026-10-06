@props(['photos'])
{{--
    Visionneuse plein écran partagée (page album, accueil, galerie).
    S'ouvre par l'événement « rj-visionneuse » : { index }. Flèches du
    clavier, Échap, glisser du doigt sur mobile ; compteur et légende.
--}}
<div
    x-data="{
        open: false,
        index: 0,
        startX: null,
        photos: @js(collect($photos)->map(fn ($p) => ['url' => $p['url'], 'caption' => $p['caption'] ?? ''])->values()),
        show(i) { this.index = i; this.open = true; document.documentElement.style.overflow = 'hidden'; this.$nextTick(() => this.$refs.fermer?.focus()); },
        close() { this.open = false; document.documentElement.style.overflow = ''; },
        next() { this.index = (this.index + 1) % this.photos.length; },
        prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
        swipe(e) { if (this.startX === null) return; const dx = e.changedTouches[0].clientX - this.startX; if (Math.abs(dx) > 45) { dx < 0 ? this.next() : this.prev(); } this.startX = null; },
    }"
    @rj-visionneuse.window="show($event.detail.index)"
    @keydown.escape.window="open && close()"
    @keydown.arrow-right.window="open && next()"
    @keydown.arrow-left.window="open && prev()"
>
    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-[120] flex flex-col bg-brand-900/[.97]"
         role="dialog" aria-modal="true" aria-label="Visionneuse de photos"
         @touchstart="startX = $event.touches[0].clientX" @touchend="swipe($event)">
        <div class="flex items-center justify-between px-4 py-3 text-white/80 sm:px-6">
            <span class="text-sm font-semibold tabular-nums"><span x-text="index + 1"></span> / <span x-text="photos.length"></span></span>
            <button type="button" x-ref="fermer" @click="close()" aria-label="Fermer" class="flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20">
                <x-ui.icon name="x" class="size-5" />
            </button>
        </div>
        <div class="relative flex min-h-0 flex-1 items-center justify-center px-3 pb-4 sm:px-20" @click.self="close()">
            <template x-for="(p, i) in photos" :key="i">
                <figure x-show="i === index" class="flex max-h-full flex-col items-center">
                    <img :src="p.url" :alt="p.caption || 'Photo REJCC'" :loading="Math.abs(i - index) <= 1 ? 'eager' : 'lazy'" class="max-h-[calc(100svh-9rem)] w-auto max-w-full rounded-lg object-contain shadow-2xl">
                    <figcaption x-show="p.caption" x-text="p.caption" class="mt-3 max-w-2xl text-center text-sm text-white/80"></figcaption>
                </figure>
            </template>
            <button type="button" x-show="photos.length > 1" @click="prev()" aria-label="Photo précédente" class="absolute left-2 top-1/2 hidden size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:flex sm:left-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" x-show="photos.length > 1" @click="next()" aria-label="Photo suivante" class="absolute right-2 top-1/2 hidden size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:flex sm:right-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </div>
</div>
