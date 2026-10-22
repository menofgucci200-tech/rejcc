<div>
    <x-member-light.topbar title="Mes sessions de formateur" />
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-8">
        <p class="mb-6 text-[13.5px] text-[#5B677A]">Les sessions live que vous animez. Le jour J, le bouton « Rejoindre la visio » apparaît une heure avant le début.</p>
        <div class="space-y-5">
            @forelse ($sessions as $s)
                <x-formations.espace-animateur :session="$s" />
            @empty
                <p class="rounded-[18px] border border-brand/10 bg-white px-5 py-10 text-center text-sm text-[#5B677A]">{{ $formateur ? 'Aucune session à venir pour le moment.' : 'Vous n\'êtes pas encore formateur.' }}</p>
            @endforelse
        </div>
    </div>
</div>
