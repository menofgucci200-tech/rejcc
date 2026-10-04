<?php

namespace App\Support\Content;

/**
 * « Parole & prière du jour » de l'espace membre : une liste de versets avec
 * une intention de prière, modifiable par l'administration (Réglages), qui
 * tourne chaque jour. Sans liste enregistrée, la sélection ci-dessous sert.
 */
class DailyWord
{
    public const SETTING = 'spirit.paroles';

    public const DEFAULTS = [
        ['verset' => 'Confie à l\'Éternel tes œuvres, et tes projets réussiront.', 'reference' => 'Proverbes 16:3', 'intention' => 'Prions pour les membres qui présentent un projet à un investisseur cette semaine.'],
        ['verset' => 'Tout ce que vous faites, faites-le de bon cœur, comme pour le Seigneur et non pour des hommes.', 'reference' => 'Colossiens 3:23', 'intention' => 'Prions pour que chacun trouve du sens et de la joie dans son travail quotidien.'],
        ['verset' => 'Les projets de l\'homme diligent ne mènent qu\'à l\'abondance.', 'reference' => 'Proverbes 21:5', 'intention' => 'Prions pour la persévérance des porteurs de projet qui démarrent leur activité.'],
        ['verset' => 'Je puis tout par celui qui me fortifie.', 'reference' => 'Philippiens 4:13', 'intention' => 'Prions pour les membres qui traversent une période difficile dans leur entreprise.'],
        ['verset' => 'Car je connais les projets que j\'ai formés sur vous, dit l\'Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l\'espérance.', 'reference' => 'Jérémie 29:11', 'intention' => 'Prions pour les jeunes qui cherchent encore leur voie professionnelle.'],
        ['verset' => 'Deux valent mieux qu\'un, parce qu\'ils retirent un bon salaire de leur travail.', 'reference' => 'Ecclésiaste 4:9', 'intention' => 'Prions pour les collaborations et les partenariats nés au sein du réseau.'],
        ['verset' => 'Cherchez premièrement le royaume et la justice de Dieu ; et toutes ces choses vous seront données par-dessus.', 'reference' => 'Matthieu 6:33', 'intention' => 'Prions pour que nos entreprises restent fidèles aux valeurs de l\'Évangile.'],
        ['verset' => 'Fortifie-toi et prends courage ! Ne t\'effraie point, car l\'Éternel, ton Dieu, est avec toi dans tout ce que tu entreprendras.', 'reference' => 'Josué 1:9', 'intention' => 'Prions pour les membres qui lancent un nouveau produit ou ouvrent une nouvelle activité.'],
        ['verset' => 'Que chacun de vous mette au service des autres le don qu\'il a reçu.', 'reference' => '1 Pierre 4:10', 'intention' => 'Prions pour les mentors du réseau qui partagent leur expérience.'],
        ['verset' => 'Le fer aiguise le fer, ainsi un homme en aiguise un autre.', 'reference' => 'Proverbes 27:17', 'intention' => 'Prions pour les groupes sectoriels et les échanges entre membres.'],
        ['verset' => 'Ne nous lassons pas de faire le bien ; car nous moissonnerons au temps convenable, si nous ne nous relâchons pas.', 'reference' => 'Galates 6:9', 'intention' => 'Prions pour les membres qui attendent les fruits de longs efforts.'],
        ['verset' => 'Vois-tu un homme habile dans son ouvrage ? Il se tient auprès des rois.', 'reference' => 'Proverbes 22:29', 'intention' => 'Prions pour les membres en formation et ceux qui passent un examen ou une certification.'],
        ['verset' => 'Recommande ton sort à l\'Éternel, mets en lui ta confiance, et il agira.', 'reference' => 'Psaume 37:5', 'intention' => 'Prions pour les familles des membres et pour l\'équilibre entre travail et vie de foi.'],
        ['verset' => 'Que celui qui dérobait ne dérobe plus ; mais plutôt qu\'il travaille, en faisant de ses mains ce qui est bien, pour avoir de quoi donner à celui qui est dans le besoin.', 'reference' => 'Éphésiens 4:28', 'intention' => 'Prions pour que nos réussites profitent aussi aux plus fragiles de nos communautés.'],
    ];

    /** Liste en vigueur : celle de l'administration, sinon la sélection par défaut. */
    public static function all(): array
    {
        $paroles = SiteRemote::setting(self::SETTING);

        $paroles = is_array($paroles)
            ? array_values(array_filter($paroles, fn ($p) => is_array($p) && trim((string) ($p['verset'] ?? '')) !== ''))
            : [];

        return $paroles !== [] ? $paroles : self::DEFAULTS;
    }

    /** Numéro (à partir de 0) de la parole du jour dans une liste de $count éléments. */
    public static function indexFor(int $count, ?\DateTimeInterface $date = null): int
    {
        $date ??= now();

        // Jour absolu (et non jour de l'année) : la rotation continue sans saut au 1er janvier.
        return intdiv($date->getTimestamp() + $date->getOffset(), 86400) % max(1, $count);
    }

    public static function today(): array
    {
        $paroles = self::all();

        return $paroles[self::indexFor(count($paroles))] + ['intention' => ''];
    }
}
