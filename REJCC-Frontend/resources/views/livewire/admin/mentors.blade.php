<div>
    <x-admin-light.topbar title="Mentors" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="mb-1 text-[17px] font-bold text-brand">Mentors du réseau</h2>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-2 text-xs text-[#9AA6B8]">{{ $mentors->count() }} mentor{{ $mentors->count() > 1 ? 's' : '' }} — créez-en depuis « Nouvelle inscription » ou en changeant le rôle d'un membre</p>
            </div>
            <a href="{{ route('admin.inscription') }}" wire:navigate class="rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600">+ Inscrire un mentor</a>
        </div>

        <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            @forelse ($mentors as $m)
                <div class="flex flex-wrap items-center gap-4 border-t border-[#EDF0F5] py-3.5 first:border-t-0 {{ $m['actif'] ? '' : 'opacity-55' }}">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #F5A623, #F7C873)">{{ $m['initiales'] }}</span>
                    <div class="min-w-[200px] flex-1">
                        <p class="text-[13.5px] font-bold text-brand">{{ $m['nom'] }}</p>
                        <p class="text-xs text-[#5B677A]">{{ $m['email'] }} · {{ $m['telephone'] }}@if ($m['ville']) · {{ $m['ville'] }}@endif</p>
                        <p class="mt-0.5 text-[11px] text-[#9AA6B8]">Mentor depuis le {{ $m['depuis'] }}@if ($m['secteur']) · {{ $m['secteur'] }}@endif</p>
                    </div>
                    <a href="{{ route('admin.members') }}" wire:navigate class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">Gérer dans Membres</a>
                </div>
            @empty
                <p class="py-12 text-center text-sm text-[#5B677A]">Aucun mentor pour le moment. Inscrivez-en un via « Nouvelle inscription » (type Mentor).</p>
            @endforelse
        </div>
    </div>
</div>
