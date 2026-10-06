@php
    // Page autonome : elle doit s'afficher même si la session ou l'API sont en panne.
    $code = $code ?? 500;
    $connecte = false;
    $admin = false;
    try {
        $u = \App\Support\Api::user();
        $connecte = (bool) $u;
        $admin = ($u->role ?? null) === 'admin';
    } catch (\Throwable) {
    }
    $assets = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
    // Seuls nos propres messages (en français) sont affichés, jamais les messages techniques du framework.
    $detail = in_array($code, [403, 404], true) && isset($exception) ? trim((string) $exception->getMessage()) : '';
    if ($detail !== '' && preg_match('/^(The |No query results|Not Found|Forbidden|This action is unauthorized)|could not be found/i', $detail)) {
        $detail = '';
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>@yield('titre') · REJCC</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        @if ($assets)
            @vite(['resources/css/app.css'])
        @endif
        <style>
            /* Repli si les styles compilés sont indisponibles. */
            body { margin: 0; font-family: Manrope, system-ui, sans-serif; background: #f4f6f8; color: #333; }
            .rj-err-code { font-family: Anton, Impact, sans-serif; }
        </style>
    </head>
    <body class="min-h-screen bg-cloud font-sans text-ink antialiased">
        <div class="relative flex min-h-screen flex-col overflow-hidden">
            {{-- Décor : halos aux couleurs de la charte --}}
            <div aria-hidden="true" class="pointer-events-none absolute -left-40 -top-40 size-[520px] rounded-full bg-azure/15 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-48 -right-32 size-[520px] rounded-full bg-accent/10 blur-3xl"></div>

            <header class="relative z-10 mx-auto flex w-full max-w-[1100px] items-center justify-between px-5 py-5 sm:px-8">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3" aria-label="REJCC — accueil">
                    <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="" class="size-11 object-contain" style="width:44px;height:44px">
                    <span class="leading-tight">
                        <span class="block text-[17px] font-bold tracking-wide text-brand">REJCC</span>
                        <span class="hidden text-[11.5px] text-[#5B677A] sm:block">Réseau Entrepreneurial des Jeunes Chrétiens Catholiques</span>
                    </span>
                </a>
                <a href="{{ url('/contact') }}" class="text-[13px] font-semibold text-brand hover:text-accent">Aide &amp; contact</a>
            </header>

            <main class="relative z-10 mx-auto flex w-full max-w-[1100px] flex-1 items-center px-5 pb-16 sm:px-8">
                <div class="grid w-full items-center gap-10 md:grid-cols-[1fr_1.1fr]">
                    <div class="order-2 md:order-1">
                        <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11.5px] font-bold uppercase tracking-[0.08em] text-accent shadow-sm">
                            <span class="size-1.5 rounded-full bg-accent"></span> Erreur {{ $code }}
                        </p>
                        <h1 class="text-[28px] font-bold leading-tight text-brand sm:text-[34px]">@yield('titre')</h1>
                        <div class="mt-2 h-[3px] w-10 rounded bg-accent"></div>
                        <p class="mt-4 max-w-md text-[15px] leading-relaxed text-[#5B677A]">@yield('message')</p>
                        @if ($detail)
                            <p data-test="detail-erreur" class="mt-4 max-w-md rounded-[12px] border border-brand/10 bg-white px-4 py-3 text-[13px] font-semibold text-brand">{{ $detail }}</p>
                        @endif
                        <div class="mt-7 flex flex-wrap gap-2.5">
                            @hasSection('actions')
                                @yield('actions')
                            @else
                                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-accent-600">Retour à l'accueil</a>
                            @endif
                            @if ($connecte)
                                <a href="{{ $admin ? url('/admin') : url('/espace-membre') }}" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-5 py-2.5 text-[13.5px] font-bold text-brand hover:bg-cloud">{{ $admin ? 'Administration' : 'Mon espace membre' }}</a>
                            @endif
                            <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')" class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-[13.5px] font-bold text-[#5B677A] hover:text-brand">← Page précédente</button>
                        </div>
                    </div>
                    <div class="order-1 flex justify-center md:order-2" aria-hidden="true">
                        <div class="relative">
                            <p class="rj-err-code select-none font-display text-[120px] leading-none tracking-wide text-brand sm:text-[190px]">
                                {{ substr((string) $code, 0, 1) }}<span class="text-accent">{{ substr((string) $code, 1, 1) }}</span>{{ substr((string) $code, 2) }}
                            </p>
                            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="" class="absolute -bottom-4 -right-4 size-16 rotate-[-8deg] rounded-2xl bg-white p-2.5 shadow-[0_12px_30px_rgba(3,29,89,.15)] sm:size-20" style="width:64px;height:64px">
                        </div>
                    </div>
                </div>
            </main>

            <footer class="relative z-10 border-t border-brand/10 bg-white/60 px-5 py-4 text-center text-[12px] text-[#5B677A]">
                © {{ date('Y') }} REJCC · Réseau Entrepreneurial des Jeunes Chrétiens Catholiques
            </footer>
        </div>
    </body>
</html>
