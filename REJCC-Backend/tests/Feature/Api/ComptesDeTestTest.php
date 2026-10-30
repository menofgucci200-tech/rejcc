<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\Group;
use App\Models\MarketplaceListing;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Salon;
use App\Models\SalonMessage;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ComptesDeTestTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function csv(string $contenu): string
    {
        $f = tempnam(sys_get_temp_dir(), 'comptes').'.csv';
        file_put_contents($f, $contenu);

        return $f;
    }

    // ── Retours des testeurs ────────────────────────────────────────────────

    public function test_un_testeur_signale_un_bug_avec_la_page_et_le_navigateur(): void
    {
        $token = $this->tokenFor(User::factory()->create(['departement' => 'Communication']));

        $this->withToken($token)->withHeader('X-Client-Agent', 'Mozilla/5.0 (iPhone)')->postJson('/api/suggestions', [
            'categorie' => 'bug',
            'message' => 'Le bouton Enregistrer ne réagit pas sur mon profil.',
            'page' => '/mon-espace/profil',
        ])->assertCreated()
            ->assertJsonPath('suggestion.departement', 'Communication')
            ->assertJsonPath('suggestion.navigateur', 'Mozilla/5.0 (iPhone)');

        $this->withToken($token)->postJson('/api/suggestions', ['categorie' => 'bug', 'message' => 'court'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/suggestions', ['categorie' => 'inconnue', 'message' => 'Message suffisamment long'])->assertStatus(422);
        $this->withToken($token)->getJson('/api/suggestions')->assertOk()->assertJsonCount(1, 'suggestions');
    }

    public function test_un_vrai_membre_ne_peut_pas_deposer_de_retour(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson('/api/suggestions', ['categorie' => 'bug', 'message' => 'Un message assez long'])->assertForbidden();
        $this->withToken($token)->getJson('/api/suggestions')->assertForbidden();
        $this->assertSame(0, Suggestion::count());
    }

    public function test_l_admin_consulte_filtre_et_traite_les_retours(): void
    {
        Suggestion::create(['departement' => 'Communication', 'categorie' => 'bug', 'message' => 'Retour A']);
        $b = Suggestion::create(['departement' => 'Formation', 'categorie' => 'besoin', 'message' => 'Retour B']);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($admin)->getJson('/api/admin/suggestions')->assertOk()->assertJsonCount(2, 'suggestions')->assertJsonCount(2, 'departements');
        $this->withToken($admin)->getJson('/api/admin/suggestions?departement=Formation')->assertOk()->assertJsonCount(1, 'suggestions');
        $this->withToken($admin)->putJson("/api/admin/suggestions/{$b->id}", ['statut' => 'corrige', 'reponse' => 'Corrigé le 22/10.'])
            ->assertOk()->assertJsonPath('suggestion.statut', 'corrige');
        $this->assertCount(2, $this->withToken($admin)->getJson('/api/admin/export/suggestions')->assertOk()->json('rows'));
        $this->withToken($admin)->getJson('/api/admin/a-traiter')->assertOk()->assertJsonFragment(['cle' => 'suggestions', 'nombre' => 1]);

        $restreint = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['actualites']]));
        $this->withToken($restreint)->getJson('/api/admin/suggestions')->assertForbidden();
    }

    // ── Droits ──────────────────────────────────────────────────────────────

    public function test_un_admin_testeur_n_accede_jamais_aux_envois_ni_aux_adhesions(): void
    {
        // Même sans liste de rubriques (accès complet en principe).
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null, 'departement' => 'Direction']));

        $this->withToken($token)->getJson('/api/admin/formations')->assertOk();
        $this->withToken($token)->getJson('/api/admin/membership-applications')->assertForbidden();
        $this->withToken($token)->postJson('/api/admin/notifications/broadcast', ['title' => 'Bonjour à tous'])->assertForbidden();
        $this->withToken($token)->getJson('/api/admin/export/newsletter')->assertForbidden();
        $this->withToken($token)->getJson('/api/admin/export/candidatures')->assertForbidden();
        // Communauté : la gestion des salons publie des annonces à tous les membres.
        $this->withToken($token)->getJson('/api/admin/communaute')->assertForbidden();
        $this->withToken($token)->postJson('/api/admin/communaute/salons', ['nom' => 'Salon de test'])->assertForbidden();
    }

    public function test_un_admin_restreint_n_exporte_que_les_donnees_de_ses_rubriques(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['evenements']]));

        $this->withToken($token)->getJson('/api/admin/export/evenements')->assertOk();
        $this->withToken($token)->getJson('/api/admin/export/members')->assertForbidden();
        $this->withToken($token)->getJson('/api/admin/export/paiements')->assertForbidden();
        $this->withToken($token)->getJson('/api/admin/export/contacts')->assertForbidden();

        $complet = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));
        $this->withToken($complet)->getJson('/api/admin/export/members')->assertOk();
    }

    // ── Création des comptes ────────────────────────────────────────────────

    public function test_creation_des_comptes_de_test(): void
    {
        $fichier = $this->csv("departement;prenom;nom;email;acces;sections\n"
            ."Communication;Awa;Koné;Awa.Kone@exemple.ci;admin;actualites|evenements\n"
            ."Communication;Awa;Koné;awa.membre@exemple.ci;membre;\n"
            ."Direction;Paul;Yao;paul@exemple.ci;admin;\n");

        $this->artisan('test:comptes', ['fichier' => $fichier])->assertSuccessful();

        $admin = User::where('email', 'awa.kone@exemple.ci')->first();
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Communication', $admin->departement);
        $this->assertSame(['actualites', 'evenements'], $admin->permissions);
        $this->assertNotNull($admin->email_verified_at);

        // Admin sans rubrique précisée : tout, sauf les rubriques interdites aux testeurs.
        $direction = User::where('email', 'paul@exemple.ci')->first()->permissions;
        $this->assertContains('formations', $direction);
        $this->assertNotContains('newsletter', $direction);
        $this->assertNotContains('adhesions', $direction);
        $this->assertNotContains('salons', $direction);

        $membre = User::where('email', 'awa.membre@exemple.ci')->first();
        $this->assertSame('member', $membre->role);
        $this->assertTrue($membre->hasActiveSubscription());

        // Relancer ne change pas les mots de passe déjà communiqués.
        $hash = $admin->password;
        $this->artisan('test:comptes', ['fichier' => $fichier])->assertSuccessful();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertSame(3, User::count());
    }

    public function test_un_vrai_compte_n_est_jamais_transforme_en_compte_de_test(): void
    {
        $vrai = User::factory()->create(['email' => 'tresorier@rejcc.site', 'role' => 'admin', 'permissions' => null]);

        $this->artisan('test:comptes', ['fichier' => $this->csv("Finances;Jean;Yao;tresorier@rejcc.site;membre;\n")])->assertFailed();

        $vrai->refresh();
        $this->assertNull($vrai->departement);
        $this->assertSame('admin', $vrai->role);
    }

    public function test_un_csv_invalide_ne_cree_aucun_compte(): void
    {
        $fichier = $this->csv("Communication;Awa;Koné;awa@exemple.ci;admin;actualites\n"
            ."Formation;Jean;Yao;pas-un-email;superadmin;inconnue\n"
            ."Direction;Paul;Yao;paul@exemple.ci;admin;newsletter\n"
            ."Communication;Marie;Kra;marie@exemple.ci;admin;salons\n");

        $this->artisan('test:comptes', ['fichier' => $fichier])->assertFailed();
        $this->assertSame(0, User::count());
    }

    // ── Fin de campagne ─────────────────────────────────────────────────────

    public function test_le_nettoyage_supprime_les_testeurs_et_leur_contenu_et_rien_d_autre(): void
    {
        $groupe = Group::first();
        $testeur = User::factory()->create(['departement' => 'Communication']);
        $vrai = User::factory()->create();
        $projet = fn (User $u, string $titre) => Project::create(['user_id' => $u->id, 'title' => $titre, 'description' => 'Description du projet', 'statut' => 'evaluation']);
        $annonce = fn (User $u, string $titre) => MarketplaceListing::create(['user_id' => $u->id, 'type' => 'service', 'title' => $titre, 'group_id' => $groupe->id, 'category' => $groupe->name, 'description' => 'Description', 'statut' => 'approuve']);
        $evenement = fn (string $titre) => Event::create(['title' => $titre, 'slug' => Str::slug($titre), 'category' => 'Atelier', 'location' => 'Abidjan', 'starts_at' => now()->addWeek()]);

        $projet($testeur, 'Projet du testeur');
        $projet($vrai, 'Vrai projet');
        $annonce($testeur, 'Annonce du testeur');
        $annonce($vrai, 'Vraie annonce');
        Opportunity::create(['title' => 'Offre du testeur', 'description' => 'Description', 'type' => 'stage', 'entreprise' => 'Test', 'lieu' => 'Abidjan', 'author_id' => $testeur->id, 'statut' => 'publiee']);
        $evenement('[TEST] Atelier fictif');
        $evenement('Vrai forum');
        Suggestion::create(['user_id' => $testeur->id, 'departement' => 'Communication', 'categorie' => 'bug', 'message' => 'Bug à garder']);
        $salon = Salon::firstOrFail();
        SalonMessage::create(['salon_id' => $salon->id, 'user_id' => $testeur->id, 'type' => 'texte', 'body' => 'Message du testeur']);
        SalonMessage::create(['salon_id' => $salon->id, 'user_id' => $vrai->id, 'type' => 'texte', 'body' => 'Vrai message']);

        $this->artisan('test:nettoyer', ['--force' => true])->assertSuccessful();

        $this->assertNull(User::find($testeur->id));
        $this->assertNotNull(User::find($vrai->id));
        $this->assertSame(['Vrai projet'], Project::pluck('title')->all());
        $this->assertSame(['Vraie annonce'], MarketplaceListing::pluck('title')->all());
        $this->assertSame(0, Opportunity::count());
        $this->assertSame(['Vrai forum'], Event::pluck('title')->all());
        // Pas de message « compte supprimé » laissé dans les salons.
        $this->assertSame(['Vrai message'], SalonMessage::pluck('body')->all());

        $retour = Suggestion::first();
        $this->assertNull($retour->user_id);
        $this->assertSame('Communication', $retour->departement);
    }

    public function test_le_nettoyage_sans_confirmation_ne_supprime_rien(): void
    {
        User::factory()->create(['departement' => 'Communication']);

        $this->artisan('test:nettoyer')->expectsConfirmation('Supprimer définitivement ces éléments du site ?', 'no')->assertFailed();
        $this->assertSame(1, User::count());
    }
}
