<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Boîte à outils du département Communication : catégories des documents de
 * marque, types de contacts médias, catégories et textes par défaut de la
 * bibliothèque « prêts à copier ». Les textes reprennent le guide d'identité
 * de marque (nom officiel, slogan, ton inspirant et institutionnel,
 * vouvoiement) et laissent entre crochets ce que la Com complète.
 */
class OutilsCommunication
{
    public const CATEGORIES_FICHIERS = [
        'identite' => 'Identité',
        'charte' => 'Charte graphique',
        'logos' => 'Logos',
        'gabarits' => 'Gabarits',
        'autres' => 'Autres',
    ];

    public const TYPES_CONTACTS = [
        'presse' => 'Presse écrite',
        'radio' => 'Radio',
        'tv' => 'Télévision',
        'web' => 'Média web',
        'influenceur' => 'Influenceur',
        'institution' => 'Institution',
        'autre' => 'Autre',
    ];

    public const CATEGORIES_TEXTES = [
        'presentation' => 'Présentation du REJCC',
        'signature' => "Signature d'e-mail",
        'reseaux' => 'Bio des réseaux sociaux',
        'hashtags' => 'Hashtags',
        'messages' => 'Messages types',
        'mentions' => 'Mentions et consentement',
    ];

    /** Taille maximale d'un document de marque, en Ko (envois limités à 32M en production). */
    public const TAILLE_MAX_KO = 30720;

    /**
     * Textes par défaut : clé => [catégorie, titre, contenu, limite conseillée].
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: ?int}>
     */
    public static function textesParDefaut(): array
    {
        $nom = 'Réseau Entrepreneurial des Jeunes Chrétiens Catholiques';

        return [
            // ── Présentation ─────────────────────────────────────────────
            'presentation-courte' => ['presentation', 'Présentation courte', "Le REJCC ({$nom}) rassemble les jeunes entrepreneurs et porteurs de projets catholiques de Côte d'Ivoire qui veulent grandir, collaborer et réussir ensemble, en alliant foi, innovation et excellence entrepreneuriale. Ensemble pour l'excellence.", null],
            'presentation-longue' => ['presentation', 'Présentation longue', "Le {$nom} (REJCC) est une communauté de jeunes entrepreneurs et de porteurs de projets catholiques unis par la volonté de collaborer, d'innover et de bâtir des solutions durables au service du développement.\n\nSa mission : connecter les jeunes entrepreneurs et porteurs de projets catholiques pour favoriser la co-création, le partage de ressources et le développement de solutions technologiques et commerciales durables.\n\nSa vision : devenir le premier incubateur de talents et d'entreprises au service de l'Église et de la société en Côte d'Ivoire, en bâtissant une communauté solidaire, innovante et économiquement autonome.\n\nLe REJCC allie l'excellence entrepreneuriale et l'innovation à la puissance de la foi et de la solidarité chrétienne. Il ne forme pas seulement des femmes et des hommes d'affaires : il bâtit une communauté éthique où la création de richesse est mise au service de l'Église et de la société.\n\nSes valeurs : foi, excellence, solidarité, impact et création de richesse.\n\nRejoindre le REJCC, c'est accéder à un réseau d'excellence pour entreprendre, grandir et réussir ensemble.\n\nEnsemble pour l'excellence.\nrejcc.site", null],
            'presentation-promesse' => ['presentation', 'Promesse et slogan', "Accéder à un réseau d'excellence pour entreprendre, grandir et réussir ensemble.\nREJCC · Ensemble pour l'excellence.", null],

            // ── Signature d'e-mail ───────────────────────────────────────
            'signature-email' => ['signature', "Signature d'e-mail de l'équipe", "[Prénom Nom]\n[Fonction] · Département Communication et Marketing\nREJCC · {$nom}\n« Ensemble pour l'excellence »\n[Téléphone / WhatsApp] · contact@rejcc.site · rejcc.site", null],

            // ── Réseaux sociaux ──────────────────────────────────────────
            'bio-instagram' => ['reseaux', 'Bio Instagram', "Réseau des jeunes entrepreneurs chrétiens catholiques de Côte d'Ivoire. Foi, innovation, excellence. Ensemble pour l'excellence. rejcc.site", 150],
            'bio-tiktok' => ['reseaux', 'Bio TikTok', "Jeunes entrepreneurs chrétiens catholiques · Ensemble pour l'excellence", 80],
            'bio-linkedin' => ['reseaux', 'Titre LinkedIn (page)', "Réseau Entrepreneurial des Jeunes Chrétiens Catholiques · Foi, innovation et excellence entrepreneuriale · Ensemble pour l'excellence", 220],
            'bio-facebook' => ['reseaux', 'À propos Facebook et YouTube', "Le REJCC connecte les jeunes entrepreneurs chrétiens catholiques de Côte d'Ivoire pour bâtir des entreprises à impact durable. Formations, événements, mentorat : entreprenez, grandissez et réussissez ensemble. Ensemble pour l'excellence. rejcc.site", 255],

            // ── Hashtags ─────────────────────────────────────────────────
            'hashtags-generaux' => ['hashtags', 'Hashtags de base', '#REJCC #EnsemblePourLExcellence #JeunesEntrepreneurs #EntrepreneuriatCatholique #CoteDIvoire', null],
            'hashtags-evenements' => ['hashtags', 'Hashtags événements et formations', '#REJCC #EnsemblePourLExcellence #Formation #Networking #Entrepreneuriat #CoteDIvoire', null],
            'hashtags-cathoimpact' => ['hashtags', 'Hashtags CathoImpact', '#CathoImpact #REJCC #EnsemblePourLExcellence #PortraitEntrepreneur #FoiEtEntreprise', null],

            // ── Messages types (un par type d'information) ───────────────
            'message-nouvel-evenement' => ['messages', 'Nouvel événement', "📅 [Titre de l'événement]\n\nLe REJCC vous invite à [type : conférence, atelier, rencontre…] sur le thème « [thème] ».\n\n🗓 [Date] à [heure]\n📍 [Lieu]\n🎤 Avec [intervenants]\n\n[Une phrase sur ce que les participants vont apprendre ou vivre.]\n\nInscription : [lien]\nPlaces limitées : réservez dès maintenant la vôtre.\n\nEnsemble pour l'excellence.\n#REJCC #EnsemblePourLExcellence", null],
            'message-rappel-veille' => ['messages', 'Rappel la veille', "⏰ C'est demain !\n\n[Titre de l'événement]\n🗓 [Date] à [heure]\n📍 [Lieu ou lien de connexion]\n\nNous avons hâte de vous retrouver. Pensez à [arriver 15 minutes en avance / préparer vos questions / apporter vos cartes de visite].\n\nPas encore inscrit ? Il reste quelques places : [lien]\n\nEnsemble pour l'excellence.", null],
            'message-compte-rendu' => ['messages', 'Compte rendu', "Merci à toutes et à tous !\n\n[Titre de l'événement] s'est tenu le [date] à [lieu]. Merci aux [nombre] participants, à [intervenants] et à [partenaires] pour la qualité des échanges.\n\nÀ retenir :\n• [Idée forte n° 1]\n• [Idée forte n° 2]\n• [Idée forte n° 3]\n\nRetrouvez les photos dans la galerie : [lien]\nProchain rendez-vous : [date et thème].\n\nEnsemble pour l'excellence.", null],
            'message-portrait-cathoimpact' => ['messages', 'Portrait CathoImpact', "✨ CathoImpact · [Prénom Nom]\n\n[Fonction] chez [entreprise], à [ville].\n\n« [Citation courte tirée du portrait] »\n\n[Deux ou trois phrases sur son parcours, son entreprise et ce que sa foi apporte à sa manière d'entreprendre.]\n\nDécouvrez son portrait complet : [lien]\n\nVous aussi, partagez votre parcours : rejcc.site/cathoimpact\n#CathoImpact #REJCC #EnsemblePourLExcellence", null],
            'message-formation' => ['messages', 'Formation', "🎓 Nouvelle formation : [titre]\n\n[Une phrase sur l'objectif : ce que vous saurez faire à la fin.]\n\n🗓 [Date] à [heure]\n💻 [En ligne / lieu]\n👤 Animée par [formateur]\n[Gratuite pour les membres / tarif]\n\nInscription sur la plateforme : [lien]\n\nEnsemble pour l'excellence.", null],
            'message-offre-emploi' => ['messages', "Offre d'emploi ou de stage", "💼 Opportunité : [intitulé du poste]\n\n[Entreprise] recherche [profil] à [ville].\nType : [CDI / CDD / stage / mission]\nDate limite : [date]\n\nToutes les informations et la candidature sont sur la plateforme, dans Emploi & Stage : [lien]\n\nN'hésitez pas à partager cette offre autour de vous.", null],
            'message-bureau' => ['messages', 'Message du Bureau', "Chers membres,\n\n[Message du Bureau exécutif : annonce, orientation, remerciements…]\n\nNous restons à votre écoute à contact@rejcc.site.\n\nFraternellement,\nLe Bureau exécutif du REJCC\nEnsemble pour l'excellence.", null],
            'message-partenaire' => ['messages', 'Nouveau partenaire', "🤝 Bienvenue à [nom du partenaire] !\n\nLe REJCC est heureux d'annoncer son partenariat avec [nom du partenaire], [présentation en une phrase].\n\nEnsemble, nous [objectif concret du partenariat] au bénéfice des membres du réseau.\n\nEn savoir plus : [lien]\n#REJCC #EnsemblePourLExcellence", null],
            'message-fete-liturgique' => ['messages', 'Fête liturgique', "[Nom de la fête]\n\nEn ce jour de [fête], le REJCC souhaite à chacune et à chacun [souhait].\n\n« [Verset ou citation] » ([référence])\n\nQue cette fête nourrisse notre foi et notre engagement à entreprendre au service de l'Église et de la société.\n\nEnsemble pour l'excellence.", null],
            'message-campagne-adhesion' => ['messages', "Campagne d'adhésion", "Entrepreneur, porteur de projet, étudiant ou jeune diplômé ?\n\nRejoignez le REJCC, le réseau des jeunes entrepreneurs chrétiens catholiques : formations, événements, mentorat, opportunités et une communauté qui vous fait grandir.\n\nAccédez à un réseau d'excellence pour entreprendre, grandir et réussir ensemble.\n\nAdhésion en ligne : rejcc.site/adhesion\n[Date limite ou offre du moment, s'il y en a une]\n\nEnsemble pour l'excellence.\n#REJCC #EnsemblePourLExcellence", null],

            // ── Mentions ─────────────────────────────────────────────────
            'mention-consentement-image' => ['mentions', 'Consentement photo et vidéo (événements)', "Des photos et des vidéos seront prises pendant cet événement. Elles pourront être publiées par le REJCC ({$nom}) sur son site et ses réseaux sociaux pour faire connaître ses activités. Si vous ne souhaitez pas apparaître sur ces images, merci de le signaler à l'équipe d'accueil ou d'écrire à contact@rejcc.site : nous retirerons toute image sur simple demande.", null],
            'mention-consentement-portrait' => ['mentions', 'Consentement portrait (CathoImpact, témoignages)', "J'autorise le REJCC ({$nom}) à publier mon portrait (texte, photo et, le cas échéant, vidéo) sur son site et ses réseaux sociaux, sans contrepartie financière. Je peux demander à tout moment la modification ou le retrait de ce contenu en écrivant à contact@rejcc.site.", null],
        ];
    }

    /**
     * Campagne de test sur le site réel : un compte de test ne modifie ni ne
     * supprime que ce qu'il a créé (les vrais documents et textes de la Com
     * restent intacts).
     */
    public static function peutModifier(User $u, ?int $auteurId): bool
    {
        return ! $u->estTesteur() || $auteurId === $u->id;
    }

    public const MESSAGE_TESTEUR = 'Les comptes de test ne peuvent modifier que ce qu\'ils ont créé.';

    /** Taille lisible d'un fichier (« 2,4 Mo », « 350 Ko »). */
    public static function taille(?int $octets): ?string
    {
        if (! $octets) {
            return null;
        }

        return $octets >= 1048576 ? number_format($octets / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($octets / 1024)).' Ko';
    }

    /** Ajoute les textes par défaut absents (par clé), sans toucher à ceux que la Com a modifiés. Renvoie le nombre ajouté. */
    public static function installerTextesParDefaut(): int
    {
        $existants = DB::table('communication_textes')->whereNotNull('cle')->pluck('cle')->all();
        $ajoutes = 0;
        $ordre = 0;
        foreach (self::textesParDefaut() as $cle => [$categorie, $titre, $contenu, $limite]) {
            $ordre += 10;
            if (in_array($cle, $existants, true)) {
                continue;
            }
            DB::table('communication_textes')->insert([
                'cle' => $cle, 'categorie' => $categorie, 'titre' => $titre, 'contenu' => $contenu,
                'limite' => $limite, 'ordre' => $ordre, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $ajoutes++;
        }

        return $ajoutes;
    }
}
