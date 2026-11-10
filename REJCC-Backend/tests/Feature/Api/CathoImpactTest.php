<?php

namespace Tests\Feature\Api;

use App\Mail\RecapCathoImpact;
use App\Models\ApiToken;
use App\Models\CathoImpactCandidature;
use App\Models\CathoImpactPortrait;
use App\Models\User;
use App\Support\CathoImpact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CathoImpact : formulaire court de présélection, suivi par l'espace
 * Communication (statuts, attribution, suppression), galerie publique
 * « Ils ont inspiré » et récapitulatif hebdomadaire.
 */
class CathoImpactTest extends TestCase
{
    use RefreshDatabase;

    private function reponse(array $surcharge = []): array
    {
        return array_merge([
            'profil' => 'entrepreneur',
            'nom_complet' => 'Awa Koné',
            'email' => 'Awa.Kone@exemple.ci',
            'whatsapp' => '0700000000',
            'entreprise' => 'Kone Agro',
            'fonction' => 'Transformation de manioc',
            'secteur' => 'Agroalimentaire',
            'ville' => 'Abidjan',
            'parcours' => 'J\'ai commencé à vendre de l\'attiéké au marché à 17 ans, avant de créer mon atelier.',
            'inspiration' => 'Trente femmes du quartier travaillent aujourd\'hui avec moi.',
            'lien' => 'https://www.linkedin.com/in/awa-kone',
            'photo' => 'cathoimpact/2026/11/'.Str::uuid().'.jpg',
            'consentement' => true,
        ], $surcharge);
    }

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function compteCom(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'is_active' => true,
            'permissions' => ['actualites', 'evenements', 'contenu', 'salons', 'communication'], ...$extra]);
    }

    private function candidature(array $extra = []): CathoImpactCandidature
    {
        $this->postJson('/api/cathoimpact/reponses', $this->reponse($extra))->assertCreated();

        return CathoImpactCandidature::latest('id')->firstOrFail();
    }

    // ── Formulaire public ───────────────────────────────────────────────

    public function test_le_questionnaire_est_public_et_court(): void
    {
        $this->getJson('/api/cathoimpact/questionnaire')->assertOk()
            ->assertJsonPath('consentement', CathoImpact::CONSENTEMENT)
            ->assertJsonPath('parcours_max', 1500)
            ->assertJsonPath('profils.entrepreneur.fonction', 'Votre activité')
            ->assertJsonPath('profils.professionnel.fonction', 'Votre poste');
    }

    public function test_une_reponse_complete_est_enregistree_avec_le_consentement(): void
    {
        $c = $this->candidature();

        $this->assertSame('awa.kone@exemple.ci', $c->email);
        $this->assertSame('recu', $c->statut);
        $this->assertNotNull($c->statut_at);
        $this->assertSame('recu', $c->historique[0]['statut']);
        $this->assertSame(CathoImpact::CONSENTEMENT, $c->consentement_texte);
        $this->assertNotNull($c->consentement_at);
        $this->assertStringStartsWith('cathoimpact/2026/11/', $c->photo);
    }

    public function test_la_photo_et_le_lien_sont_facultatifs(): void
    {
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['photo' => null, 'lien' => null, 'profil' => 'professionnel']))->assertCreated();

        $c = CathoImpactCandidature::firstOrFail();
        $this->assertNull($c->photo);
        $this->assertNull($c->lien);
        $this->assertSame('professionnel', $c->profil);
    }

    public function test_les_champs_obligatoires_et_les_longueurs_sont_controles(): void
    {
        foreach (['parcours', 'inspiration', 'secteur', 'ville', 'entreprise', 'fonction', 'whatsapp'] as $champ) {
            $this->postJson('/api/cathoimpact/reponses', $this->reponse([$champ => '']))->assertStatus(422);
        }
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['parcours' => str_repeat('a', 1501)]))->assertStatus(422);
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['lien' => 'javascript:alert(1)']))->assertStatus(422);
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['profil' => 'autre']))->assertStatus(422);
        $this->assertSame(0, CathoImpactCandidature::count());
    }

    public function test_sans_consentement_rien_n_est_enregistre(): void
    {
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['consentement' => false]))->assertStatus(422);
        $this->assertSame(0, CathoImpactCandidature::count());
    }

    public function test_un_chemin_de_photo_hors_du_dossier_est_refuse(): void
    {
        foreach (['../../.env', 'communaute/2026/11/'.Str::uuid().'.jpg', 'cathoimpact/2026/11/'.Str::uuid().'.php', 'cathoimpact/../../'.Str::uuid().'.jpg'] as $chemin) {
            $this->postJson('/api/cathoimpact/reponses', $this->reponse(['photo' => $chemin]))->assertStatus(422);
        }
        $this->assertSame(0, CathoImpactCandidature::count());
    }

    public function test_les_anciens_champs_sensibles_ne_sont_plus_enregistres(): void
    {
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['date_naissance' => '1998-04-12', 'statut' => 'publie']))->assertCreated();

        $c = CathoImpactCandidature::firstOrFail();
        $this->assertSame('recu', $c->statut);
        $this->assertArrayNotHasKey('date_naissance', $c->getAttributes());
    }

    // ── Espace Communication ────────────────────────────────────────────

    public function test_un_admin_sans_la_rubrique_communication_recoit_403(): void
    {
        $c = $this->candidature();
        $autre = $this->tokenFor(User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique', 'permissions' => ['contenu', 'documents']]));
        $membre = $this->tokenFor(User::factory()->create(['role' => 'member']));

        foreach ([$autre, $membre] as $token) {
            $this->withToken($token)->getJson('/api/admin/cathoimpact')->assertStatus(403);
            $this->withToken($token)->getJson("/api/admin/cathoimpact/{$c->id}")->assertStatus(403);
            $this->withToken($token)->putJson("/api/admin/cathoimpact/{$c->id}", ['statut' => 'retenu'])->assertStatus(403);
            $this->withToken($token)->deleteJson("/api/admin/cathoimpact/{$c->id}")->assertStatus(403);
            $this->withToken($token)->postJson('/api/admin/cathoimpact/portraits', [])->assertStatus(403);
        }
        $this->flushHeaders()->getJson('/api/admin/cathoimpact')->assertStatus(401);
    }

    public function test_la_communication_et_l_administrateur_general_consultent_les_reponses(): void
    {
        $this->candidature();
        $this->candidature(['nom_complet' => 'Jean Yao', 'email' => 'jean@exemple.ci', 'ville' => 'Bouaké']);

        $com = $this->tokenFor($this->compteCom());
        $r = $this->withToken($com)->getJson('/api/admin/cathoimpact')->assertOk();
        $this->assertSame(2, $r->json('meta.total'));
        $this->assertSame(2, $r->json('compteurs.recu'));

        $this->withToken($com)->getJson('/api/admin/cathoimpact?q=Bouaké')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('candidatures.0.nom_complet', 'Jean Yao');
        $this->withToken($com)->getJson('/api/admin/cathoimpact?statut=publie')->assertOk()->assertJsonPath('meta.total', 0);

        $general = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));
        $this->withToken($general)->getJson('/api/admin/cathoimpact')->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_la_fiche_complete_et_la_photo(): void
    {
        $c = $this->candidature();
        $com = $this->tokenFor($this->compteCom());

        $this->withToken($com)->getJson("/api/admin/cathoimpact/{$c->id}")->assertOk()
            ->assertJsonPath('candidature.parcours', $c->parcours)
            ->assertJsonPath('candidature.libelle_fonction', 'Votre activité')
            ->assertJsonPath('candidature.consentement_texte', CathoImpact::CONSENTEMENT);
        $this->withToken($com)->getJson("/api/admin/cathoimpact/{$c->id}/photo")->assertOk()->assertJsonPath('fichier.chemin', $c->photo);

        $sansPhoto = $this->candidature(['photo' => null]);
        $this->withToken($com)->getJson("/api/admin/cathoimpact/{$sansPhoto->id}/photo")->assertStatus(404);
    }

    public function test_les_changements_de_statut_sont_horodates_et_historises(): void
    {
        $c = $this->candidature();
        $agent = $this->compteCom(['prenom' => 'Marc', 'nom' => 'Kouassi']);
        $com = $this->tokenFor($agent);

        $this->travel(1)->hours();
        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", [
            'statut' => 'questionnaire_envoye',
            'lien_questionnaire' => 'https://forms.gle/abc',
            'note_interne' => 'Parcours très fort.',
        ])->assertOk()->assertJsonPath('candidature.statut_label', 'Questionnaire détaillé envoyé');

        $c->refresh();
        $this->assertSame('questionnaire_envoye', $c->statut);
        $this->assertTrue($c->statut_at->isAfter($c->created_at));
        $this->assertCount(2, $c->historique);
        $this->assertSame('Marc Kouassi', $c->historique[1]['par']);
        $this->assertSame('https://forms.gle/abc', $c->lien_questionnaire);

        // Même statut : rien n'est ajouté à l'historique.
        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['statut' => 'questionnaire_envoye'])->assertOk();
        $this->assertCount(2, $c->fresh()->historique);

        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['statut' => 'inconnu'])->assertStatus(422);
        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['lien_publication' => 'pas un lien'])->assertStatus(422);
    }

    public function test_le_community_manager_est_un_compte_com_ou_un_nom_libre(): void
    {
        $c = $this->candidature();
        $agent = $this->compteCom(['prenom' => 'Grace', 'nom' => 'Aka']);
        $com = $this->tokenFor($agent);
        $horsCom = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique']);

        $this->withToken($com)->getJson('/api/admin/cathoimpact/equipe')->assertOk()->assertJsonPath('equipe.0.nom', 'Grace Aka');

        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['community_manager_id' => $horsCom->id])->assertStatus(422);
        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['statut' => 'attribue', 'community_manager_id' => $agent->id])
            ->assertOk()->assertJsonPath('candidature.community_manager', 'Grace Aka');

        $this->withToken($com)->putJson("/api/admin/cathoimpact/{$c->id}", ['community_manager_id' => null, 'community_manager' => 'Agence Lumière'])
            ->assertOk()->assertJsonPath('candidature.community_manager', 'Agence Lumière');
        $this->assertNull($c->fresh()->community_manager_id);
    }

    public function test_la_suppression_renvoie_la_photo_a_effacer(): void
    {
        $c = $this->candidature();
        $portrait = CathoImpactPortrait::create(['nom' => 'Awa', 'image_url' => 'https://rejcc.site/uploads/a.jpg', 'lien' => 'https://instagram.com/p/1', 'reseau' => 'instagram', 'candidature_id' => $c->id]);
        $com = $this->tokenFor($this->compteCom());

        $this->withToken($com)->deleteJson("/api/admin/cathoimpact/{$c->id}")->assertOk()->assertJsonPath('photo', $c->photo);

        $this->assertSame(0, CathoImpactCandidature::count());
        $this->assertNull($portrait->fresh()->candidature_id);
    }

    public function test_le_resume_et_la_pastille_a_traiter(): void
    {
        $this->candidature();
        $publie = $this->candidature(['email' => 'b@exemple.ci']);
        $publie->changerStatut('publie');
        $publie->save();
        $com = $this->tokenFor($this->compteCom());

        $this->withToken($com)->getJson('/api/admin/cathoimpact/resume')->assertOk()
            ->assertJsonPath('chiffres.recus_mois', 2)
            ->assertJsonPath('chiffres.a_traiter', 1)
            ->assertJsonPath('chiffres.publies_mois', 1);

        $elements = collect($this->withToken($com)->getJson('/api/admin/a-traiter')->assertOk()->json('elements'));
        $this->assertSame(1, $elements->firstWhere('cle', 'cathoimpact')['nombre']);
        $this->assertSame('admin.cathoimpact', $elements->firstWhere('cle', 'cathoimpact')['route']);

        $autre = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['contenu']]));
        $this->assertNull(collect($this->withToken($autre)->getJson('/api/admin/a-traiter')->json('elements'))->firstWhere('cle', 'cathoimpact'));
    }

    // ── Galerie « Ils ont inspiré » ─────────────────────────────────────

    public function test_la_galerie_publique_ne_montre_que_les_cartes_visibles_dans_l_ordre(): void
    {
        $com = $this->tokenFor($this->compteCom());
        $carte = fn (string $nom, array $plus = []) => $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', [
            'nom' => $nom, 'accroche' => 'Fondatrice', 'image_url' => 'https://rejcc.site/uploads/media/'.$nom.'.jpg',
            'lien' => 'https://www.instagram.com/p/'.$nom, 'reseau' => 'instagram', ...$plus,
        ])->assertCreated()->json('portrait.id');

        $a = $carte('Awa');
        $b = $carte('Bintou');
        $c = $carte('Cedric', ['visible' => false, 'reseau' => 'linkedin']);

        $this->withToken($com)->putJson('/api/admin/cathoimpact/portraits/ordre', ['ids' => [$c, $b, $a]])->assertOk();

        $r = $this->getJson('/api/cathoimpact/portraits')->assertOk();
        $this->assertSame(['Bintou', 'Awa'], collect($r->json('portraits'))->pluck('nom')->all());
        $this->assertSame('Instagram', $r->json('portraits.0.reseau_label'));

        $this->withToken($com)->putJson("/api/admin/cathoimpact/portraits/{$c}", ['visible' => true])->assertOk();
        $this->withToken($com)->putJson("/api/admin/cathoimpact/portraits/{$a}", ['visible' => false])->assertOk();
        $this->assertSame(['Cedric', 'Bintou'], collect($this->getJson('/api/cathoimpact/portraits')->json('portraits'))->pluck('nom')->all());

        $this->withToken($com)->deleteJson("/api/admin/cathoimpact/portraits/{$b}")->assertOk();
        $this->assertSame(['Cedric'], collect($this->getJson('/api/cathoimpact/portraits')->json('portraits'))->pluck('nom')->all());
    }

    public function test_une_carte_exige_une_image_et_un_lien_en_https(): void
    {
        $com = $this->tokenFor($this->compteCom());
        $base = ['nom' => 'Awa', 'image_url' => 'https://rejcc.site/a.jpg', 'lien' => 'https://youtu.be/x', 'reseau' => 'youtube'];

        $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', ['image_url' => ''] + $base)->assertStatus(422);
        $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', ['lien' => 'javascript:alert(1)'] + $base)->assertStatus(422);
        $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', ['reseau' => 'myspace'] + $base)->assertStatus(422);
        $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', ['candidature_id' => 999] + $base)->assertStatus(422);
        $this->withToken($com)->postJson('/api/admin/cathoimpact/portraits', $base)->assertCreated();
    }

    // ── Récapitulatif hebdomadaire ──────────────────────────────────────

    public function test_le_recap_hebdomadaire_part_a_l_equipe_communication(): void
    {
        Mail::fake();
        config(['mail.admin_email' => 'contact@rejcc.site']);
        $this->compteCom(['email' => 'com1@rejcc.site']);
        $this->compteCom(['email' => 'inactif@rejcc.site', 'is_active' => false]);
        User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique', 'email' => 'juridique@rejcc.site']);

        $ancienne = $this->candidature(['email' => 'ancienne@exemple.ci']);
        $ancienne->forceFill(['created_at' => now()->subDays(20)])->save();
        $ancienne->changerStatut('retenu');
        $ancienne->save();
        $this->candidature();

        $this->artisan('cathoimpact:recap')->assertSuccessful();
        $this->app->terminate();

        Mail::assertSent(RecapCathoImpact::class, 2);
        Mail::assertSent(RecapCathoImpact::class, fn ($m) => $m->hasTo('com1@rejcc.site') && $m->nouvelles->count() === 1 && $m->attente === ['recu' => 1, 'retenu' => 1]);
        Mail::assertSent(RecapCathoImpact::class, fn ($m) => $m->hasTo('contact@rejcc.site'));
        Mail::assertNotSent(RecapCathoImpact::class, fn ($m) => $m->hasTo('inactif@rejcc.site') || $m->hasTo('juridique@rejcc.site'));
    }

    public function test_aucun_recap_si_rien_de_neuf_ni_en_attente(): void
    {
        Mail::fake();
        $this->compteCom();
        $c = $this->candidature();
        $c->forceFill(['created_at' => now()->subDays(20)])->save();
        $c->changerStatut('publie');
        $c->save();

        $this->artisan('cathoimpact:recap')->assertSuccessful();
        $this->app->terminate();

        Mail::assertNothingSent();
    }

    public function test_le_recap_est_planifie_le_lundi_a_8_heures(): void
    {
        $evenement = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'cathoimpact:recap'));

        $this->assertNotNull($evenement);
        $this->assertSame('0 8 * * 1', $evenement->expression);
    }

    // ── Permissions et nettoyage ────────────────────────────────────────

    public function test_la_migration_donne_la_rubrique_aux_comptes_com_existants(): void
    {
        $com = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => ['actualites', 'salons']]);
        $dejaLa = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => ['communication']]);
        $complet = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => null]);
        $autre = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique', 'permissions' => ['contenu']]);

        $migration = require database_path('migrations/2026_11_10_000003_ajouter_rubrique_communication_aux_comptes_com.php');
        $migration->up();

        $this->assertSame(['actualites', 'salons', 'communication'], $com->fresh()->permissions);
        $this->assertSame(['communication'], $dejaLa->fresh()->permissions);
        $this->assertNull($complet->fresh()->permissions);
        $this->assertSame(['contenu'], $autre->fresh()->permissions);
        $this->assertNull(DB::table('users')->where('id', $complet->id)->value('permissions'));

        $migration->down();
        $this->assertSame(['actualites', 'salons'], $com->fresh()->permissions);
    }

    public function test_le_nettoyage_de_campagne_retire_les_reponses_des_testeurs(): void
    {
        User::factory()->create(['departement' => 'Communication', 'email' => 'testeur@exemple.ci']);
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['email' => 'testeur@exemple.ci']))->assertCreated();
        $this->postJson('/api/cathoimpact/reponses', $this->reponse(['email' => 'vraie@exemple.ci']))->assertCreated();
        CathoImpactPortrait::create(['nom' => '[TEST] Carte', 'image_url' => 'https://rejcc.site/a.jpg', 'lien' => 'https://x.com/a', 'reseau' => 'x']);
        CathoImpactPortrait::create(['nom' => 'Vraie carte', 'image_url' => 'https://rejcc.site/b.jpg', 'lien' => 'https://x.com/b', 'reseau' => 'x']);

        $this->artisan('test:nettoyer', ['--force' => true])->assertSuccessful();

        $this->assertSame(['vraie@exemple.ci'], CathoImpactCandidature::pluck('email')->all());
        $this->assertSame(['Vraie carte'], CathoImpactPortrait::pluck('nom')->all());
    }
}
