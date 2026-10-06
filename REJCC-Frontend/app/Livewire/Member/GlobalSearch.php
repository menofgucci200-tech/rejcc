<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Recherche de la barre du haut de l'espace membre : formations, membres
 * (abonnés uniquement), événements et rubriques de l'espace. Les résultats
 * s'affichent pendant la frappe et mènent directement au bon écran.
 */
class GlobalSearch extends Component
{
    public string $q = '';

    /** Rubriques de l'espace membre, retrouvables par leur nom ou des mots proches. */
    protected const RUBRIQUES = [
        ['label' => 'Ma carte membre', 'route' => 'espace-membre.carte', 'mots' => 'carte qr code badge'],
        ['label' => 'Mes formations', 'route' => 'espace-membre.formations', 'mots' => 'formations cours apprendre'],
        ['label' => 'Catalogue des formations', 'route' => 'espace-membre.catalogue', 'mots' => 'catalogue formations cours'],
        ['label' => 'Mes parcours', 'route' => 'espace-membre.parcours', 'mots' => 'parcours guides'],
        ['label' => 'Mentorat', 'route' => 'espace-membre.mentorat', 'mots' => 'mentor mentorat accompagnement'],
        ['label' => 'Annuaire des membres', 'route' => 'espace-membre.directory', 'mots' => 'annuaire membres reseau'],
        ['label' => 'Groupes sectoriels', 'route' => 'espace-membre.groupes', 'mots' => 'groupes secteurs'],
        ['label' => 'Messagerie', 'route' => 'espace-membre.messaging', 'mots' => 'messages messagerie discussion'],
        ['label' => 'Marketplace', 'route' => 'espace-membre.marketplace', 'mots' => 'marketplace annonces produits services'],
        ['label' => 'Événements', 'route' => 'espace-membre.evenements', 'mots' => 'evenements agenda calendrier'],
        ['label' => 'Projets', 'route' => 'espace-membre.projets', 'mots' => 'projets equipe porteur partenaires'],
        ['label' => 'Emploi & Stage', 'route' => 'espace-membre.emplois', 'mots' => 'emploi stage offres travail'],
        ['label' => 'Documents', 'route' => 'espace-membre.documents', 'mots' => 'documents ressources telecharger'],
        ['label' => 'Certificats', 'route' => 'espace-membre.certificats', 'mots' => 'certificats attestations'],
        ['label' => 'Mon abonnement', 'route' => 'espace-membre.abonnement', 'mots' => 'abonnement paiement cotisation'],
        ['label' => 'Paramètres du profil', 'route' => 'espace-membre.profile', 'mots' => 'profil parametres mot de passe photo'],
        ['label' => 'Notifications', 'route' => 'espace-membre.notifications', 'mots' => 'notifications alertes'],
    ];

    /** Comparaison sans accents ni majuscules (« evenement » trouve « Événement »). */
    protected static function norm(?string $s): string
    {
        return Str::lower(Str::ascii((string) $s));
    }

    protected static function matches(string $needle, ?string ...$fields): bool
    {
        foreach ($fields as $f) {
            if ($f !== null && str_contains(static::norm($f), $needle)) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        $q = trim($this->q);
        $groupes = [];

        if (mb_strlen($q) >= 2) {
            $needle = static::norm($q);
            $token = Api::token();

            $formations = Collection::make(Api::get('/formations', [], $token)['formations'] ?? [])
                ->filter(fn ($f) => static::matches($needle, $f['title'], $f['category'], $f['description'] ?? null))
                ->take(4)
                ->map(fn ($f) => [
                    'titre' => $f['title'],
                    'detail' => $f['category'].($f['enrolled'] ? ' · inscrit' : ''),
                    'url' => $f['enrolled']
                        ? route('espace-membre.formations.detail', $f['id'])
                        : route('espace-membre.catalogue', ['q' => $f['title']]),
                    'icon' => 'graduation-cap',
                ])->values()->all();

            $membres = [];
            if (Api::user()->subscription_active ?? false) {
                $membres = Collection::make(Api::get('/members', ['q' => $q], $token)['members'] ?? [])
                    ->take(4)
                    ->map(fn ($m) => [
                        'titre' => trim($m['prenom'].' '.$m['nom']),
                        'detail' => collect([$m['secteur'] ?? null, $m['ville'] ?? null])->filter()->join(' · ') ?: 'Membre du réseau',
                        'url' => route('espace-membre.directory', ['q' => trim($m['prenom'].' '.$m['nom'])]),
                        'icon' => 'user',
                    ])->all();
            }

            $projets = [];
            if (Api::user()->subscription_active ?? false) {
                $projets = Collection::make(Api::get('/projects', ['q' => $q], $token)['projects'] ?? [])
                    ->take(4)
                    ->map(fn ($p) => [
                        'titre' => $p['title'],
                        'detail' => collect([$p['stade_label'] ?? null, $p['groupe']['nom'] ?? null, $p['ville'] ?? null])->filter()->join(' · '),
                        'url' => route('espace-membre.projets', ['projet' => $p['id']]),
                        'icon' => 'nav-projects',
                    ])->values()->all();
            }

            $evenements = Collection::make(Api::get('/events', [], $token)['events'] ?? [])
                ->filter(fn ($e) => static::matches($needle, $e['title'], $e['category'], $e['location']))
                ->map(function ($e) {
                    $e['starts_at'] = Carbon::parse($e['starts_at']);

                    return $e;
                })
                ->sortBy(fn ($e) => [$e['starts_at']->isPast(), $e['starts_at']->timestamp])
                ->take(4)
                ->map(fn ($e) => [
                    'titre' => $e['title'],
                    'detail' => $e['starts_at']->translatedFormat('j F Y').($e['location'] ? ' · '.$e['location'] : ''),
                    'url' => route('espace-membre.evenements', ['evenement' => $e['id']]),
                    'icon' => 'calendar',
                ])->values()->all();

            $rubriques = collect(static::RUBRIQUES)
                ->filter(fn ($r) => static::matches($needle, $r['label'], $r['mots']))
                ->take(3)
                ->map(fn ($r) => ['titre' => $r['label'], 'detail' => 'Rubrique de l\'espace membre', 'url' => route($r['route']), 'icon' => 'arrow-right'])
                ->values()->all();

            $groupes = array_filter([
                'Formations' => $formations,
                'Membres' => $membres,
                'Événements' => $evenements,
                'Projets' => $projets,
                'Rubriques' => $rubriques,
            ]);
        }

        return view('livewire.member.global-search', [
            'groupes' => $groupes,
            'membresVerrouilles' => ! (Api::user()->subscription_active ?? false),
            'actif' => mb_strlen($q) >= 2,
        ]);
    }
}
