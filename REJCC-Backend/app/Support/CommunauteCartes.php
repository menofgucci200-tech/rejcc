<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Formation;
use App\Models\Opportunity;
use App\Models\Project;
use Illuminate\Support\Str;

/**
 * Cartes de partage de la Communauté : un événement, une offre d'emploi,
 * une formation ou un projet présenté dans un salon, avec un lien direct
 * vers sa page dans l'espace membre (inscription, candidature…). La carte
 * est recalculée à chaque affichage : elle reste à jour (date, état).
 */
class CommunauteCartes
{
    public const TYPES = [
        'evenement' => 'Événement',
        'offre' => 'Offre d\'emploi',
        'formation' => 'Formation',
        'projet' => 'Projet',
    ];

    /** Carte prête à afficher, ou null si l'élément n'existe plus ou n'est pas partageable. */
    public static function carte(?string $type, ?int $id): ?array
    {
        if (! $type || ! $id) {
            return null;
        }

        return match ($type) {
            'evenement' => self::evenement(Event::whereIn('statut', ['publie', 'annule'])->find($id)),
            'offre' => self::offre(Opportunity::whereIn('statut', ['publiee', 'pourvue', 'cloturee'])->find($id)),
            'formation' => self::formation(Formation::where('is_published', true)->find($id)),
            'projet' => self::projet(Project::where('statut', 'valide')->find($id)),
            default => null,
        };
    }

    /** Éléments partageables (pour le sélecteur de la zone de saisie). */
    public static function rechercher(string $type, string $q = ''): array
    {
        $q = trim($q);
        $filtre = fn ($requete, string $champ) => $q !== '' ? $requete->where($champ, 'like', '%'.$q.'%') : $requete;

        $liste = match ($type) {
            'evenement' => $filtre(Event::where('statut', 'publie')->where('starts_at', '>=', now()->subDay()), 'title')->orderBy('starts_at')->limit(8)->get()->map(fn ($e) => self::evenement($e)),
            'offre' => $filtre(Opportunity::enLigne(), 'title')->latest('publie_at')->limit(8)->get()->map(fn ($o) => self::offre($o)),
            'formation' => $filtre(Formation::where('is_published', true), 'title')->latest('id')->limit(8)->get()->map(fn ($f) => self::formation($f)),
            'projet' => $filtre(Project::where('statut', 'valide'), 'title')->latest('decide_at')->limit(8)->get()->map(fn ($p) => self::projet($p)),
            default => collect(),
        };

        return $liste->filter()->values()->all();
    }

    private static function evenement(?Event $e): ?array
    {
        if (! $e) {
            return null;
        }
        $date = $e->starts_at->locale('fr');
        $etat = match (true) {
            $e->statut === 'annule' => 'Annulé',
            $e->starts_at->lt(now()->startOfDay()) => 'Passé',
            default => null,
        };

        return [
            'type' => 'evenement',
            'type_libelle' => self::TYPES['evenement'],
            'id' => $e->id,
            'titre' => $e->title,
            'detail' => ucfirst($date->isoFormat('dddd D MMMM [à] HH[h]mm')).' · '.($e->en_ligne ? 'En ligne' : ($e->location ?: 'Lieu à préciser')),
            'image' => $e->image,
            'etat' => $etat,
            'action' => $etat ? 'Voir l\'événement' : 'S\'inscrire',
            'lien' => '/espace-membre/evenements?evenement='.$e->id,
        ];
    }

    private static function offre(?Opportunity $o): ?array
    {
        if (! $o) {
            return null;
        }
        $etat = match (true) {
            $o->statut === 'pourvue' => 'Pourvue',
            $o->statut === 'cloturee' || $o->estExpiree() => 'Expirée',
            default => null,
        };

        return [
            'type' => 'offre',
            'type_libelle' => Opportunity::TYPES[$o->type] ?? self::TYPES['offre'],
            'id' => $o->id,
            'titre' => $o->title,
            'detail' => collect([$o->entreprise, $o->lieu])->filter()->join(' · '),
            'image' => null,
            'etat' => $etat,
            'action' => $etat ? 'Voir l\'offre' : 'Postuler',
            'lien' => '/espace-membre/emplois?offre='.$o->id,
        ];
    }

    private static function formation(?Formation $f): ?array
    {
        if (! $f) {
            return null;
        }

        return [
            'type' => 'formation',
            'type_libelle' => self::TYPES['formation'],
            'id' => $f->id,
            'titre' => $f->title,
            'detail' => collect([$f->category, $f->duration, $f->is_free ? 'Accès libre' : null, $f->is_certifying ? 'Certifiante' : null])->filter()->join(' · '),
            'image' => $f->image_url,
            'etat' => null,
            'action' => 'Voir la formation',
            'lien' => '/espace-membre/catalogue/'.$f->id,
        ];
    }

    private static function projet(?Project $p): ?array
    {
        if (! $p) {
            return null;
        }

        return [
            'type' => 'projet',
            'type_libelle' => self::TYPES['projet'],
            'id' => $p->id,
            'titre' => $p->title,
            'detail' => Str::limit((string) ($p->accroche ?: $p->description), 110),
            'image' => $p->image,
            'etat' => null,
            'action' => 'Découvrir le projet',
            'lien' => '/espace-membre/projets?projet='.$p->id,
        ];
    }
}
