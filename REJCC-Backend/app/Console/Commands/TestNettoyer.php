<?php

namespace App\Console\Commands;

use App\Models\CathoImpactCandidature;
use App\Models\Certificate;
use App\Models\CommunicationFichier;
use App\Models\CommunicationTexte;
use App\Models\ContactMedia;
use App\Models\Document;
use App\Models\Event;
use App\Models\Formation;
use App\Models\GalleryAlbum;
use App\Models\LienSuivi;
use App\Models\MarketplaceListing;
use App\Models\NewsArticle;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\SalonMessage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fin de la campagne de test sur le site réel : supprime les comptes de test
 * et tout ce qu'ils ont créé (projets, annonces, offres, inscriptions,
 * messages… supprimés avec le compte), ainsi que les contenus dont le titre
 * commence par « [TEST] » (événements, actualités, formations… créés depuis
 * l'administration). Les retours des testeurs (bugs, suggestions) sont
 * conservés avec leur département.
 */
class TestNettoyer extends Command
{
    protected $signature = 'test:nettoyer {--force : sans demander de confirmation}';

    protected $description = 'Campagne de test : supprime les comptes de test, leur contenu et les contenus « [TEST] ».';

    public const PREFIXE = '[TEST]';

    public function handle(): int
    {
        $testeurs = User::whereNotNull('departement')->pluck('id');
        $prefixe = fn ($q, string $colonne) => $q->where($colonne, 'like', self::PREFIXE.'%');

        $cibles = [
            'Comptes de test' => User::whereIn('id', $testeurs),
            'Projets' => Project::where(fn ($q) => $q->whereIn('user_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'title'))),
            "Offres d'emploi et de stage" => Opportunity::where(fn ($q) => $q->whereIn('author_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'title'))),
            'Certificats délivrés aux testeurs' => Certificate::whereIn('user_id', $testeurs),
            // Gardés sinon avec un auteur vide (« compte supprimé ») dans les salons.
            'Messages dans la Communauté' => SalonMessage::whereIn('user_id', $testeurs),
            'Réponses CathoImpact' => CathoImpactCandidature::whereIn('email', \App\Support\CathoImpact::emailsTesteurs()),
            'Documents' => Document::where(fn ($q) => $q->whereIn('auteur_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'title'))),
            // Boîte à outils de la Communication (gardés sinon sans auteur après la suppression des comptes).
            'Documents de marque (Communication)' => CommunicationFichier::where(fn ($q) => $q->whereIn('auteur_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'titre'))),
            'Contacts médias' => ContactMedia::where(fn ($q) => $q->whereIn('auteur_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'nom'))),
            'Textes prêts à copier' => CommunicationTexte::where(fn ($q) => $q->whereIn('auteur_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'titre'))),
            // Leurs clics partent avec eux (cascade).
            'Liens de suivi' => LienSuivi::where(fn ($q) => $q->whereIn('cree_par', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'libelle'))),
            // Demandes de communication des testeurs, ou titrées « [TEST] » (leurs pièces jointes restent dans le stockage privé du site).
            'Demandes de communication' => \App\Models\DemandeCommunication::where(fn ($q) => $q->whereIn('demandeur_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'titre'))),
            'Annonces Marketplace « [TEST] »' => $prefixe(MarketplaceListing::query(), 'title'),
            'Événements « [TEST] »' => $prefixe(Event::query(), 'title'),
            'Actualités « [TEST] »' => $prefixe(NewsArticle::query(), 'title'),
            'Formations « [TEST] »' => $prefixe(Formation::query(), 'title'),
            'Albums photo « [TEST] »' => $prefixe(GalleryAlbum::query(), 'titre'),
            'Cartes CathoImpact « [TEST] »' => $prefixe(\App\Models\CathoImpactPortrait::query(), 'nom'),
            // Calendrier éditorial : publications et tours créés par les testeurs ou titrés « [TEST] ».
            'Publications du calendrier éditorial' => \App\Models\CommunicationPublication::where(fn ($q) => $q->whereIn('cree_par', $testeurs)->orWhereIn('responsable_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'titre'))),
            'Tours de rôle (Communication)' => \App\Models\CommunicationTour::where(fn ($q) => $q->whereIn('cree_par', $testeurs)->orWhereIn('responsable_id', $testeurs)->orWhere(fn ($q) => $prefixe($q, 'responsable'))),
        ];

        $lignes = collect($cibles)->map(fn ($q, $libelle) => [$libelle, (clone $q)->count()])->values()->all();
        $this->table(['À supprimer', 'Nombre'], $lignes);
        $this->line('Supprimés aussi avec les comptes : leurs annonces, inscriptions aux événements, messages privés, réactions et votes, avis, mentorats, favoris…');
        $this->line('Conservés : les retours des testeurs (bugs, suggestions) et le journal d\'audit.');

        if (array_sum(array_column($lignes, 1)) === 0) {
            $this->info('Rien à nettoyer.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Supprimer définitivement ces éléments du site ?')) {
            $this->warn('Rien n\'a été supprimé.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($cibles) {
            // Les contenus d'abord, les comptes en dernier (le reste suit par cascade).
            foreach (array_reverse($cibles, true) as $q) {
                (clone $q)->get()->each->delete();
            }
        });

        $this->info('Nettoyage terminé.');

        return self::SUCCESS;
    }
}
