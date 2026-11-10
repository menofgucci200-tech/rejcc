<?php

namespace Tests\Feature\Api;

use App\Mail\DemandeCommunicationRecue;
use App\Mail\DemandeCommunicationSuivi;
use App\Models\ApiToken;
use App\Models\CommunicationPublication;
use App\Models\DemandeCommunication;
use App\Models\User;
use App\Support\PlanDiffusion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Demandes de communication : dépôt par les départements, suivi par le
 * demandeur, traitement par la Communication (statuts, refus motivé,
 * réponses), e-mails, pièces jointes, pastille « À traiter » et plan de diffusion.
 */
class DemandesCommunicationTest extends TestCase
{
    use RefreshDatabase;

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

    private function compteFormation(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'formation', 'is_active' => true,
            'permissions' => ['formations', 'certificats'], ...$extra]);
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'type' => 'formation',
            'titre' => 'Session live : bâtir son business plan',
            'message' => 'Annoncer la session live du 28 novembre, ouverte à tous les membres, inscription sur la plateforme.',
            'date_souhaitee' => now()->addDays(20)->toDateString(),
            'date_evenement' => now()->addDays(25)->toDateString(),
            'canaux' => ['site', 'email', 'whatsapp', 'annonces'],
            'contact' => 'Awa Koné · 0700000001',
        ], $surcharge);
    }

    private function deposer(User $u, array $surcharge = [])
    {
        return $this->withToken($this->tokenFor($u))->postJson('/api/admin/mes-demandes-communication', $this->donnees($surcharge));
    }

    public function test_un_compte_d_un_autre_departement_depose_une_demande(): void
    {
        Mail::fake();
        $formation = $this->compteFormation();

        $r = $this->deposer($formation)->assertCreated()
            ->assertJsonPath('demande.statut', 'nouvelle')
            ->assertJsonPath('demande.type_label', 'Formation')
            ->assertJsonPath('avertissement_delai', null);

        $d = DemandeCommunication::find($r->json('demande.id'));
        $this->assertSame($formation->id, $d->demandeur_id);
        $this->assertSame(['site', 'whatsapp', 'email', 'annonces'], $d->canaux); // dans l'ordre du plan
        $this->assertSame('nouvelle', $d->historique[0]['statut']);
    }

    public function test_la_demande_d_un_testeur_ne_previent_pas_la_vraie_equipe(): void
    {
        Mail::fake();
        $this->compteCom();
        $testeur = User::factory()->create(['role' => 'admin', 'departement' => 'Formation', 'is_active' => true, 'permissions' => ['formations']]);

        $this->deposer($testeur)->assertCreated();

        Mail::assertNotSent(DemandeCommunicationRecue::class);
    }

    public function test_le_departement_est_deduit_du_compte_sinon_administration(): void
    {
        Mail::fake();
        $this->deposer($this->compteFormation(), ['departement' => 'tresorerie'])->assertCreated()
            ->assertJsonPath('demande.departement', 'formation')
            ->assertJsonPath('demande.departement_label', 'Formation');

        $general = User::factory()->create(['role' => 'admin', 'permissions' => null, 'bureau_departement' => null]);
        $this->deposer($general)->assertCreated()
            ->assertJsonPath('demande.departement', null)
            ->assertJsonPath('demande.departement_label', 'Administration');
    }

    public function test_un_simple_membre_ne_peut_pas_deposer(): void
    {
        $membre = User::factory()->create(['role' => 'member']);
        $this->deposer($membre)->assertForbidden();
    }

    public function test_l_avertissement_de_delai_est_renvoye_sans_bloquer(): void
    {
        Mail::fake();
        $u = $this->compteFormation();

        $this->deposer($u, ['date_souhaitee' => now()->addDays(3)->toDateString()])->assertCreated()
            ->assertJsonPath('avertissement_delai', PlanDiffusion::MESSAGE_DELAI)
            ->assertJsonPath('demande.niveau_delai', 'court')
            ->assertJsonPath('demande.urgente', true);
        $this->deposer($u, ['date_souhaitee' => now()->addDays(10)->toDateString()])->assertCreated()
            ->assertJsonPath('avertissement_delai', null)
            ->assertJsonPath('demande.niveau_delai', 'juste');
        $this->deposer($u, ['date_souhaitee' => now()->addDays(14)->toDateString()])->assertCreated()
            ->assertJsonPath('demande.niveau_delai', 'confortable');
        // Une date passée est refusée.
        $this->deposer($u, ['date_souhaitee' => now()->subDay()->toDateString()])->assertStatus(422)->assertJsonValidationErrors('date_souhaitee');
    }

    public function test_les_champs_obligatoires_sont_controles(): void
    {
        $u = $this->compteFormation();
        $this->deposer($u, ['type' => 'inconnu', 'titre' => '', 'canaux' => [], 'contact' => ''])->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'titre', 'canaux', 'contact']);
        $this->deposer($u, ['canaux' => ['telegram']])->assertStatus(422)->assertJsonValidationErrors('canaux.0');
    }

    public function test_un_demandeur_ne_voit_que_ses_demandes(): void
    {
        Mail::fake();
        $moi = $this->compteFormation();
        $autre = $this->compteFormation();
        $this->deposer($moi, ['titre' => 'Ma demande']);
        $idAutre = $this->deposer($autre, ['titre' => 'Demande d\'un autre'])->json('demande.id');

        $token = $this->tokenFor($moi);
        $this->withToken($token)->getJson('/api/admin/mes-demandes-communication')->assertOk()
            ->assertJsonCount(1, 'demandes')->assertJsonPath('demandes.0.titre', 'Ma demande')
            ->assertJsonPath('departement_label', 'Formation');
        $this->withToken($token)->getJson("/api/admin/mes-demandes-communication/{$idAutre}")->assertNotFound();
        $this->withToken($token)->postJson("/api/admin/mes-demandes-communication/{$idAutre}/annuler")->assertNotFound();
        // Sans la rubrique « communication », pas d'accès au traitement.
        $this->withToken($token)->getJson('/api/admin/demandes-communication')->assertForbidden();
        $this->withToken($token)->getJson("/api/admin/demandes-communication/{$idAutre}")->assertForbidden();
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$idAutre}/statut", ['statut' => 'acceptee'])->assertForbidden();
        $this->withToken($token)->getJson('/api/admin/demandes-communication/resume')->assertForbidden();
    }

    public function test_la_communication_et_l_administrateur_general_voient_tout_les_urgentes_d_abord(): void
    {
        Mail::fake();
        $f = $this->compteFormation();
        $this->deposer($f, ['titre' => 'Lointaine', 'date_souhaitee' => now()->addDays(30)->toDateString()]);
        $this->deposer($f, ['titre' => 'Urgente', 'date_souhaitee' => now()->addDays(2)->toDateString()]);
        $publiee = DemandeCommunication::create($this->donnees(['titre' => 'Close', 'demandeur_id' => $f->id, 'statut' => 'publiee', 'date_souhaitee' => now()->toDateString()]));

        $r = $this->withToken($this->tokenFor($this->compteCom()))->getJson('/api/admin/demandes-communication?statut=tous')->assertOk();
        $this->assertSame(['Urgente', 'Lointaine', 'Close'], collect($r->json('demandes'))->pluck('titre')->all());
        $this->assertSame(2, $r->json('compteurs.nouvelle'));

        // Par défaut, seules les demandes ouvertes ; filtres par statut et par département.
        $general = User::factory()->create(['role' => 'admin', 'permissions' => null]);
        $token = $this->tokenFor($general);
        $this->withToken($token)->getJson('/api/admin/demandes-communication')->assertJsonCount(2, 'demandes');
        $this->withToken($token)->getJson('/api/admin/demandes-communication?statut=publiee')->assertJsonCount(1, 'demandes')->assertJsonPath('demandes.0.id', $publiee->id);
        $this->withToken($token)->getJson('/api/admin/demandes-communication?departement=formation')->assertJsonCount(2, 'demandes');
        $this->withToken($token)->getJson('/api/admin/demandes-communication?departement=tresorerie')->assertJsonCount(0, 'demandes');
    }

    public function test_les_statuts_sont_horodates_et_le_refus_exige_un_motif(): void
    {
        Mail::fake();
        $f = $this->compteFormation();
        $id = $this->deposer($f)->json('demande.id');
        $token = $this->tokenFor($this->compteCom(['prenom' => 'Marc', 'nom' => 'Yao']));
        $url = "/api/admin/demandes-communication/{$id}/statut";

        // Pas de saut direct vers « publiée » depuis « nouvelle », ni d'annulation par la Com.
        $this->withToken($token)->putJson($url, ['statut' => 'publiee'])->assertStatus(422);
        $this->withToken($token)->putJson($url, ['statut' => 'annulee'])->assertStatus(422);
        $this->withToken($token)->putJson($url, ['statut' => 'refusee'])->assertStatus(422)->assertJsonValidationErrors('motif');

        $this->withToken($token)->putJson($url, ['statut' => 'acceptee', 'commentaire' => 'Bien reçu, visuel en préparation.'])->assertOk()
            ->assertJsonPath('demande.statut', 'acceptee')->assertJsonPath('demande.annulable', false)
            ->assertJsonPath('demande.derniere_reponse.texte', 'Bien reçu, visuel en préparation.');
        $this->withToken($token)->putJson($url, ['statut' => 'planifiee', 'date_publication_prevue' => now()->addDays(18)->toDateString()])->assertOk()
            ->assertJsonPath('demande.date_publication_prevue', now()->addDays(18)->toDateString());
        $this->withToken($token)->putJson($url, ['statut' => 'publiee', 'lien_publication' => 'https://www.facebook.com/rejcc/posts/1'])->assertOk();

        $d = DemandeCommunication::find($id);
        $this->assertSame('publiee', $d->statut);
        $this->assertNotNull($d->statut_at);
        $this->assertSame(['nouvelle', 'acceptee', 'planifiee', 'publiee'], collect($d->historique)->pluck('statut')->all());
        $this->assertSame('Marc Yao', $d->historique[1]['par']);
        // Une demande publiée est close.
        $this->withToken($token)->putJson($url, ['statut' => 'acceptee'])->assertStatus(422);

        $id2 = $this->deposer($f)->json('demande.id');
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id2}/statut", ['statut' => 'refusee', 'motif' => 'Hors du périmètre de la Communication.'])->assertOk()
            ->assertJsonPath('demande.motif_refus', 'Hors du périmètre de la Communication.');
        // Le demandeur voit le motif.
        $this->withToken($this->tokenFor($f))->getJson("/api/admin/mes-demandes-communication/{$id2}")->assertOk()
            ->assertJsonPath('demande.statut', 'refusee')
            ->assertJsonPath('demande.derniere_reponse.texte', 'Hors du périmètre de la Communication.');
    }

    public function test_l_annulation_par_le_demandeur_seulement_avant_acceptation(): void
    {
        Mail::fake();
        $f = $this->compteFormation();
        $tokenF = $this->tokenFor($f);
        $id = $this->deposer($f)->json('demande.id');
        $this->withToken($tokenF)->postJson("/api/admin/mes-demandes-communication/{$id}/annuler")->assertOk()->assertJsonPath('demande.statut', 'annulee');
        // La Com ne peut plus rien en faire.
        $tokenC = $this->tokenFor($this->compteCom());
        $this->withToken($tokenC)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'])->assertStatus(422);

        $id2 = $this->deposer($f)->json('demande.id');
        $this->withToken($tokenC)->putJson("/api/admin/demandes-communication/{$id2}/statut", ['statut' => 'acceptee'])->assertOk();
        $this->withToken($tokenF)->postJson("/api/admin/mes-demandes-communication/{$id2}/annuler")->assertStatus(422);
        $this->assertSame('acceptee', DemandeCommunication::find($id2)->statut);
    }

    public function test_les_e_mails_partent_aux_bonnes_personnes(): void
    {
        Mail::fake();
        $this->compteCom(['email' => 'com1@rejcc.site']);
        $this->compteCom(['email' => 'com2@rejcc.site']);
        $this->compteCom(['email' => 'inactif@rejcc.site', 'is_active' => false]);
        $this->compteFormation(['email' => 'autre.formation@rejcc.site']);
        $f = $this->compteFormation(['email' => 'demandeur@rejcc.site']);

        $id = $this->deposer($f)->json('demande.id');
        Mail::assertSent(DemandeCommunicationRecue::class, 2);
        Mail::assertSent(DemandeCommunicationRecue::class, fn ($m) => $m->hasTo('com1@rejcc.site'));
        Mail::assertSent(DemandeCommunicationRecue::class, fn ($m) => $m->hasTo('com2@rejcc.site'));
        Mail::assertNotSent(DemandeCommunicationRecue::class, fn ($m) => $m->hasTo('inactif@rejcc.site') || $m->hasTo('demandeur@rejcc.site') || $m->hasTo('autre.formation@rejcc.site'));
        Mail::assertNotSent(DemandeCommunicationSuivi::class);

        $token = $this->tokenFor(User::where('email', 'com1@rejcc.site')->first());
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'])->assertOk();
        $this->withToken($token)->postJson("/api/admin/demandes-communication/{$id}/commentaires", ['texte' => 'Pouvez-vous envoyer le logo du formateur ?'])->assertOk();
        // (En test, les envois différés après la réponse peuvent être rejoués à la requête suivante : on compte les distincts.)
        $suivis = Mail::sent(DemandeCommunicationSuivi::class);
        $this->assertSame(['acceptee', 'commentaire'], $suivis->map(fn ($m) => $m->evenement)->unique()->sort()->values()->all());
        $this->assertTrue($suivis->every(fn ($m) => $m->hasTo('demandeur@rejcc.site')));
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->hasTo('demandeur@rejcc.site') && $m->evenement === 'acceptee');
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->hasTo('demandeur@rejcc.site') && $m->evenement === 'commentaire' && str_contains($m->render(), 'logo du formateur'));

        // Contenu de l'e-mail à l'équipe : département, lien vers la fiche.
        $html = (new DemandeCommunicationRecue(DemandeCommunication::find($id)))->render();
        $this->assertStringContainsString('Formation', $html);
        $this->assertStringContainsString('/admin/communication/demandes?fiche='.$id, $html);
    }

    public function test_les_pieces_jointes_ont_un_chemin_securise(): void
    {
        Mail::fake();
        $f = $this->compteFormation();
        $chemin = 'demandes-communication/2026/11/'.Str::uuid().'.png';
        foreach (['../.env', 'marque/2026/11/'.Str::uuid().'.png', 'demandes-communication/2026/11/visuel.png', 'demandes-communication/2026/11/'.Str::uuid().'.php'] as $mauvais) {
            $this->deposer($f, ['fichiers' => [['chemin' => $mauvais, 'nom' => 'x.png', 'mime' => 'image/png', 'octets' => 100]]])
                ->assertStatus(422)->assertJsonValidationErrors('fichiers.0.chemin');
        }
        $trop = array_fill(0, 4, ['chemin' => $chemin, 'nom' => 'a.png', 'mime' => 'image/png', 'octets' => 100]);
        $this->deposer($f, ['fichiers' => $trop])->assertStatus(422)->assertJsonValidationErrors('fichiers');

        $id = $this->deposer($f, ['fichiers' => [['chemin' => $chemin, 'nom' => 'affiche "live".png', 'mime' => 'image/png', 'octets' => 2048]]])
            ->assertCreated()->assertJsonPath('demande.nb_fichiers', 1)->assertJsonPath('demande.fichiers.0.image', true)
            ->json('demande.id');
        // Le chemin n'est jamais exposé dans la fiche.
        $this->assertStringNotContainsString($chemin, $this->withToken($this->tokenFor($f))->getJson("/api/admin/mes-demandes-communication/{$id}")->getContent());

        // Accès : le demandeur et la Communication ; pas un autre département.
        $this->withToken($this->tokenFor($f))->getJson("/api/admin/demandes-communication/{$id}/fichiers/0")->assertOk()
            ->assertJsonPath('fichier.chemin', $chemin)->assertJsonPath('fichier.nom', 'affiche live.png');
        $this->withToken($this->tokenFor($this->compteCom()))->getJson("/api/admin/demandes-communication/{$id}/fichiers/0")->assertOk();
        $this->withToken($this->tokenFor($this->compteFormation()))->getJson("/api/admin/demandes-communication/{$id}/fichiers/0")->assertForbidden();
        $this->withToken($this->tokenFor($f))->getJson("/api/admin/demandes-communication/{$id}/fichiers/1")->assertNotFound();
    }

    public function test_le_resume_et_la_pastille_a_traiter(): void
    {
        Mail::fake();
        $f = $this->compteFormation();
        $this->deposer($f, ['date_souhaitee' => now()->addDays(2)->toDateString()]);
        $this->deposer($f, ['date_souhaitee' => now()->addDays(30)->toDateString()]);
        $id = $this->deposer($f)->json('demande.id');
        $com = $this->compteCom();
        $token = $this->tokenFor($com);
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'])->assertOk();

        $this->withToken($token)->getJson('/api/admin/demandes-communication/resume')->assertOk()
            ->assertJsonPath('chiffres.nouvelles', 2)
            ->assertJsonPath('chiffres.urgentes', 1)
            ->assertJsonPath('chiffres.en_cours', 1)
            ->assertJsonCount(2, 'demandes')
            ->assertJsonPath('demandes.0.urgente', true);

        $element = collect($this->withToken($token)->getJson('/api/admin/a-traiter')->json('elements'))->firstWhere('cle', 'demandes_communication');
        $this->assertSame(2, $element['nombre']);
        $this->assertSame('admin.demandes-communication', $element['route']);
        // Pas de pastille pour un compte sans la rubrique.
        $this->assertNull(collect($this->withToken($this->tokenFor($f))->getJson('/api/admin/a-traiter')->json('elements'))->firstWhere('cle', 'demandes_communication'));
    }

    public function test_planifier_une_demande_nouvelle_vaut_acceptation_avec_un_seul_e_mail(): void
    {
        Mail::fake();
        $f = $this->compteFormation(['email' => 'demandeur@rejcc.site']);
        $id = $this->deposer($f)->json('demande.id');
        $token = $this->tokenFor($this->compteCom());
        Mail::fake(); // on ne compte que les e-mails de la planification

        $this->withToken($token)->postJson("/api/admin/demandes-communication/{$id}/planifier", ['date' => now()->addDays(12)->toDateString()])->assertOk()
            ->assertJsonPath('demande.statut', 'planifiee');
        $d = \App\Models\DemandeCommunication::find($id);
        // L'acceptation est inscrite à l'historique, juste avant la planification.
        $this->assertSame(['nouvelle', 'acceptee', 'planifiee'], collect($d->historique)->where('type', 'statut')->pluck('statut')->values()->all());
        Mail::assertSent(DemandeCommunicationSuivi::class, 1);
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->hasTo('demandeur@rejcc.site') && $m->evenement === 'planifiee');

        // Une demande refusée ne se planifie pas.
        $refusee = $this->deposer($f)->json('demande.id');
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$refusee}/statut", ['statut' => 'refusee', 'motif' => 'Hors périmètre.'])->assertOk();
        $this->withToken($token)->postJson("/api/admin/demandes-communication/{$refusee}/planifier", ['date' => now()->addDays(12)->toDateString()])->assertStatus(422);
    }

    public function test_planifier_cree_la_publication_du_calendrier_editorial(): void
    {
        Mail::fake();
        $f = $this->compteFormation(['email' => 'demandeur@rejcc.site']);
        $id = $this->deposer($f, ['type' => 'campagne_adhesion', 'canaux' => ['site', 'reseaux']])->json('demande.id');
        $com = $this->compteCom();
        $token = $this->tokenFor($com);
        $date = now()->addDays(16)->toDateString();

        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'])->assertOk();
        $r = $this->withToken($token)->postJson("/api/admin/demandes-communication/{$id}/planifier", ['date' => $date, 'heure' => '18:30', 'responsable_id' => $com->id])->assertOk()
            ->assertJsonPath('demande.statut', 'planifiee')
            ->assertJsonPath('demande.date_publication_prevue', $date)
            ->assertJsonPath('demande.publication.statut', 'prevu');

        $p = CommunicationPublication::find($r->json('publication_id'));
        $this->assertSame($id, $p->demande_id);
        $this->assertSame('adhesion', $p->type);
        $this->assertSame($date, $p->date->toDateString());
        $this->assertContains('site', $p->canaux);
        $this->assertContains('facebook', $p->canaux); // « réseaux sociaux » détaillé en réseaux
        $this->assertNotContains('whatsapp', $p->canaux);
        Mail::assertSent(DemandeCommunicationSuivi::class, fn ($m) => $m->hasTo('demandeur@rejcc.site') && $m->evenement === 'planifiee');

        // Replanifier déplace la même publication, sans doublon.
        $nouvelle = now()->addDays(20)->toDateString();
        $this->withToken($token)->postJson("/api/admin/demandes-communication/{$id}/planifier", ['date' => $nouvelle])->assertOk()->assertJsonPath('replanifiee', true);
        $this->assertSame(1, CommunicationPublication::where('demande_id', $id)->count());
        $this->assertSame($nouvelle, $p->fresh()->date->toDateString());

        // Publier la demande publie aussi la publication du calendrier, avec son lien.
        $this->withToken($token)->putJson("/api/admin/demandes-communication/{$id}/statut", ['statut' => 'publiee', 'lien_publication' => 'https://www.linkedin.com/posts/rejcc-1'])->assertOk();
        $p->refresh();
        $this->assertSame('publie', $p->statut);
        $this->assertNotNull($p->publie_at);
        $this->assertSame(['https://www.linkedin.com/posts/rejcc-1'], $p->liens);
    }

    public function test_le_plan_de_diffusion_est_expose(): void
    {
        $r = $this->withToken($this->tokenFor($this->compteFormation()))->getJson('/api/admin/plan-diffusion')->assertOk();
        $types = collect($r->json('plan.types'))->keyBy('cle');
        $this->assertSame(['email', 'annonces'], $types['compte_rendu']['canaux']);
        $this->assertSame(['site', 'email', 'whatsapp', 'annonces'], $types['formation']['canaux']);
        $this->assertTrue($types['nouvel_evenement']['tous_les_canaux']);
        $this->assertSame(['site', 'reseaux'], $types['campagne_adhesion']['canaux']);
        $this->assertStringContainsString('pas WhatsApp', $r->json('plan.annonces'));
        $this->assertCount(11, $types);
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'member'])))->getJson('/api/admin/plan-diffusion')->assertForbidden();
    }

    public function test_le_nettoyage_de_campagne_retire_les_demandes_des_testeurs(): void
    {
        Mail::fake();
        $testeur = $this->compteFormation(['departement' => 'Formation']);
        $vrai = $this->compteFormation();
        $this->deposer($testeur, ['titre' => 'Demande du testeur']);
        $this->deposer($vrai, ['titre' => '[TEST] Essai depuis un vrai compte']);
        $this->deposer($vrai, ['titre' => 'Vraie demande']);

        $this->artisan('test:nettoyer', ['--force' => true])->assertSuccessful();

        $this->assertSame(['Vraie demande'], DemandeCommunication::pluck('titre')->all());
    }
}
