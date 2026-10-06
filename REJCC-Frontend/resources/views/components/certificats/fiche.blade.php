@props(['c'])
{{-- Informations du registre officiel (jamais celles du document présenté). --}}
@php
    $d = $c['details'] ?? [];
    $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->locale('fr')->isoFormat('D MMMM YYYY') : null;
    $lignes = array_filter([
        'Titulaire' => $c['nom'],
        'Document' => $c['intitule'],
        $c['type'] === 'evenement' ? 'Événement' : ($c['type'] === 'parcours' ? 'Parcours' : 'Formation') => $c['titre'],
        'Date de l\'événement' => $date($d['date_evenement'] ?? null),
        'Lieu' => $d['lieu_evenement'] ?? null,
        'Durée' => $d['duree'] ?? null,
        'Modules' => ! empty($d['modules']) ? $d['modules'] : null,
        'Résultat à l\'examen' => isset($d['score']) ? $d['score'].' %' : null,
        'Délivré le' => $date($c['delivre_le']).($c['lieu'] ? ', à '.$c['lieu'] : ''),
        'Référence' => $c['reference'],
        'Code de vérification' => $c['code'],
    ]);
    $liste = $c['type'] === 'parcours' ? ($d['formations'] ?? []) : ($d['competences'] ?? []);
@endphp
<dl {{ $attributes->merge(['class' => 'grid gap-x-6 gap-y-3 sm:grid-cols-2']) }}>
    @foreach ($lignes as $k => $v)
        <div class="{{ in_array($k, ['Titulaire', 'Formation', 'Événement', 'Parcours'], true) ? 'sm:col-span-2' : '' }}">
            <dt class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">{{ $k }}</dt>
            <dd class="mt-0.5 {{ $k === 'Titulaire' ? 'font-serif text-[22px] text-brand' : 'text-[14px] font-semibold text-brand' }}" @if (in_array($k, ['Référence', 'Code de vérification'], true)) style="letter-spacing:.04em" @endif>{{ $v }}</dd>
        </div>
    @endforeach
    @if ($liste)
        <div class="sm:col-span-2">
            <dt class="text-[10.5px] font-bold uppercase tracking-[0.1em] text-[#9AA6B8]">{{ $c['type'] === 'parcours' ? 'Formations du parcours' : 'Compétences validées' }}</dt>
            <dd class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13.5px] font-semibold text-brand">
                @foreach ($liste as $i => $x)
                    @if ($i)<span class="inline-block size-1.5 rotate-45 bg-accent"></span>@endif<span>{{ $x }}</span>
                @endforeach
            </dd>
        </div>
    @endif
</dl>
