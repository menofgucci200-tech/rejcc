<?php

namespace Tests\Feature\Api;

use App\Mail\DemandeCommunicationSuivi;
use App\Models\ApiToken;
use App\Models\CathoImpactCandidature;
use App\Models\CathoImpactPortrait;
use App\Models\CommunicationPublication;
use App\Models\DemandeCommunication;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Accueil de l'espace Communication (cockpit) et finitions : synchronisation
 * calendrier → demande, données réelles cachées aux comptes de test
 * (CathoImpact, demandes) et envois de masse des événements interdits aux testeurs.
 */
class CockpitCommunicationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function com(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'is_active' => true,
            'permissions' => ['actualites', 'evenements', 'contenu', 'salons', 'communication'], ...$extra]);
    }

    /** Compte de test (campagne sur le site réel) avec la rubrique Communication. */
    private function compteTesteurCom(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'departement' => 'Communication', 'is_active' => true,
            'permissions' => ['actualites', 'evenements', 'contenu', 'communication'], ...$extra]);
    }

    private function formation(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'formation', 'is_active' => true,
            'permissions' => ['formations', 'certificats'], ...$extra]);
    }

    private function reponseCathoImpact(string $email, string $nom = 'Awa Koné'): int
    {
        $this->postJson('/api/cathoimpact/reponses', [
            'profil' => 'entrepreneur', 'nom_complet' => $nom, 'email' => $email, 'whatsapp' => '0700000000',
            'entreprise' => 'Kone Agro', 'fonction' => 'Transformation de manioc', 'secteur' => 'Agroalimentaire', 'ville' => 'Abidjan',
            'parcours' => 'J\'ai commencé à vendre de l\'attiéké au marché à 17 ans, avant de créer mon atelier.',
            'inspiration' => 'Trente femmes du quartier travaillent aujourd\'hui avec moi.', 'consentement' => true,
        ])->assertCreated();

        return CathoImpactCandidature::latest('id')->value('id');
    }

    private function deposer(User $u, array $surcharge = []): int
    {
        return $this->withToken($this->tokenFor($u))->postJson('/api/admin/mes-demandes-communication', array_merge([
            'type' => 'formation', 'titre' => 'Session live : bâtir son business plan',
            'message' => 'Annoncer la session live du 28 novembre, ouverte à tous les membres.',
            'date_souhaitee' => now()->addDays(20)->toDateString(), 'canaux' => ['site', 'email'], 'contact' => 'Awa Koné · 0700000001',
        ], $surcharge))->assertCreated()->json('demande.id');
    }

    // ── Cockpit ─────────────────────────────────────────────────────────

    public function test_le_cockpit_exige_la_rubrique_communication(): void
    {
        $this->withToken($this->tokenFor($this->formation()))->getJson('/api/admin/communication/cockpit')->assertForbidden();
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null])))
            ->getJson('/api/admin/communication/cockpit')->assertOk();
    }

    public function test_le_cockpit_donne_les_chiffres_du_mois_et_ce_qui_est_a_traiter(): void
    {
        Mail::fake();
        $com = $this->com();
        $this->reponseCathoImpact('awa@exemple.ci');
        $this->reponseCathoImpact('jean@exemple.ci', 'Jean Yao');
        $urgente = $this->deposer($this->formation(), ['titre' => 'Rappel urgent', 'date_souhaitee' => now()->addDays(2)->toDateString()]);
        $this->deposer($this->formation(), ['titre' => 'Annonce lointaine']);
        User::factory()->create(['role' => 'member']);
        CommunicationPublication::create(['date' => today()->startOfMonth()->toDateString(), 'titre' => 'Publiée', 'type' => 'autre', 'statut' => 'publie']);
        CommunicationPublication::create(['date' => today()->endOfMonth()->toDateString(), 'titre' => 'Prévue', 'type' => 'autre', 'statut' => 'prevu']);
        CommunicationPublication::create(['date' => today()->toDateString(), 'titre' => 'Annulée', 'type' => 'autre', 'statut' => 'annule']);

        $r = $this->withToken($this->tokenFor($com))->getJson('/api/admin/communication/cockpit')->assertOk()
            ->assertJsonPath('mois', now()->format('Y-m'))
            ->assertJsonPath('chiffres.cathoimpact.recues', 2)
            ->assertJsonPath('chiffres.demandes_en_attente', 2)
            ->assertJsonPath('chiffres.demandes_nouvelles', 2)
            ->assertJsonPath('chiffres.publications_prevues', 2)
            ->assertJsonPath('chiffres.publications_publiees', 1)
            ->assertJsonPath('a_traiter.cathoimpact_total', 2)
            ->assertJsonPath('a_traiter.demandes_total', 2)
            ->assertJsonPath('a_traiter.demandes.0.id', $urgente) // la plus proche d'abord
            ->assertJsonPath('a_traiter.demandes.0.urgente', true);
        $this->assertGreaterThanOrEqual(1, $r->json('chiffres.nouveaux_membres'));
        // Pas d'abonnés newsletter ni d'abonnés par réseau.
        $this->assertArrayNotHasKey('abonnes_newsletter', $r->json('chiffres'));
    }

    // ── Synchronisation calendrier → demande ────────────────────────────

    private function demandePlanifiee(User $demandeur, User $com): array
    {
        $id = $this->deposer($demandeur);
        $token = $this->tokenFor($com);
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'])->assertOk();
        $pub = $this->withToken($token)->postJson("/api/admin/demandes-communication/{$id}/planifier", ['date' => now()->addDays(10)->toDateString()])
            ->assertOk()->json('publication_id');

        return [$id, $pub, $token];
    }

    public function test_marquer_publie_dans_le_calendrier_publie_la_demande_et_previent_le_demandeur(): void
    {
        Mail::fake();
        [$id, $pub, $token] = $this->demandePlanifiee($this->formation(['email' => 'demandeur@rejcc.site']), $this->com());

        $this->withToken($token)->postJson("/api/admin/communication/publications/{$pub}/publie", ['lien' => 'https://www.facebook.com/rejcc/posts/1'])->assertOk();

        $d = DemandeCommunication::find($id);
        $this->assertSame('publiee', $d->statut);
        $this->assertSame('https://www.facebook.com/rejcc/posts/1', $d->lien_publication);
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->hasTo('demandeur@rejcc.site') && $m->evenement === 'publiee');

        // Une seconde fois : rien ne bouge, aucun nouvel e-mail.
        $this->withToken($token)->postJson("/api/admin/communication/publications/{$pub}/publie")->assertOk();
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->evenement === 'publiee', 1);
    }

    public function test_le_statut_publie_dans_le_formulaire_du_calendrier_publie_aussi_la_demande(): void
    {
        Mail::fake();
        [$id, $pub, $token] = $this->demandePlanifiee($this->formation(), $this->com());

        $this->withToken($token)->putJson("/api/admin/communication/publications/{$pub}", ['statut' => 'publie'])->assertOk();

        $this->assertSame('publiee', DemandeCommunication::find($id)->statut);
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->evenement === 'publiee', 1);
    }

    public function test_publier_la_demande_ne_repasse_pas_par_le_calendrier_sans_boucle(): void
    {
        Mail::fake();
        [$id, $pub, $token] = $this->demandePlanifiee($this->formation(), $this->com());

        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'publiee'])->assertOk();

        $this->assertSame('publie', CommunicationPublication::find($pub)->statut);
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->evenement === 'publiee', 1);
    }

    public function test_un_testeur_qui_marque_publie_ne_touche_pas_la_demande_d_un_vrai_departement(): void
    {
        Mail::fake();
        [$id, $pub] = $this->demandePlanifiee($this->formation(), $this->com());

        // La publication n'est pas la sienne : refus, ni la publication ni la demande ne changent.
        $this->withToken($this->tokenFor($this->compteTesteurCom()))->postJson("/api/admin/communication/publications/{$pub}/publie")->assertForbidden();

        $this->assertNotSame('publie', CommunicationPublication::find($pub)->statut);
        $this->assertSame('planifiee', DemandeCommunication::find($id)->statut);
        Mail::assertNotSent(DemandeCommunicationSuivi::class, fn ($m) => $m->evenement === 'publiee');
    }

    // ── Comptes de test : pas de données réelles ────────────────────────

    public function test_un_testeur_ne_voit_que_les_reponses_cathoimpact_des_testeurs(): void
    {
        $testeur = $this->compteTesteurCom(['email' => 'Testeur.Com@exemple.ci']);
        $vraie = $this->reponseCathoImpact('candidat@exemple.ci', 'Vrai Candidat');
        $sienne = $this->reponseCathoImpact('testeur.com@exemple.ci', 'Essai Testeur');
        CathoImpactPortrait::create(['nom' => 'Vrai Candidat', 'image_url' => 'https://rejcc.site/a.jpg', 'lien' => 'https://www.linkedin.com/posts/1',
            'reseau' => 'linkedin', 'candidature_id' => $vraie, 'ordre' => 1, 'visible' => true]);
        $token = $this->tokenFor($testeur);

        $this->withToken($token)->getJson('/api/admin/cathoimpact')->assertOk()
            ->assertJsonCount(1, 'candidatures')->assertJsonPath('candidatures.0.id', $sienne)
            ->assertJsonPath('meta.total', 1)->assertJsonPath('compteurs.recu', 1);
        $this->withToken($token)->getJson("/api/admin/cathoimpact/{$vraie}")->assertNotFound();
        $this->withToken($token)->putJson("/api/admin/cathoimpact/{$vraie}", ['statut' => 'retenu'])->assertNotFound();
        $this->withToken($token)->deleteJson("/api/admin/cathoimpact/{$vraie}")->assertNotFound();
        $this->withToken($token)->getJson("/api/admin/cathoimpact/{$vraie}/photo")->assertNotFound();
        $this->withToken($token)->getJson("/api/admin/cathoimpact/{$sienne}")->assertOk();
        $this->withToken($token)->getJson('/api/admin/cathoimpact/resume')->assertOk()
            ->assertJsonPath('chiffres.recus_mois', 1)->assertJsonPath('chiffres.a_traiter', 1)->assertJsonCount(1, 'dernieres');
        $this->withToken($token)->getJson('/api/admin/communication/cockpit')->assertOk()
            ->assertJsonPath('chiffres.cathoimpact.recues', 1)->assertJsonPath('a_traiter.cathoimpact.0.nom_complet', 'Essai Testeur');
        $this->withToken($token)->getJson('/api/admin/communication/bilan')->assertOk()->assertJsonPath('cathoimpact.recues', 1);
        $pastille = collect($this->withToken($token)->getJson('/api/admin/a-traiter')->json('elements'))->firstWhere('cle', 'cathoimpact');
        $this->assertSame(1, $pastille['nombre']);

        // Les cartes de la galerie restent visibles (publiques), sans le lien vers la vraie réponse.
        $this->withToken($token)->getJson('/api/admin/cathoimpact/portraits')->assertOk()
            ->assertJsonCount(1, 'portraits')->assertJsonPath('portraits.0.nom', 'Vrai Candidat')
            ->assertJsonPath('portraits.0.candidature_id', null)->assertJsonPath('portraits.0.candidature_nom', null);

        // Un vrai compte Com voit tout.
        $this->withToken($this->tokenFor($this->com()))->getJson('/api/admin/cathoimpact')->assertJsonPath('meta.total', 2);
    }

    public function test_un_testeur_ne_voit_que_les_demandes_des_testeurs(): void
    {
        Mail::fake();
        $vraie = $this->deposer($this->formation(), ['titre' => 'Vraie demande']);
        $essai = $this->deposer($this->formation(['departement' => 'Formation']), ['titre' => 'Demande du testeur']);
        $testeur = $this->compteTesteurCom();
        $token = $this->tokenFor($testeur);

        $this->withToken($token)->getJson('/api/admin/demandes-communication')->assertOk()
            ->assertJsonCount(1, 'demandes')->assertJsonPath('demandes.0.id', $essai)->assertJsonPath('compteurs.nouvelle', 1);
        $this->withToken($token)->getJson("/api/admin/demandes-communication/{$vraie}")->assertNotFound();
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$vraie}/statut", ['statut' => 'acceptee'])->assertNotFound();
        $this->withToken($token)->postJson("/api/admin/demandes-communication/{$vraie}/commentaires", ['texte' => 'Bonjour'])->assertNotFound();
        $this->withToken($token)->getJson("/api/admin/demandes-communication/{$essai}")->assertOk();
        $this->withToken($token)->getJson('/api/admin/demandes-communication/resume')->assertOk()->assertJsonPath('chiffres.nouvelles', 1);
        $this->withToken($token)->getJson('/api/admin/communication/cockpit')->assertOk()
            ->assertJsonPath('chiffres.demandes_en_attente', 1)->assertJsonPath('a_traiter.demandes.0.id', $essai);
        $this->withToken($token)->getJson('/api/admin/communication/bilan')->assertOk()->assertJsonPath('demandes.recues', 1);
        $this->assertSame('nouvelle', DemandeCommunication::find($vraie)->statut);

        $this->withToken($this->tokenFor($this->com()))->getJson('/api/admin/demandes-communication')->assertJsonCount(2, 'demandes');
    }

    // ── Événements : pas d'envoi de masse par un compte de test ─────────

    public function test_un_testeur_ne_peut_pas_ecrire_ni_annuler_aupres_de_vrais_inscrits(): void
    {
        $testeur = $this->compteTesteurCom();
        $token = $this->tokenFor($testeur);
        $e = Event::create(['title' => 'Forum', 'slug' => 'forum', 'category' => 'Forum', 'statut' => 'publie', 'starts_at' => now()->addDays(10)]);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => User::factory()->create(['role' => 'member'])->id]);

        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/message", ['message' => 'Bonjour à tous les inscrits'])
            ->assertForbidden()->assertJsonPath('ok', false);
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/annuler", ['motif' => 'Essai de test'])->assertForbidden();
        $this->assertSame('publie', $e->fresh()->statut);
        $this->assertSame(0, MemberNotification::count());

        // Report d'un événement publié : préviendrait les vrais inscrits.
        $this->withToken($token)->putJson("/api/admin/events/{$e->id}", ['title' => 'Forum', 'category' => 'Forum', 'starts_at' => now()->addDays(12)->toDateTimeString()])->assertForbidden();

        // Annonce à tous les membres : jamais pour un testeur.
        $this->withToken($token)->postJson('/api/admin/events', ['title' => '[TEST] Atelier', 'category' => 'Atelier', 'starts_at' => now()->addDays(5)->toDateTimeString(), 'annoncer' => true])
            ->assertForbidden();

        // L'équipe, elle, peut écrire aux inscrits.
        $this->withToken($this->tokenFor($this->com()))->postJson("/api/admin/events/{$e->id}/message", ['message' => 'Bonjour à tous les inscrits'])->assertOk();
    }

    public function test_un_testeur_peut_essayer_les_envois_si_seuls_des_testeurs_sont_inscrits(): void
    {
        $testeur = $this->compteTesteurCom();
        $e = Event::create(['title' => '[TEST] Atelier', 'slug' => 'test-atelier', 'category' => 'Atelier', 'statut' => 'publie', 'starts_at' => now()->addDays(10)]);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => User::factory()->create(['role' => 'member', 'departement' => 'Formation'])->id]);

        $this->withToken($this->tokenFor($testeur))->postJson("/api/admin/events/{$e->id}/message", ['message' => 'Message de test aux testeurs'])->assertOk();

        // Un invité sans compte est une vraie personne.
        EventRegistration::create(['event_id' => $e->id, 'prenom' => 'Invité', 'nom' => 'Réel', 'email' => 'invite@exemple.ci']);
        $this->withToken($this->tokenFor($testeur))->postJson("/api/admin/events/{$e->id}/message", ['message' => 'Message de test aux testeurs'])->assertForbidden();
    }
}
