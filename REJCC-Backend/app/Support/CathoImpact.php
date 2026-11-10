<?php

namespace App\Support;

use App\Mail\RecapCathoImpact;
use App\Models\CathoImpactCandidature;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * CathoImpact (département Communication). Le formulaire du site est volontairement
 * court : il sert à présélectionner les parcours. Les personnes retenues reçoivent
 * ensuite un questionnaire détaillé (Google Forms), hors de la plateforme.
 */
class CathoImpact
{
    public const CONSENTEMENT = 'J\'autorise le REJCC à publier mon récit et mes photos sur ses réseaux sociaux et sur son site internet, dans le cadre de CathoImpact.';

    /** Longueurs maximales des deux textes libres. */
    public const PARCOURS_MAX = 1500;

    public const INSPIRATION_MAX = 1000;

    /** Photo (portrait) déposée par le site dans le stockage privé partagé. */
    public const CHEMIN_PHOTO = '/^cathoimpact\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|jpeg|png|webp)$/';

    /** Libellés des champs « entreprise » et « fonction » selon le profil. */
    public const PROFILS = [
        'entrepreneur' => [
            'label' => 'Entrepreneur',
            'entreprise' => 'Nom de votre entreprise',
            'fonction' => 'Votre activité',
        ],
        'professionnel' => [
            'label' => 'Professionnel (non entrepreneur)',
            'entreprise' => 'Votre entreprise ou organisation',
            'fonction' => 'Votre poste',
        ],
    ];

    /** Parcours d'une réponse, dans l'ordre, puis les deux issues négatives. */
    public const STATUTS = [
        'recu' => 'Reçu',
        'retenu' => 'Retenu',
        'questionnaire_envoye' => 'Questionnaire détaillé envoyé',
        'questionnaire_rempli' => 'Questionnaire rempli',
        'attribue' => 'Attribué à un community manager',
        'en_creation' => 'En création',
        'publie' => 'Publié',
        'non_retenu' => 'Non retenu',
        'non_publie' => 'Non publié',
    ];

    /** Statuts qui n'attendent plus rien de la Communication. */
    public const STATUTS_CLOS = ['publie', 'non_retenu', 'non_publie'];

    /** Réseaux des cartes « Ils ont inspiré ». */
    public const RESEAUX = [
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'x' => 'X',
        'autre' => 'Autre',
    ];

    public static function libelleStatut(?string $statut): string
    {
        return self::STATUTS[$statut] ?? (string) $statut;
    }

    /**
     * Réponses visibles par ce compte. Un compte de test ne voit que les
     * réponses envoyées avec l'e-mail d'un compte de test : il peut essayer
     * le parcours avec ses propres envois sans lire les données des vrais
     * candidats. Sans compte (commande, récapitulatif) : toutes.
     */
    public static function visibles(?User $u): Builder
    {
        return CathoImpactCandidature::query()->when($u?->estTesteur(), fn ($q) => $q->whereIn('email', self::emailsTesteurs()));
    }

    /** E-mails (en minuscules) des comptes de la campagne de test. */
    public static function emailsTesteurs(): array
    {
        return User::whereNotNull('departement')->pluck('email')->filter()->map(fn ($e) => mb_strtolower($e))->values()->all();
    }

    /** Comptes d'administration actifs du département Communication. */
    public static function equipe()
    {
        return User::where('role', 'admin')->where('is_active', true)
            ->where('bureau_departement', 'communication')
            ->orderBy('prenom')->get(['id', 'prenom', 'nom', 'email']);
    }

    /**
     * Récapitulatif hebdomadaire envoyé à l'équipe Communication : nouvelles
     * réponses des 7 derniers jours et réponses en attente par statut. Rien
     * n'est envoyé s'il n'y a ni nouveauté ni attente.
     *
     * @return int nombre d'e-mails envoyés
     */
    public static function envoyerRecap(): int
    {
        $nouvelles = CathoImpactCandidature::where('created_at', '>=', now()->subDays(7))->latest()->get();
        $attente = CathoImpactCandidature::whereNotIn('statut', self::STATUTS_CLOS)
            ->selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut')
            ->map(fn ($n) => (int) $n);
        // Dans l'ordre du parcours.
        $attente = collect(self::STATUTS)->keys()->filter(fn ($s) => ($attente[$s] ?? 0) > 0)
            ->mapWithKeys(fn ($s) => [$s => $attente[$s]])->all();

        if ($nouvelles->isEmpty() && $attente === []) {
            return 0;
        }

        $destinataires = self::equipe()->pluck('email')
            ->push(config('mail.admin_email'))
            ->filter()->map(fn ($e) => mb_strtolower($e))->unique()->values();

        foreach ($destinataires as $email) {
            Mailer::send($email, new RecapCathoImpact($nouvelles, $attente));
        }

        return $destinataires->count();
    }
}
