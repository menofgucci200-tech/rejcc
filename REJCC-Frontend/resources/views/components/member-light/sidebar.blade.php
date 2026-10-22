@props(['withClose' => false, 'reductible' => false])

{{-- Menu de l'espace membre : rubriques groupées, pastilles (messages non lus,
     actions de mentorat), carte utilisateur avec menu en bas. Sur ordinateur,
     le menu peut être réduit aux icônes (choix mémorisé dans le navigateur). --}}
@php
    use App\Support\NavCompteurs;

    $user = \App\Support\Api::user();
    $abonnementActif = $user->subscription_active ?? false;
    $estMentor = ($user->role ?? null) === 'mentor';
    $compteurs = NavCompteurs::get();

    $sections = [
        'Mon parcours' => [
            ['label' => 'Accueil', 'icon' => 'nav-home', 'route' => 'espace-membre.dashboard'],
            ['label' => $estMentor ? 'Ma carte mentor' : 'Ma carte membre', 'icon' => 'qr-code', 'route' => 'espace-membre.carte', 'locked' => true],
            ['label' => 'Mes formations', 'icon' => 'graduation-cap', 'route' => 'espace-membre.formations'],
            ['label' => 'Catalogue', 'icon' => 'nav-compass', 'route' => 'espace-membre.catalogue'],
            ['label' => 'Mes parcours', 'icon' => 'nav-route', 'route' => 'espace-membre.parcours'],
            ['label' => 'Certificats', 'icon' => 'award', 'route' => 'espace-membre.certificats'],
            ...(($user->est_formateur ?? false) ? [['label' => 'Mes sessions (formateur)', 'icon' => 'calendar-days', 'route' => 'espace-membre.sessions-animees']] : []),
        ],
        'Réseau' => [
            ['label' => 'Mentorat', 'icon' => 'hand-heart', 'route' => 'espace-membre.mentorat', 'badge' => $compteurs['mentorat'] ?? 0],
            ['label' => 'Annuaire', 'icon' => 'users', 'route' => 'espace-membre.directory', 'locked' => true],
            ['label' => 'Groupes sectoriels', 'icon' => 'network', 'route' => 'espace-membre.groupes', 'badge' => $compteurs['groupes'] ?? 0],
            ['label' => 'Messagerie', 'icon' => 'message-circle', 'route' => 'espace-membre.messaging', 'cle' => 'messages', 'badge' => $compteurs['messages'] ?? 0],
            ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'espace-membre.evenements'],
        ],
        'Opportunités' => [
            ['label' => 'Marketplace', 'icon' => 'store', 'route' => 'espace-membre.marketplace'],
            ['label' => 'Projets', 'icon' => 'nav-projects', 'route' => 'espace-membre.projets', 'locked' => true],
            ['label' => 'Emploi & Stage', 'icon' => 'nav-briefcase', 'route' => 'espace-membre.emplois'],
        ],
        'Ressources' => [
            ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'espace-membre.documents'],
        ],
    ];

    $isActive = fn (array $item) => request()->routeIs($item['route'].'*');

    // Pastille d'abonnement de la carte utilisateur.
    $statut = match (true) {
        (bool) ($user->subscription_exempt ?? false) => ['Dispensé d\'abonnement', 'bg-[#EAF5EF] text-[#17734B]'],
        ! ($user->subscriptions_enforced ?? true) => ['Accès libre', 'bg-[#EAF5EF] text-[#17734B]'],
        (bool) ($user->subscription_grace ?? false) => ['Délai de grâce'.(($user->subscription_grace_fin ?? null) ? ' · '.\Carbon\Carbon::parse($user->subscription_grace_fin)->translatedFormat('j M') : ''), 'bg-[#FBF1DC] text-[#8A5A00]'],
        (bool) ($user->subscription_paid ?? false) => ['À jour'.(($user->subscription_expires_at ?? null) ? ' · '.\Carbon\Carbon::parse($user->subscription_expires_at)->translatedFormat('j M Y') : ''), 'bg-[#EAF5EF] text-[#17734B]'],
        default => ['Abonnement non actif', 'bg-[#FBF1DC] text-[#8A5A00]'],
    };
    $initiales = mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1));
@endphp

<aside
    @if ($reductible) x-data="{ reduit: $persist(false).as('rejcc-menu-reduit') }" :class="reduit ? 'w-[76px]' : 'w-[248px]'" @endif
    {{ $attributes->merge(['class' => 'relative flex h-full shrink-0 flex-col border-r border-[#ECE5DA] bg-white text-brand transition-[width] duration-300 ease-out'.($reductible ? '' : ' w-[248px]')]) }}
    data-test="menu-membre"
>
    {{-- En-tête --}}
    <div class="flex items-center gap-3 border-b border-[#EFEAE1] px-[18px] py-[18px]">
        <a href="{{ route('espace-membre.dashboard') }}" wire:navigate class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-[#F6EEE3]" aria-label="Accueil de l'espace membre">
            <img src="{{ asset('brand/rejcc-monogram-color.png') }}" alt="REJCC" class="size-[26px] object-contain">
        </a>
        <div class="min-w-0 flex-1" @if ($reductible) x-show="! reduit" @endif>
            <p class="text-[15px] font-extrabold tracking-[0.04em]">REJCC</p>
            <p class="text-[10px] font-semibold tracking-[0.08em] {{ $estMentor ? 'text-accent' : 'text-[#5B677A]' }}">{{ $estMentor ? 'ESPACE MENTOR' : 'ESPACE MEMBRE' }}</p>
        </div>
        @if ($withClose)
            <button type="button" @click="mobileOpen = false" aria-label="Fermer le menu" class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] border border-[#E7E1D6] text-brand">
                <x-ui.icon name="x" class="size-4" />
            </button>
        @endif
    </div>

    {{-- Rubriques --}}
    <nav aria-label="Menu de l'espace membre" class="flex flex-1 flex-col overflow-y-auto overflow-x-hidden px-3 pb-3 pt-2">
        @foreach ($sections as $titre => $items)
            <p class="mb-1 mt-3 px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-[#5B677A]" @if ($reductible) x-show="! reduit" @endif>{{ $titre }}</p>
            @if ($reductible)
                <div x-show="reduit" class="mx-auto my-2 h-px w-6 bg-[#EFEAE1]" style="display: none"></div>
            @endif
            @foreach ($items as $item)
                @php
                    $active = $isActive($item);
                    $verrouille = ($item['locked'] ?? false) && ! $abonnementActif;
                    $badge = (int) ($item['badge'] ?? 0);
                @endphp
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    title="{{ $item['label'] }}{{ $verrouille ? ' — réservé aux abonnés' : '' }}{{ $badge ? ' ('.$badge.')' : '' }}"
                    data-test="menu-{{ \Illuminate\Support\Str::slug($item['label']) }}"
                    @if ($active) aria-current="page" @endif
                    class="group relative flex items-center gap-3 rounded-[10px] px-3 py-[9px] text-[13.5px] font-medium transition-colors duration-200 {{ $active ? 'bg-[#F6EEE3] font-semibold text-brand shadow-[inset_3px_0_0_var(--color-accent)]' : 'text-[#3D4A60] hover:bg-[#FAF6EF] hover:text-brand' }}"
                    @if ($reductible) :class="reduit ? 'justify-center !px-0' : ''" @endif
                >
                    <span class="relative shrink-0">
                        <x-ui.icon :name="$item['icon']" class="size-[18px] transition-transform duration-200 group-hover:scale-110" />
                        @if ($badge && $reductible)
                            <span x-show="reduit" style="display: none" class="absolute -right-1.5 -top-1.5 size-2.5 rounded-full border-2 border-white bg-accent"></span>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1 truncate" @if ($reductible) x-show="! reduit" @endif>{{ $item['label'] }}</span>
                    @if (($item['cle'] ?? null) === 'messages' && ! $verrouille)
                        {{-- Pastille mise à jour en direct par la messagerie (évènement compteur-messages) --}}
                        <span x-data="{ n: {{ $badge }} }" x-on:compteur-messages.window="n = $event.detail.n" x-show="n > 0{{ $reductible ? ' && ! reduit' : '' }}" @if (! $badge) style="display: none" @endif
                            data-test="pastille" class="shrink-0 rounded-full bg-accent px-1.5 py-px text-[10.5px] font-bold leading-4 text-white" x-text="n > 99 ? '99+' : n">{{ $badge > 99 ? '99+' : $badge }}</span>
                    @elseif ($badge)
                        <span data-test="pastille" class="shrink-0 rounded-full bg-accent px-1.5 py-px text-[10.5px] font-bold leading-4 text-white" @if ($reductible) x-show="! reduit" @endif>{{ $badge > 99 ? '99+' : $badge }}</span>
                    @elseif ($verrouille)
                        <span class="shrink-0" @if ($reductible) x-show="! reduit" @endif><x-ui.icon name="lock" class="size-[13px] text-[#B27007]" /></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- Carte utilisateur + menu --}}
    <div x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false" class="relative border-t border-[#EFEAE1] p-3">
        <div x-show="menu" x-transition.origin.bottom style="display: none" data-test="menu-utilisateur" class="absolute bottom-[calc(100%-4px)] left-3 right-3 z-20 min-w-[220px] overflow-hidden rounded-[14px] bg-white py-1.5 text-[13px] text-ink shadow-[0_20px_50px_-12px_rgba(3,29,89,.55)]">
            @foreach ([
                ['Mon profil', 'user', route('espace-membre.profile')],
                [$estMentor ? 'Ma carte mentor' : 'Ma carte membre', 'qr-code', route('espace-membre.carte')],
                ['Mon abonnement', 'shield-check', route('espace-membre.abonnement')],
            ] as [$label, $icon, $href])
                <a href="{{ $href }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon :name="$icon" class="size-4 text-[#5B677A]" /> {{ $label }}</a>
            @endforeach
            @if (($user->role ?? null) === 'admin')
                <a href="{{ config('services.dashboard.url') }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="layout-dashboard" class="size-4 text-[#5B677A]" /> Administration</a>
            @endif
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 px-4 py-2 font-semibold text-brand hover:bg-cloud"><x-ui.icon name="globe" class="size-4 text-[#5B677A]" /> Retour au site</a>
            <div class="my-1 h-px bg-cloud-200"></div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 font-semibold text-accent hover:bg-accent/5"><x-ui.icon name="log-out" class="size-4" /> Se déconnecter</button>
            </form>
        </div>

        <button type="button" @click="menu = ! menu" :aria-expanded="menu" data-test="carte-utilisateur"
            class="flex w-full items-center gap-2.5 rounded-[12px] p-1.5 text-left transition-colors hover:bg-[#FAF6EF]"
            @if ($reductible) :class="reduit ? 'justify-center' : ''" @endif>
            <span x-data="{ erreur: false }" class="relative shrink-0">
                @if ($user->photo ?? null)
                    <img x-show="! erreur" x-on:error="erreur = true" x-init="$el.complete && ! $el.naturalWidth && (erreur = true)" src="{{ $user->photo }}" alt="" class="size-10 rounded-full object-cover ring-2 ring-white">
                @endif
                <span x-show="{{ ($user->photo ?? null) ? 'erreur' : 'true' }}" @if ($user->photo ?? null) style="display: none" @endif class="flex size-10 items-center justify-center rounded-full text-xs font-bold text-white ring-2 ring-white" style="background: linear-gradient(135deg, {{ $estMentor ? '#AC0100, #D95B5A' : '#4F6FBF, #AC0100' }})">{{ $initiales }}</span>
            </span>
            <span class="min-w-0 flex-1" @if ($reductible) x-show="! reduit" @endif>
                <span class="flex items-center gap-1.5">
                    <span class="truncate text-[13px] font-bold text-brand">{{ $user->prenom }} {{ $user->nom }}</span>
                    @if ($estMentor)
                        <span class="shrink-0 rounded-full bg-accent px-1.5 py-px text-[9px] font-bold uppercase tracking-[0.06em] text-white">Mentor</span>
                    @endif
                </span>
                <span data-test="statut-abonnement" class="mt-0.5 inline-block max-w-full truncate rounded-full px-2 py-px text-[10px] font-bold {{ $statut[1] }}">{{ $statut[0] }}</span>
            </span>
            <x-ui.icon name="chevron-down" class="size-4 shrink-0 rotate-180 text-[#5B677A]" x-show="! {{ $reductible ? 'reduit' : 'false' }}" />
        </button>

        @if ($reductible)
            <button type="button" @click="reduit = ! reduit" :aria-label="reduit ? 'Déplier le menu' : 'Réduire le menu'" :title="reduit ? 'Déplier le menu' : 'Réduire le menu'" data-test="reduire-menu"
                class="mt-2 flex w-full items-center justify-center gap-2 rounded-[9px] py-1.5 text-[11.5px] font-semibold text-[#5B677A] transition-colors hover:bg-[#FAF6EF] hover:text-brand">
                <x-ui.icon name="panel-left" class="size-4" /> <span x-show="! reduit">Réduire le menu</span>
            </button>
        @endif
    </div>
</aside>
