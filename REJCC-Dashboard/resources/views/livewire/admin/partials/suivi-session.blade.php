{{-- Panneaux de suivi d'une session live : émargement, après-session, message WhatsApp. --}}
@if ($panneauDe === $s['id'] && $panneau === 'emargement')
    <div class="panel-enter mt-3 rounded-[14px] bg-[#FDFBF7] p-4" data-test="emargement">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <p class="flex-1 text-[13px] font-bold text-ink">Émargement · {{ collect($emargement)->where('present', true)->count() }} présent(s) sur {{ count($emargement) }} inscrit(s)</p>
            <button type="button" wire:click="presentsDepuisRejoindre" class="btn-tap rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-bold text-white hover:bg-brand/90">Présents : ceux qui ont cliqué « Rejoindre »</button>
            <a href="{{ route('admin.lives.emargement', $s['id']) }}" class="btn-tap rounded-full border border-[#E7E1D6] bg-white px-3.5 py-1.5 text-[12px] font-bold text-ink hover:bg-[#FAF6EF]">Exporter (Excel)</a>
        </div>
        @if ($s['attestation'])
            <p class="mb-2 text-[12px] text-[#5B677A]">Les présents reçoivent automatiquement leur attestation de présence.</p>
        @endif
        @forelse ($emargement as $i)
            <label class="flex flex-wrap items-center gap-3 border-t border-[#EFEAE1] py-2 text-[12.5px] first:border-t-0" wire:key="em-{{ $i['id'] }}">
                <input type="checkbox" @checked($i['present']) wire:click="basculerPresence({{ $i['id'] }}, {{ $i['present'] ? 'false' : 'true' }})" class="size-4 rounded">
                <span class="min-w-0 flex-1 font-semibold text-ink">{{ $i['nom'] }}</span>
                @if ($i['rejoint_at'])
                    <span class="rounded-full bg-[#EFFAF4] px-2 py-0.5 text-[11px] font-bold text-[#1C6B47]">a rejoint à {{ \Carbon\Carbon::parse($i['rejoint_at'])->format('H\hi') }}</span>
                @endif
                <span class="text-[#5B677A]">{{ $i['telephone'] }}</span>
            </label>
        @empty
            <p class="text-[12.5px] text-[#5B677A]">Personne n'était inscrit.</p>
        @endforelse
    </div>
@endif

@if ($panneauDe === $s['id'] && $panneau === 'apres')
    <div class="panel-enter mt-3 rounded-[14px] bg-[#FDFBF7] p-4" data-test="apres-session">
        <p class="mb-1 text-[13px] font-bold text-ink">Après la session</p>
        <p class="mb-3 text-[12px] text-[#5B677A]">Ces liens apparaissent sur la fiche de la session, pour ceux qui l'ont manquée ou veulent la revoir.</p>
        <div class="grid gap-2 sm:grid-cols-2">
            <input wire:model="replayUrl" type="url" placeholder="Lien du replay (YouTube non répertorié, Drive…)" class="rounded-[10px] border border-[#E7E1D6] bg-white px-3 py-2 text-[13px] outline-none focus:border-azure">
            <input wire:model="supportUrl" type="url" placeholder="Lien du support (PDF, présentation…)" class="rounded-[10px] border border-[#E7E1D6] bg-white px-3 py-2 text-[13px] outline-none focus:border-azure">
        </div>
        <label class="mt-2 flex items-center gap-2 text-[12.5px] text-ink"><input type="checkbox" wire:model="prevenirApres" class="size-4 rounded"> Prévenir les inscrits</label>
        <button type="button" wire:click="enregistrerApres" class="btn-tap mt-3 rounded-full bg-brand px-4 py-2 text-[12.5px] font-bold text-white hover:bg-brand/90">Enregistrer</button>
    </div>
@endif

@if ($panneauDe === $s['id'] && $panneau === 'whatsapp')
    @php
        $lienSession = \App\Support\Site::url('/espace-membre/evenements?evenement='.$s['id']);
        $texteWa = "📚 *Session live REJCC : {$s['title']}*\n"
            .'🗓 '.ucfirst($s['debut']->isoFormat('dddd D MMMM [à] HH[h]mm')).($s['animateur'] ? "\n👤 Avec {$s['animateur']}" : '')
            ."\n🎥 En visio (Google Meet)\n\nInscrivez-vous et rejoignez la session depuis la plateforme :\n{$lienSession}"
            ."\n\nLe jour J, cliquez sur « Rejoindre la visio » 5 minutes avant.";
    @endphp
    <div class="panel-enter mt-3 rounded-[14px] border border-[#CDEBDD] bg-[#F4FBF7] p-4" data-test="message-whatsapp" x-data="{ copie: false }">
        <p class="mb-2 text-[13px] font-bold text-[#1C6B47]">Message à partager dans les groupes WhatsApp</p>
        <textarea readonly rows="8" class="w-full rounded-[10px] border border-[#CDEBDD] bg-white px-3 py-2 text-[12.5px] text-[#2F4A3C]">{{ $texteWa }}</textarea>
        <div class="mt-2 flex flex-wrap gap-2">
            <button type="button" x-on:click="navigator.clipboard.writeText(@js($texteWa)); copie = true; setTimeout(() => copie = false, 2000)" class="btn-tap rounded-full bg-[#0F7B5F] px-4 py-2 text-[12.5px] font-bold text-white hover:bg-[#0C6A51]" x-text="copie ? 'Copié !' : 'Copier le message'">Copier le message</button>
            <a href="https://wa.me/?text={{ rawurlencode($texteWa) }}" target="_blank" rel="noopener" class="btn-tap rounded-full border border-[#CDEBDD] bg-white px-4 py-2 text-[12.5px] font-bold text-[#1C6B47] hover:bg-[#EFFAF4]">Ouvrir dans WhatsApp</a>
        </div>
    </div>
@endif
