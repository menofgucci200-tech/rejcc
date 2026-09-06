@props(['description' => null])

<div class="mx-auto flex max-w-[520px] flex-col items-center px-6 py-16 text-center">
    <div class="flex size-14 items-center justify-center rounded-2xl bg-[#F5A623]/10">
        <x-ui.icon name="shield" class="size-6 text-[#B27007]" />
    </div>
    <h2 class="mt-5 text-[16px] font-extrabold text-brand">{{ $slot->isEmpty() ? 'Fonctionnalité réservée aux abonnés' : $slot }}</h2>
    <p class="mt-2 text-[13.5px] leading-relaxed text-[#5B677A]">
        {{ $description ?? "Cette fonctionnalité est réservée aux membres à jour de leur abonnement annuel (10 000 F). Débloquez-la en réglant votre cotisation via Wave, Orange Money, MTN, Moov ou carte bancaire." }}
    </p>
    <a href="{{ route('espace-membre.abonnement') }}" wire:navigate class="btn-tap mt-6 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13px] font-bold text-white hover:bg-brand/90">
        <x-ui.icon name="shield-check" class="size-4" /> Payer mon abonnement (10 000 F)
    </a>
</div>
