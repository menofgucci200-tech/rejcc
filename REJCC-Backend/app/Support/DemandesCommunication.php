<?php

namespace App\Support;

use App\Mail\DemandeCommunicationRecue;
use App\Mail\DemandeCommunicationSuivi;
use App\Models\CommunicationPublication;
use App\Models\DemandeCommunication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Demandes de communication des départements du Bureau. Tout compte du
 * dashboard peut en déposer ; seule la Communication les traite, sans
 * circuit de validation.
 */
class DemandesCommunication
{
    public const STATUTS = [
        'nouvelle' => 'Nouvelle',
        'acceptee' => 'Acceptée',
        'planifiee' => 'Planifiée',
        'publiee' => 'Publiée',
        'refusee' => 'Refusée',
        'annulee' => 'Annulée',
    ];

    /** Statuts qui attendent encore la Communication. */
    public const STATUTS_OUVERTS = ['nouvelle', 'acceptee', 'planifiee'];

    /**
     * Changements possibles pour la Communication. L'annulation appartient au
     * demandeur ; une demande refusée peut être reprise (erreur, nouveau
     * contexte), une demande publiée ou annulée est close.
     */
    public const TRANSITIONS = [
        'nouvelle' => ['acceptee', 'refusee'],
        'acceptee' => ['planifiee', 'publiee', 'refusee'],
        'planifiee' => ['publiee', 'acceptee', 'refusee'],
        'refusee' => ['acceptee'],
        'publiee' => [],
        'annulee' => [],
    ];

    /** Type de la demande => type de publication du calendrier éditorial (CalendrierEditorial::TYPES). */
    public const TYPES_CALENDRIER = [
        'nouvel_evenement' => 'evenement',
        'rappel' => 'rappel_veille',
        'compte_rendu' => 'compte_rendu',
        'formation' => 'formation',
        'offre_emploi' => 'emploi',
        'message_bureau' => 'bureau',
        'partenaire' => 'partenaire',
        'fete_liturgique' => 'fete_liturgique',
        'campagne_adhesion' => 'adhesion',
        'mise_en_avant_membre' => 'membre',
        'autre' => 'autre',
    ];

    /** Pièces jointes déposées par le dashboard : dossier dédié, nom aléatoire, image ou PDF. */
    public const CHEMIN_FICHIER = '/^demandes-communication\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|jpeg|png|webp|pdf)$/';

    public const FICHIERS_MAX = 3;

    public const FICHIER_MAX_OCTETS = 10 * 1024 * 1024;

    public static function libelleStatut(?string $statut): string
    {
        return self::STATUTS[$statut] ?? (string) $statut;
    }

    public static function libelleDepartement(?string $slug): string
    {
        return Departements::label($slug) ?? 'Administration';
    }

    /** Département demandeur, déduit du compte (sinon « Administration », null). */
    public static function departementDe(User $u): ?string
    {
        return $u->bureau_departement && isset(Departements::LISTE[$u->bureau_departement]) ? $u->bureau_departement : null;
    }

    /** Le compte traite-t-il les demandes (rubrique « communication ») ? */
    public static function traite(User $u): bool
    {
        return $u->role === 'admin' && (! is_array($u->permissions) || in_array('communication', $u->permissions, true));
    }

    /**
     * Demandes visibles côté Communication par ce compte. Un compte de test ne
     * voit que les demandes déposées par des comptes de test (jamais celles
     * des vrais départements, ni leurs pièces jointes).
     */
    public static function visibles(?User $u): Builder
    {
        return DemandeCommunication::query()
            ->when($u?->estTesteur(), fn ($q) => $q->whereIn('demandeur_id', User::whereNotNull('departement')->select('id')));
    }

    /**
     * Synchronisation calendrier → demande : la publication liée vient d'être
     * marquée publiée dans le calendrier éditorial, la demande passe à
     * « publiée » et le demandeur est prévenu, comme dans l'autre sens. Pas de
     * boucle : le sens demande → calendrier modifie la publication sans
     * repasser par ici. Un compte de test ne touche pas la demande d'un vrai
     * département (aucun e-mail à un vrai demandeur).
     */
    public static function publicationPubliee(CommunicationPublication $p, User $par): ?DemandeCommunication
    {
        if (! $p->demande_id || $p->statut !== 'publie') {
            return null;
        }
        $d = self::visibles($par)->find($p->demande_id);
        if (! $d || ! in_array('publiee', self::TRANSITIONS[$d->statut] ?? [], true)) {
            return null;
        }
        $lien = collect($p->liens ?? [])->filter()->last();
        if ($lien && ! $d->lien_publication) {
            $d->lien_publication = $lien;
        }
        $d->publication_id ??= $p->id;
        $d->changerStatut('publiee', $par, 'Publication marquée publiée dans le calendrier éditorial.');
        $d->save();
        self::notifierDemandeur($d, 'publiee', null);

        return $d;
    }

    /** Demande « urgente » : encore ouverte et souhaitée dans moins de 7 jours (ou déjà dépassée). */
    public static function estUrgente(DemandeCommunication $d): bool
    {
        return in_array($d->statut, ['nouvelle', 'acceptee'], true)
            && PlanDiffusion::joursAvant($d->date_souhaitee) < PlanDiffusion::DELAI_MINIMUM_JOURS;
    }

    /** Équipe Communication : comptes d'administration actifs du département. */
    public static function equipe()
    {
        return CathoImpact::equipe();
    }

    /** E-mail à l'équipe Communication pour une nouvelle demande. */
    public static function notifierEquipe(DemandeCommunication $d): int
    {
        // Demande d'un compte de test : la vraie équipe n'est pas dérangée.
        if (User::find($d->demandeur_id)?->estTesteur()) {
            return 0;
        }

        $emails = self::equipe()->pluck('email')->filter()->map(fn ($e) => mb_strtolower($e))->unique()->values();
        foreach ($emails as $email) {
            Mailer::send($email, new DemandeCommunicationRecue($d));
        }

        return $emails->count();
    }

    /** E-mail au demandeur : changement de statut ou réponse de la Communication. */
    public static function notifierDemandeur(DemandeCommunication $d, string $evenement, ?string $texte = null): void
    {
        $d->loadMissing('demandeur');
        Mailer::send($d->demandeur?->email, new DemandeCommunicationSuivi($d, $evenement, $texte));
    }
}
