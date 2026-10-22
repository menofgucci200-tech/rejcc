{{-- Champ mot de passe avec bascule afficher/masquer (œil). Transmet wire:model,
     id, autocomplete, placeholder et classes via $attributes. --}}
<div x-data="{ show: false }" class="relative">
    <input
        x-bind:type="show ? 'text' : 'password'"
        type="password"
        {{ $attributes->merge(['class' => 'pr-11']) }}
    />
    <button
        type="button"
        tabindex="-1"
        x-on:click="show = !show"
        x-bind:aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#9AA6B8] transition-colors hover:text-brand"
    >
        <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" /><circle cx="12" cy="12" r="3" />
        </svg>
        <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" /><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" /><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" /><path d="m2 2 20 20" />
        </svg>
    </button>
</div>
