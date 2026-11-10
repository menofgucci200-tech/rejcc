{{-- Actions sur place d'une demande de communication (accueil de la Com) : Accepter, Planifier
     (date + responsable) ou Refuser (motif), sans changer de page.
     Paramètres : $d (ligne « a_traiter.demandes » du cockpit), $options (équipe), $demandeId, $demandeAction, $grand (bool). --}}
@php
    $grand ??= false;
    $ouverte = $demandeId === $d['id'];
    $champ = 'w-full rounded-[10px] border border-brand/15 bg-white px-3 py-2 text-[13px] focus:border-brand focus:outline-none';
    $taille = $grand ? 'px-4 py-2.5 text-[13.5px]' : 'px-3 py-1.5 text-[12px]';
@endphp
<div data-test="actions-demande-{{ $d['id'] }}">
    <div class="flex flex-wrap gap-1.5">
        <button type="button" wire:click="choisirDemande({{ $d['id'] }}, 'planifier', @js($d['date_souhaitee']))" data-test="demande-planifier"
            class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand font-bold text-white hover:bg-brand/90 {{ $taille }}">
            <x-ui.icon name="calendar" class="size-4" /> Planifier
        </button>
        @if ($d['statut'] === 'nouvelle')
            <button type="button" wire:click="accepter({{ $d['id'] }})" data-test="demande-accepter"
                class="btn-tap rounded-full border border-brand/20 bg-white font-bold text-brand hover:bg-cloud {{ $taille }}">Accepter</button>
            <button type="button" wire:click="choisirDemande({{ $d['id'] }}, 'refuser')" data-test="demande-refuser"
                class="btn-tap rounded-full font-bold text-accent hover:bg-accent/5 {{ $taille }}">Refuser</button>
        @endif
        <a href="{{ route('admin.demandes-communication', ['fiche' => $d['id']]) }}" wire:navigate class="btn-tap rounded-full font-bold text-[#5B677A] hover:text-brand {{ $taille }}">Voir le détail</a>
    </div>

    @if ($ouverte && $demandeAction === 'planifier')
        <form wire:submit="planifierDemande" class="panel-enter mt-2.5 grid gap-2 rounded-[12px] bg-[#F7F4FD] p-3 sm:grid-cols-[1fr_1.4fr_auto] sm:items-end" data-test="form-planifier-demande">
            <label class="block"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Date de publication</span>
                <input type="date" wire:model="demDate" required min="{{ now()->toDateString() }}" class="{{ $champ }}"></label>
            <label class="block"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Responsable</span>
                <select wire:model="demResponsable" class="{{ $champ }}">
                    <option value="">Celui du tour de rôle à cette date</option>
                    @foreach ($options['equipe'] ?? [] as $m)<option value="{{ $m['id'] }}">{{ $m['nom'] }}</option>@endforeach
                </select></label>
            <button type="submit" wire:loading.attr="disabled" data-test="confirmer-planifier-demande" class="btn-tap rounded-full bg-[#5B3FA8] px-4 py-2 text-[12.5px] font-bold text-white hover:bg-[#4A3290] disabled:opacity-60">Ajouter au calendrier</button>
            @error('demDate') <span class="text-xs text-accent sm:col-span-3">{{ $message }}</span> @enderror
            <p class="text-[11.5px] text-[#5B677A] sm:col-span-3">Le demandeur est prévenu par e-mail{{ $d['statut'] === 'nouvelle' ? ' ; planifier vaut acceptation' : '' }}.</p>
        </form>
    @endif

    @if ($ouverte && $demandeAction === 'refuser')
        <form wire:submit="refuserDemande" class="panel-enter mt-2.5 flex flex-col gap-2 rounded-[12px] bg-[#FDF7F7] p-3" data-test="form-refuser-demande">
            <label class="block"><span class="mb-1 block text-[11.5px] font-bold text-[#5B677A]">Motif du refus (transmis au demandeur)</span>
                <textarea wire:model="motif" rows="2" maxlength="2000" required placeholder="Ex. : le délai est trop court pour préparer un visuel de qualité." class="{{ $champ }}"></textarea></label>
            @error('motif') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            <div class="flex flex-wrap gap-2">
                <button type="submit" wire:loading.attr="disabled" data-test="confirmer-refuser-demande" class="btn-tap rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600 disabled:opacity-60">Refuser la demande</button>
                <button type="button" wire:click="fermerDemande" class="btn-tap rounded-full px-3 py-2 text-[12.5px] font-bold text-[#5B677A] hover:text-brand">Annuler</button>
            </div>
        </form>
    @endif
</div>
