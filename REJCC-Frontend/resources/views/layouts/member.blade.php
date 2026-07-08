<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Espace membre · REJCC</title>

        <script>
            (function () {
                var saved = localStorage.getItem('rejcc-theme');
                document.documentElement.dataset.msPreload = saved === 'light' ? 'light' : 'dark';
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="antialiased">
        @php
            $user = \App\Support\Api::user();
            $unreadNotifications = \App\Support\Api::get('/notifications', [], \App\Support\Api::token())['unread'] ?? 0;
            $navItems = [
                ['icon' => 'layout-dashboard', 'label' => 'Accueil', 'route' => 'espace-membre.dashboard'],
                ['icon' => 'users', 'label' => 'Annuaire', 'route' => 'espace-membre.directory'],
                ['icon' => 'message-circle', 'label' => 'Messagerie', 'route' => 'espace-membre.messaging'],
                ['icon' => 'bell', 'label' => 'Notifications', 'route' => 'espace-membre.notifications'],
                ['icon' => 'folder-open', 'label' => 'Documents', 'route' => 'espace-membre.documents'],
            ];
        @endphp

        <div
            class="ms-shell fixed inset-0 z-[100] flex overflow-hidden bg-[var(--ms-shell-bg)]"
            x-data="{
                theme: document.documentElement.dataset.msPreload || 'dark',
                sidebarMode: 'full',
                mobileOpen: false,
                updateSidebarMode() {
                    const w = window.innerWidth;
                    this.sidebarMode = w >= 1180 ? 'full' : (w >= 720 ? 'rail' : 'off');
                },
                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('rejcc-theme', this.theme);
                },
            }"
            x-init="updateSidebarMode(); window.addEventListener('resize', () => updateSidebarMode())"
            :data-ms-theme="theme"
        >
            <!-- Ambient orbs -->
            <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
                <div class="absolute -top-[25%] left-[35%] size-[55vw] animate-aurora rounded-full" style="background: radial-gradient(circle, rgba(79,111,191,0.13) 0%, transparent 68%)"></div>
                <div class="absolute -right-[8%] bottom-[5%] size-[38vw] animate-aurora rounded-full" style="background: radial-gradient(circle, rgba(172,1,0,0.07) 0%, transparent 68%); animation-direction: reverse;"></div>
            </div>

            <!-- Sidebar (desktop/tablet) -->
            <div
                x-show="sidebarMode !== 'off'"
                :style="{ width: sidebarMode === 'full' ? '268px' : '76px' }"
                class="z-[1] h-full shrink-0 transition-[width] duration-250"
            >
                <x-member.sidebar :nav-items="$navItems" :user="$user" />
            </div>

            <!-- Mobile sidebar overlay -->
            <div x-show="mobileOpen && sidebarMode === 'off'" class="absolute inset-0 z-50 flex" style="display: none;">
                <div class="absolute inset-0 bg-black/55" @click="mobileOpen = false"></div>
                <div class="relative z-[1] h-full w-[268px]">
                    <x-member.sidebar :nav-items="$navItems" :user="$user" @click="mobileOpen = false" />
                </div>
                <button @click="mobileOpen = false" class="absolute right-5 top-5 flex size-9 items-center justify-center rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf2)] text-[var(--ms-text)]">
                    <x-ui.icon name="x" class="size-4" />
                </button>
            </div>

            <!-- Main area -->
            <div class="z-[1] flex min-w-0 flex-1 flex-col overflow-hidden">
                <!-- Header -->
                <header class="flex h-[72px] shrink-0 items-center gap-3.5 border-b border-[var(--ms-bc)] bg-[var(--ms-header-bg)] px-7">
                    <button
                        x-show="sidebarMode === 'off'"
                        @click="mobileOpen = true"
                        class="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] text-[var(--ms-text)]"
                        style="display: none;"
                    >
                        <x-ui.icon name="menu" class="size-[17px]" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-serif text-[19px] italic text-[var(--ms-text)]">
                            Bonjour, {{ $user->prenom }} 👋
                        </p>
                    </div>

                    <div class="hidden h-[38px] w-60 shrink-0 items-center gap-2 rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] px-3 sm:flex">
                        <x-ui.icon name="search" class="size-[13px] shrink-0 text-[var(--ms-dim)]" />
                        <input type="search" placeholder="Rechercher… ⌘K" class="w-full bg-transparent text-[13px] text-[var(--ms-text)] outline-none placeholder:text-[var(--ms-dim)]" />
                    </div>

                    <button
                        @click="toggleTheme"
                        :title="theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'"
                        class="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] text-[var(--ms-muted)] transition-colors hover:bg-[var(--ms-surf2)] hover:text-[var(--ms-text)]"
                    >
                        <x-ui.icon x-show="theme === 'dark'" name="sun" class="size-4" />
                        <x-ui.icon x-show="theme !== 'dark'" name="moon" class="size-4" style="display: none;" />
                    </button>

                    <a href="{{ route('espace-membre.notifications') }}" wire:navigate class="relative flex shrink-0">
                        <span class="flex size-[38px] items-center justify-center rounded-[10px] border border-[var(--ms-bc)] bg-[var(--ms-surf)] text-[var(--ms-muted)]">
                            <x-ui.icon name="bell" class="size-4" />
                        </span>
                        @if ($unreadNotifications > 0)
                            <span class="absolute right-1.5 top-1.5 size-2 animate-aurora rounded-full border-[1.5px] border-[#021541] bg-accent"></span>
                        @endif
                    </a>

                    <div class="relative flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-white/14 font-semibold text-white" style="background: linear-gradient(135deg, #4F6FBF, #031D59)">
                        <span class="text-[13px] tracking-wide">{{ mb_substr($user->prenom, 0, 1) }}{{ mb_substr($user->nom, 0, 1) }}</span>
                        <span class="absolute bottom-px right-px size-[9px] rounded-full border-[1.5px] border-[#021541] bg-[#34D399]"></span>
                    </div>
                </header>

                <!-- Content -->
                <main class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden bg-[var(--ms-main-bg)]">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
