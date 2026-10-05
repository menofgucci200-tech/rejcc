<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    private function formation(array $overrides = []): Formation
    {
        return Formation::create($overrides + [
            'title' => 'Prise de parole en public',
            'category' => 'Communication',
            'duration' => '3 semaines',
            'level' => 'Débutant',
            'modules_count' => 6,
        ]);
    }

    public function test_le_catalogue_ne_montre_que_les_formations_publiees(): void
    {
        $this->formation(['title' => 'Publiée']);
        $this->formation(['title' => 'Brouillon', 'is_published' => false]);
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->withToken($token)->getJson('/api/formations')->assertOk();

        $this->assertSame(['Publiée'], array_column($response->json('formations'), 'title'));
    }

    public function test_un_membre_peut_s_inscrire_une_seule_fois(): void
    {
        $formation = $this->formation();
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertOk();
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertOk();

        $this->assertSame(1, FormationEnrollment::count());

        $catalogue = $this->withToken($token)->getJson('/api/formations')->json('formations');
        $this->assertTrue($catalogue[0]['enrolled']);
    }

    public function test_impossible_de_s_inscrire_a_une_formation_non_publiee(): void
    {
        $formation = $this->formation(['is_published' => false]);
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertStatus(404);
    }

    public function test_mes_formations_liste_les_inscriptions_avec_la_progression(): void
    {
        $formation = $this->formation();
        $user = User::factory()->create();
        FormationEnrollment::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
            'progress' => 40,
        ]);

        $response = $this->withToken($this->tokenFor($user))->getJson('/api/my-formations')->assertOk();

        $this->assertSame(40, $response->json('formations.0.progress'));
        $this->assertFalse($response->json('formations.0.completed'));
    }

    public function test_une_formation_sans_contenu_ne_se_valide_plus_au_clic(): void
    {
        // Avant : 4 clics sur « Valider le module » suffisaient pour terminer la
        // formation (et obtenir un certificat) sans aucun contenu suivi.
        $formation = $this->formation(['modules_count' => 4, 'is_certifying' => true]);
        $user = User::factory()->create();
        FormationEnrollment::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
        ]);
        $token = $this->tokenFor($user);

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module")->assertStatus(422);
        }

        $this->assertNull(FormationEnrollment::first()->completed_at);
        $this->assertSame([], $this->withToken($token)->getJson('/api/my-certificates')->json('certificates'));
    }

    public function test_valider_un_module_exige_d_etre_inscrit(): void
    {
        $formation = $this->formation();
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertStatus(404);
    }

    public function test_un_admin_gere_le_cycle_de_vie_d_une_formation(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        // Création
        $id = $this->withToken($token)->postJson('/api/admin/formations', [
            'title' => 'Nouvelle formation',
            'category' => 'Finance',
            'modules_count' => 4,
        ])->assertOk()->json('formation.id');

        // Dépublication
        $this->withToken($token)->putJson("/api/admin/formations/{$id}", [
            'title' => 'Nouvelle formation',
            'category' => 'Finance',
            'is_published' => false,
        ])->assertOk();
        $this->assertFalse(Formation::find($id)->is_published);

        // Suppression
        $this->withToken($token)->deleteJson("/api/admin/formations/{$id}")->assertOk();
        $this->assertNull(Formation::find($id));
    }

    public function test_la_creation_est_validee(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->postJson('/api/admin/formations', ['title' => 'X'])
            ->assertStatus(422);
    }

    public function test_un_membre_ne_peut_pas_gerer_les_formations(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->postJson('/api/admin/formations', [
            'title' => 'Intrusion',
            'category' => 'Test',
        ])->assertStatus(403);
    }

    // ------------------------------------------------------------ Modules réels

    public function test_un_admin_cree_des_modules_et_le_compteur_se_synchronise(): void
    {
        $formation = $this->formation(['modules_count' => 1]);
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->postJson("/api/admin/formations/{$formation->id}/modules", [
            'titre' => 'Introduction', 'description' => 'Les bases.', 'ordre' => 1,
        ])->assertOk();
        $this->withToken($token)->postJson("/api/admin/formations/{$formation->id}/modules", [
            'titre' => 'Aller plus loin', 'video_url' => 'https://youtube.com/x', 'ordre' => 2,
        ])->assertOk();

        $this->assertSame(2, $formation->fresh()->modules_count);
        $this->assertSame(2, $formation->modules()->count());
    }

    public function test_le_contenu_des_modules_exige_d_etre_inscrit(): void
    {
        $formation = $this->formation();
        $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->getJson("/api/formations/{$formation->id}/modules")->assertStatus(404);
    }

    public function test_les_modules_se_debloquent_dans_l_ordre(): void
    {
        $formation = $this->formation();
        $m1 = $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $m2 = $formation->modules()->create(['titre' => 'Module 2', 'ordre' => 2]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);
        $token = $this->tokenFor($user);

        $liste = $this->withToken($token)->getJson("/api/formations/{$formation->id}/modules")
            ->assertOk()->json('modules');

        $this->assertFalse($liste[0]['verrouille']);
        $this->assertTrue($liste[1]['verrouille']);

        // Impossible de valider le module 2 avant le module 1.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m2->id}/complete")
            ->assertStatus(422);

        // Le module 1 se valide, puis le module 2 devient accessible.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m1->id}/complete")
            ->assertOk()->assertJsonPath('progress', 50)->assertJsonPath('completed', false);

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m2->id}/complete")
            ->assertOk()->assertJsonPath('progress', 100)->assertJsonPath('completed', true);
    }

    public function test_l_ancien_compteur_est_desactive_des_qu_il_y_a_des_modules_reels(): void
    {
        $formation = $this->formation();
        $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);

        $this->withToken($this->tokenFor($user))->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertStatus(422);
    }

    public function test_le_contenu_se_suit_sur_la_plateforme_et_les_ressources_sont_reservees_aux_abonnes(): void
    {
        \App\Support\SubscriptionMode::set(true);
        $formation = $this->formation();
        $formation->modules()->create([
            'titre' => 'Module 1', 'ordre' => 1, 'contenu' => '## Leçon', 'document_url' => 'https://exemple.ci/support.pdf',
            'ressources' => [['nom' => 'Modèle.xlsx', 'url' => 'https://exemple.ci/modele.xlsx', 'taille' => '20 Ko']],
        ]);
        $formation->modules()->create(['titre' => 'Module 2', 'ordre' => 2, 'contenu' => 'Secret']);

        $nonAbonne = User::factory()->create(['subscription_expires_at' => null]);
        $abonne = User::factory()->abonne()->create();
        foreach ([$nonAbonne, $abonne] as $u) {
            FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $u->id]);
        }

        $vue = $this->withToken($this->tokenFor($nonAbonne))->getJson("/api/formations/{$formation->id}/modules")->assertOk();
        $this->assertSame('## Leçon', $vue->json('modules.0.contenu'));
        $this->assertSame('https://exemple.ci/support.pdf', $vue->json('modules.0.document_url')); // consultable
        $this->assertNull($vue->json('modules.0.ressources.0.url'));                               // pas téléchargeable
        $this->assertSame('Modèle.xlsx', $vue->json('modules.0.ressources.0.nom'));
        $this->assertFalse($vue->json('telechargement_autorise'));
        $this->assertNull($vue->json('modules.1.contenu'));                                         // module verrouillé : rien n'est envoyé

        $vue = $this->withToken($this->tokenFor($abonne))->getJson("/api/formations/{$formation->id}/modules")->assertOk();
        $this->assertSame('https://exemple.ci/modele.xlsx', $vue->json('modules.0.ressources.0.url'));
        $this->assertTrue($vue->json('telechargement_autorise'));
    }

    public function test_un_module_avec_quiz_se_valide_seulement_en_reussissant_le_quiz(): void
    {
        $formation = $this->formation(['seuil_reussite' => 70]);
        $module = $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1, 'quiz' => [
            ['question' => 'Q1', 'choix' => ['A', 'B'], 'bonne' => 1],
            ['question' => 'Q2', 'choix' => ['A', 'B', 'C'], 'bonne' => 2],
            ['question' => 'Q3', 'choix' => ['A', 'B'], 'bonne' => 0],
        ]]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);
        $token = $this->tokenFor($user);

        // La clé des réponses n'est jamais envoyée au membre.
        $quiz = $this->withToken($token)->getJson("/api/formations/{$formation->id}/modules")->json('modules.0.quiz');
        $this->assertSame(['question' => 'Q1', 'choix' => ['A', 'B']], $quiz[0]);

        $url = "/api/formations/{$formation->id}/modules/{$module->id}/complete";
        $this->withToken($token)->postJson($url)->assertStatus(422);                                  // sans réponse
        $this->withToken($token)->postJson($url, ['reponses' => [1, 0, 1]])->assertStatus(422)          // 1/3 = 33 %
            ->assertJsonPath('quiz.score', 33)->assertJsonPath('quiz.seuil', 70);
        $this->withToken($token)->postJson($url, ['reponses' => [1, 2, 0]])->assertOk()                 // 3/3
            ->assertJsonPath('completed', true)->assertJsonPath('quiz_score', 100);
    }

    public function test_l_admin_ne_peut_pas_enregistrer_un_quiz_incoherent(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $formation = $this->formation();

        $this->withToken($token)->postJson("/api/admin/formations/{$formation->id}/modules", [
            'titre' => 'Module', 'quiz' => [['question' => 'Combien ?', 'choix' => ['Un', 'Deux'], 'bonne' => 5]],
        ])->assertStatus(422);
    }

    public function test_le_certificat_n_est_delivre_qu_apres_l_examen_final(): void
    {
        $formation = $this->formation(['is_certifying' => true, 'seuil_reussite' => 70, 'examen' => [
            ['question' => 'E1', 'choix' => ['A', 'B'], 'bonne' => 0],
            ['question' => 'E2', 'choix' => ['A', 'B'], 'bonne' => 1],
        ]]);
        $module = $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);
        $token = $this->tokenFor($user);
        $examen = "/api/formations/{$formation->id}/examen";

        // Examen inaccessible tant que les modules ne sont pas terminés.
        $this->withToken($token)->getJson($examen)->assertStatus(422);

        // Module terminé : formation pas encore terminée, pas de certificat.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$module->id}/complete")
            ->assertOk()->assertJsonPath('completed', false);
        $this->assertSame([], $this->withToken($token)->getJson('/api/my-certificates')->json('certificates'));
        $this->assertTrue($this->withToken($token)->getJson('/api/my-formations')->json('formations.0.examen_a_passer'));

        // Questions sans la clé.
        $this->assertSame(['question' => 'E1', 'choix' => ['A', 'B']], $this->withToken($token)->getJson($examen)->json('questions.0'));

        // Échec, puis réussite → certificat.
        $this->withToken($token)->postJson($examen, ['reponses' => [1, 0]])->assertStatus(422)->assertJsonPath('score', 0);
        $this->withToken($token)->postJson($examen, ['reponses' => [0, 1]])->assertOk()->assertJsonPath('reussi', true);
        $this->assertCount(1, $this->withToken($token)->getJson('/api/my-certificates')->json('certificates'));
    }

    public function test_trois_echecs_a_l_examen_imposent_une_pause_de_24_h(): void
    {
        $formation = $this->formation(['examen' => [['question' => 'E1', 'choix' => ['A', 'B'], 'bonne' => 0]]]);
        $module = $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);
        $token = $this->tokenFor($user);
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$module->id}/complete");

        $examen = "/api/formations/{$formation->id}/examen";
        $this->withToken($token)->postJson($examen, ['reponses' => [1]])->assertJsonPath('essais_restants', 2);
        $this->withToken($token)->postJson($examen, ['reponses' => [1]])->assertJsonPath('essais_restants', 1);
        $this->withToken($token)->postJson($examen, ['reponses' => [1]])->assertJsonPath('essais_restants', 0);
        $this->withToken($token)->postJson($examen, ['reponses' => [0]])->assertStatus(429);   // même la bonne réponse attend

        $this->travel(25)->hours();
        $this->withToken($token)->postJson($examen, ['reponses' => [0]])->assertOk();
    }
}
