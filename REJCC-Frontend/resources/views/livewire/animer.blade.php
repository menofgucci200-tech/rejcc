<div wire:poll.60s>
    <section class="bg-[#FBF8F4] py-12 sm:py-16">
        <x-ui.container class="max-w-3xl">
            <p class="mb-4 text-[12px] font-bold uppercase tracking-[0.14em] text-[#7A6BB8]">Espace animateur · REJCC</p>
            @if ($session)
                <x-formations.espace-animateur :session="$session" />
                <p class="mt-4 text-center text-[12px] text-[#9A9DB0]">Ce lien vous est personnel : ne le partagez pas avec les participants.</p>
            @else
                <div class="rounded-[22px] border border-[#EDE9F3] bg-white p-8 text-center">
                    <p class="text-[16px] font-bold text-[#3B3F5C]">{{ $erreur ?? 'Ce lien d\'animateur n\'est plus valable.' }}</p>
                    <p class="mt-2 text-[13.5px] text-[#6B6F86]">Contactez le département Formation du REJCC pour recevoir un nouveau lien.</p>
                </div>
            @endif
        </x-ui.container>
    </section>
</div>
