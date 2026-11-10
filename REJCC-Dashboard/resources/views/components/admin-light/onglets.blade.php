@props(['groupe'])

{{-- Barre d'onglets des pages regroupées sous une seule entrée du menu
     (« Ressources », « Statistiques » : AdminNav::ONGLETS). Chaque onglet est
     une page à part entière avec sa propre adresse : les liens des e-mails et
     les favoris restent valides. Défilement horizontal propre sur téléphone. --}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminSections;

    $courante = \App\Support\RouteCourante::nom();
    $onglets = collect(AdminNav::ONGLETS[$groupe] ?? [])->filter(fn ($o) => AdminSections::allowedRoute(session('api_user'), $o['route']))->values();
@endphp

@if ($onglets->count() > 1)
    <nav aria-label="{{ ucfirst($groupe) }}" data-test="onglets-{{ $groupe }}" {{ $attributes->merge(['class' => 'no-print mb-5 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0']) }}>
        <div class="inline-flex min-w-max gap-1 rounded-[14px] border border-brand/10 bg-white p-1 shadow-[0_2px_8px_rgba(3,29,89,.04)]">
            @foreach ($onglets as $o)
                @php $actif = $courante === $o['route']; @endphp
                <a href="{{ route($o['route']) }}" wire:navigate data-test="onglet-{{ \Illuminate\Support\Str::slug($o['label']) }}" @if ($actif) aria-current="page" @endif
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-[10px] px-3.5 py-2 text-[12.5px] font-bold transition-colors {{ $actif ? 'bg-brand text-white' : 'text-[#5B677A] hover:bg-cloud hover:text-brand' }}">
                    <x-ui.icon :name="$o['icon']" class="size-4 shrink-0" />
                    {{ $o['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
