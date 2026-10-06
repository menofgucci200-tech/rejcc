@php
    use App\Support\Content\SiteRemote;

    $cta = \App\Support\Content\SiteConfig::ctaPrimary();
    $heroEyebrow = SiteRemote::field('home', 'hero', 'eyebrow', 'Réseau Entrepreneurial des Jeunes Chrétiens Catholiques');
    $heroSubtitle = SiteRemote::field('home', 'hero', 'subtitle', "Accéder à un réseau d'excellence pour entreprendre, grandir et réussir ensemble. Nous connectons les jeunes entrepreneurs catholiques pour co-créer des solutions durables, au service de l'Église et de la société.");

    // Chiffres clés (gérés dans l'administration, mêmes données que la bande « Chiffres »)
    $chiffres = collect(\App\Support\Api::get('/home-content')['stats'] ?? [])->take(3);

    $values = ['Foi', 'Excellence', 'Solidarité', 'Impact', 'Création de richesse'];
@endphp

{{--
    Hero « Autour de la table ».
    Ouverture : gros plan sur l'écran de la salle de réunion, où deux mains se
    rejoignent (vidéo) ; puis la caméra recule et révèle l'assemblée réunie
    autour de la table, légèrement floutée, et le titre apparaît.
    Pilotée par resources/js/home-motion.js. Sans JavaScript, ou si le visiteur
    a demandé à réduire les animations, la page affiche directement l'état final
    (salle floutée, poignée de main figée dans l'écran).
--}}
<section id="accueil" class="rj-hero relative flex min-h-[100svh] items-center overflow-hidden bg-brand pb-32 pt-28 lg:pt-24" data-hero-motion>
    <script>
        (function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.documentElement.classList.add('rj-motion');
            // Filet de sécurité : si le module d'animation ne démarre pas, on affiche le hero.
            var hero = document.currentScript.parentNode;
            setTimeout(function () { if (!window.__rjMotionStarted) hero.classList.add('is-live', 'is-cine-ready'); }, 3000);
        })();
    </script>

    <div class="rj-cine pointer-events-none absolute inset-0" aria-hidden="true" data-cine>
        <div class="rj-cine__scene" data-cine-scene>
            <div class="rj-cine__drift">
                <img
                    class="rj-cine__salle"
                    src="{{ asset('media/hero/salle-reunion.webp') }}"
                    srcset="{{ asset('media/hero/salle-reunion-864.webp') }} 864w, {{ asset('media/hero/salle-reunion.webp') }} 1728w"
                    sizes="110vw"
                    width="1728" height="1132" alt="" fetchpriority="high" decoding="async"
                >
                {{-- L'écran de la salle diffuse la poignée de main --}}
                <div class="rj-cine__ecran" data-cine-ecran>
                    <video
                        muted playsinline disablepictureinpicture disableremoteplayback preload="metadata"
                        poster="{{ asset('media/hero/poignee-fin.webp') }}"
                        data-poster-debut="{{ asset('media/hero/poignee-debut.webp') }}"
                        data-cine-video
                    >
                        <source src="{{ asset('media/hero/poignee-de-main.webm') }}" type="video/webm">
                        <source src="{{ asset('media/hero/poignee-de-main.mp4') }}" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
        <div class="rj-cine__voile"></div>
        <div class="rj-cine__grain"></div>
    </div>

    <div class="relative mx-auto flex w-full max-w-4xl flex-col items-center text-center container-px">
        <p class="rj-eyebrow flex items-center justify-center gap-3 text-[0.72rem] font-bold uppercase tracking-[0.22em] text-white/75">
            <span class="rj-anim rj-rule hidden h-px w-10 bg-accent sm:block" style="--d: .05s"></span>
            <span class="rj-mask"><span class="rj-anim rj-up block" style="--d: .15s">{{ $heroEyebrow }}</span></span>
            <span class="rj-anim rj-rule hidden h-px w-10 bg-accent sm:block" style="--d: .05s"></span>
        </p>

        <h1 class="mt-7 font-display uppercase leading-[0.92] tracking-[-0.01em] text-white text-[clamp(2.9rem,7vw,5.8rem)]">
            <span class="rj-mask block"><span class="rj-anim rj-up block" style="--d: .25s">Ensemble</span></span>
            <span class="rj-mask block pb-[0.06em]">
                <span class="rj-anim rj-up block" style="--d: .38s">pour <span class="font-serif normal-case tracking-normal text-azure">l'excellence</span><span class="text-accent">.</span></span>
            </span>
        </h1>

        <p class="rj-anim rj-soft mt-7 max-w-2xl text-pretty text-[1.05rem] leading-relaxed text-white/80 sm:text-lg" style="--d: .7s">
            {{ $heroSubtitle }}
        </p>

        <div class="rj-anim rj-soft mt-9 flex flex-wrap items-center justify-center gap-3" style="--d: .85s">
            <x-ui.button href="{{ url($cta['href']) }}" size="lg" variant="primary" :with-arrow="true">Rejoindre le réseau</x-ui.button>
            <x-ui.button href="/a-propos" size="lg" variant="ghost" class="text-white hover:bg-white/10">Découvrir le REJCC</x-ui.button>
        </div>

        @if ($chiffres->isNotEmpty())
            <dl class="rj-anim rj-soft mt-10 flex flex-wrap items-start justify-center gap-x-10 gap-y-5 border-t border-white/15 pt-7 sm:gap-x-14" style="--d: 1s">
                @foreach ($chiffres as $s)
                    <div class="flex flex-col-reverse items-center gap-1">
                        <dt class="text-[0.7rem] font-bold uppercase tracking-[0.16em] text-[#8fa3d9]">{{ $s['label'] }}</dt>
                        <dd class="font-display text-[clamp(1.9rem,3.4vw,2.6rem)] leading-none text-white tabular-nums">{{ $s['value'] }}{{ $s['suffix'] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>

    {{-- Bandeau des valeurs (charte : Foi, Excellence, Solidarité, Impact, Création de richesse) --}}
    <div class="rj-anim rj-fade absolute inset-x-0 bottom-0 overflow-hidden border-t border-white/10 py-4" style="--d: 1.3s" aria-label="Nos valeurs">
        <div class="rj-marquee flex w-max items-center">
            @foreach ([0, 1] as $copy)
                <ul class="flex shrink-0 items-center" @if ($copy) aria-hidden="true" @endif>
                    @foreach (array_merge($values, $values) as $value)
                        <li class="flex items-center font-display text-[clamp(1.4rem,2.6vw,2.2rem)] uppercase leading-none tracking-[0.01em] text-white/[0.16]">
                            <span class="px-8">{{ $value }}</span>
                            <span class="size-2 bg-accent"></span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</section>
