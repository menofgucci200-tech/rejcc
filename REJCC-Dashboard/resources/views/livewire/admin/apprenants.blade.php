<div>
    <x-admin-light.topbar title="Apprenants" />

    <div class="mx-auto max-w-[1180px] px-4 py-8 sm:px-8">
        <section class="mb-6 flex flex-wrap items-center gap-4 rounded-[22px] border border-[#E8E1F7] bg-gradient-to-br from-[#F7F4FD] via-white to-[#FFF8F1] p-5">
            <x-ui.illustration name="livre" size="size-12" />
            <div class="min-w-[220px] flex-1">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#7A6BB8]">Suivi des apprenants</p>
                <h1 class="text-[20px] font-bold text-brand">{{ $formation['title'] }}</h1>
            </div>
            <a href="{{ route('admin.formations') }}" wire:navigate class="text-[12.5px] font-semibold text-[#5B677A] hover:text-brand">← Formations</a>
        </section>

        @if ($message)
            <p class="mb-4 rounded-[14px] border border-[#CDEBDD] bg-[#EFFAF4] px-4 py-3 text-[13px] font-semibold text-[#2E7D5B]">{{ $message }}</p>
        @endif

        @php
            $statuts = ['tous' => ['Tous', 'bg-cloud text-brand'], 'en_cours' => ['En cours', 'bg-[#EEF6FD] text-[#2D5C8F]'], 'inactif' => ['Inactifs (14 j)', 'bg-[#FDF2E3] text-[#8A5A12]'], 'bloque' => ["Bloqués à l'examen", 'bg-[#FDEEEE] text-[#A23B3B]'], 'termine' => ['Terminé', 'bg-[#EFFAF4] text-[#1C6B47]']];
        @endphp
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($statuts as $cle => [$libelle, $classes])
                <button type="button" wire:click="$set('filtre', '{{ $cle }}')" class="btn-tap rounded-full px-3.5 py-1.5 text-[12.5px] font-bold {{ $filtre === $cle ? 'ring-2 ring-brand/30 ' : '' }}{{ $classes }}">{{ $libelle }} · {{ $compteurs[$cle] ?? 0 }}</button>
            @endforeach
        </div>

        @if ($choisis)
            <div class="panel-enter mb-4 rounded-[16px] border border-[#E4E1EC] bg-white p-4" data-test="relance">
                <p class="mb-2 text-[13px] font-bold text-[#3B3F5C]">Relancer {{ count($choisis) }} apprenant(s)</p>
                <textarea wire:model="messageRelance" rows="2" maxlength="500" placeholder="Ex. On vous attend pour le module 2 ! Une session live a lieu jeudi à 18 h pour vos questions." class="w-full rounded-[10px] border border-[#E4E1EC] px-3 py-2 text-[13px] outline-none focus:border-azure"></textarea>
                <button type="button" wire:click="relancer" class="btn-tap mt-2 rounded-full bg-accent px-4 py-2 text-[12.5px] font-bold text-white hover:bg-accent-600">Envoyer la notification</button>
            </div>
        @endif

        <div class="overflow-x-auto rounded-[18px] border border-[#EDE9F3] bg-white">
            <table class="w-full min-w-[760px] text-[13px]">
                <thead class="border-b border-[#EDE9F3] text-left text-[11px] uppercase tracking-wide text-[#5B677A]">
                    <tr>
                        <th class="px-4 py-3"><input type="checkbox" wire:click="choisirTous(@js($apprenants->pluck('user_id')->all()))" @checked($apprenants->isNotEmpty() && count($choisis) === $apprenants->count()) class="size-4 rounded" aria-label="Tout choisir"></th>
                        <th class="px-2 py-3">Apprenant</th>
                        <th class="px-2 py-3">Progression</th>
                        <th class="px-2 py-3">Dernière activité</th>
                        <th class="px-2 py-3">Examen</th>
                        <th class="px-2 py-3">Statut</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apprenants as $a)
                        <tr class="border-t border-[#F1EEF6]" wire:key="app-{{ $a['user_id'] }}">
                            <td class="px-4 py-2.5"><input type="checkbox" wire:model.live="choisis" value="{{ $a['user_id'] }}" class="size-4 rounded"></td>
                            <td class="px-2 py-2.5"><span class="font-semibold text-brand">{{ $a['nom'] }}</span><span class="block text-[11.5px] text-[#5B677A]">{{ $a['email'] }}</span></td>
                            <td class="px-2 py-2.5">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-20 overflow-hidden rounded-full bg-cloud"><span class="block h-full rounded-full bg-azure" style="width: {{ $a['progress'] }}%"></span></span><span class="tabular-nums text-[#3D4A60]">{{ $a['progress'] }} %</span></div>
                            </td>
                            <td class="px-2 py-2.5 text-[#3D4A60]">{{ $a['activite_le'] ? \Carbon\Carbon::parse($a['activite_le'])->locale('fr')->diffForHumans() : '—' }}</td>
                            <td class="px-2 py-2.5 text-[#3D4A60]">{{ $a['examen'] === null ? '—' : ($a['examen']['reussi'] ? 'Réussi ('.$a['examen']['score'].' %)' : ($a['examen']['echecs'] ? $a['examen']['echecs'].' échec(s)' : 'À passer')) }}</td>
                            <td class="px-2 py-2.5"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statuts[$a['statut']][1] ?? '' }}">{{ $statuts[$a['statut']][0] ?? $a['statut'] }}</span></td>
                            <td class="px-4 py-2.5 text-right">
                                @php $tel = preg_replace('/\D+/', '', (string) $a['telephone']); @endphp
                                @if ($tel)
                                    <a href="https://wa.me/{{ strlen($tel) === 10 ? '225'.$tel : $tel }}?text={{ rawurlencode('Bonjour '.\Illuminate\Support\Str::before($a['nom'], ' ').", c'est le département Formation du REJCC. Comment avance votre formation « ".$formation['title']." » ? On peut vous aider ?") }}" target="_blank" rel="noopener" class="text-[12px] font-bold text-[#0F7B5F] hover:underline">WhatsApp</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-[#5B677A]">Aucun apprenant dans cette catégorie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
