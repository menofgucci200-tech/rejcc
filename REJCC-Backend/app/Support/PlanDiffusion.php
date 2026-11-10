<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Plan de diffusion du département Communication : pour chaque type
 * d'information, les canaux recommandés. Sert de référence aux demandes de
 * communication des autres départements (canaux pré-cochés) et au
 * calendrier éditorial (GET /admin/plan-diffusion).
 *
 * « Annonces » désigne le canal Annonces de la Communauté sur la plateforme
 * REJCC (réservé à l'équipe), pas WhatsApp.
 */
class PlanDiffusion
{
    /** Canaux de diffusion, dans l'ordre d'affichage. */
    public const CANAUX = [
        'site' => ['label' => 'Site internet', 'detail' => 'rejcc.site : actualités, agenda, pages'],
        'reseaux' => ['label' => 'Réseaux sociaux', 'detail' => 'Facebook, LinkedIn, Instagram, TikTok, YouTube'],
        'whatsapp' => ['label' => 'WhatsApp', 'detail' => 'Groupes et chaîne WhatsApp du REJCC'],
        'email' => ['label' => 'E-mail', 'detail' => 'E-mail aux membres'],
        'annonces' => ['label' => 'Annonces', 'detail' => 'Canal Annonces de la Communauté sur la plateforme REJCC'],
    ];

    private const TOUS = ['site', 'reseaux', 'whatsapp', 'email', 'annonces'];

    /** Type d'information => libellé, canaux recommandés et précision éventuelle. */
    public const TYPES = [
        'nouvel_evenement' => ['label' => 'Nouvel événement', 'canaux' => self::TOUS, 'note' => null],
        'rappel' => ['label' => 'Rappel', 'canaux' => self::TOUS, 'note' => 'La veille de l\'événement'],
        'compte_rendu' => ['label' => 'Compte rendu et photos', 'canaux' => ['email', 'annonces'], 'note' => null],
        'formation' => ['label' => 'Formation', 'canaux' => ['site', 'email', 'whatsapp', 'annonces'], 'note' => null],
        'offre_emploi' => ['label' => 'Offre d\'emploi', 'canaux' => ['whatsapp', 'email', 'annonces'], 'note' => null],
        'message_bureau' => ['label' => 'Message du Bureau', 'canaux' => ['site', 'whatsapp', 'email'], 'note' => null],
        'partenaire' => ['label' => 'Partenaire', 'canaux' => ['site', 'email'], 'note' => null],
        'fete_liturgique' => ['label' => 'Fête liturgique', 'canaux' => self::TOUS, 'note' => null],
        'campagne_adhesion' => ['label' => 'Campagne d\'adhésion', 'canaux' => ['site', 'reseaux'], 'note' => null],
        'mise_en_avant_membre' => ['label' => 'Mise en avant d\'un membre', 'canaux' => ['reseaux', 'site'], 'note' => 'Portrait'],
        'autre' => ['label' => 'Autre', 'canaux' => [], 'note' => 'Canaux à préciser avec la Communication'],
    ];

    /** La Communication demande 1 à 2 semaines : en dessous de 7 jours, on avertit. */
    public const DELAI_MINIMUM_JOURS = 7;

    public const DELAI_CONSEILLE_JOURS = 14;

    public const MESSAGE_DELAI = 'La Communication demande 1 à 2 semaines de délai ; votre demande risque de ne pas être traitée à temps.';

    public static function libelleType(?string $type): string
    {
        return self::TYPES[$type]['label'] ?? (string) $type;
    }

    public static function libelleCanal(string $canal): string
    {
        return self::CANAUX[$canal]['label'] ?? $canal;
    }

    /** @return list<string> canaux recommandés pour ce type */
    public static function canaux(string $type): array
    {
        return self::TYPES[$type]['canaux'] ?? [];
    }

    /** Nombre de jours entre aujourd'hui et la date souhaitée (négatif si passée). */
    public static function joursAvant(CarbonInterface|string $date): int
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return (int) Carbon::today()->diffInDays($date->copy()->startOfDay(), false);
    }

    /**
     * Niveau du délai : « court » (moins de 7 jours, avertissement), « juste »
     * (entre 7 et 14 jours) ou « confortable » (14 jours et plus).
     */
    public static function niveauDelai(CarbonInterface|string $date): string
    {
        $jours = self::joursAvant($date);

        return match (true) {
            $jours < self::DELAI_MINIMUM_JOURS => 'court',
            $jours < self::DELAI_CONSEILLE_JOURS => 'juste',
            default => 'confortable',
        };
    }

    /** Avertissement à afficher au demandeur, ou null si le délai est suffisant. */
    public static function avertissementDelai(CarbonInterface|string $date): ?string
    {
        return self::niveauDelai($date) === 'court' ? self::MESSAGE_DELAI : null;
    }

    /** Plan complet, tel que l'expose l'API. */
    public static function tableau(): array
    {
        return [
            'canaux' => collect(self::CANAUX)->map(fn ($c, $cle) => ['cle' => $cle] + $c)->values()->all(),
            'types' => collect(self::TYPES)->map(fn ($t, $cle) => [
                'cle' => $cle,
                'label' => $t['label'],
                'canaux' => $t['canaux'],
                'canaux_labels' => array_map(self::libelleCanal(...), $t['canaux']),
                'tous_les_canaux' => $t['canaux'] === self::TOUS,
                'note' => $t['note'],
            ])->values()->all(),
            'delai' => [
                'minimum_jours' => self::DELAI_MINIMUM_JOURS,
                'conseille_jours' => self::DELAI_CONSEILLE_JOURS,
                'message' => self::MESSAGE_DELAI,
            ],
            'annonces' => 'Le canal Annonces, c\'est le canal Annonces de la Communauté sur la plateforme REJCC (pas WhatsApp).',
        ];
    }
}
