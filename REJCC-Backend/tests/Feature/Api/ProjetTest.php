<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjetTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function donnees(array $o = []): array
    {
        return $o + [
            'title' => 'AgroVert — séchage de mangues',
            'accroche' => 'Des mangues séchées ivoiriennes pour l’export.',
            'description' => 'Unité de transformation de mangues séchées pour l’export, avec 12 emplois locaux.',
            'probleme' => 'Les mangues invendues pourrissent.',
            'solution' => 'Un séchoir solaire coopératif.',
            'group_id' => Group::firstOrCreate(['slug' => 'agro'], ['name' => 'Agriculture'])->id,
            'stade' => 'developpement',
            'ville' => 'Korhogo',
            'besoins' => ['partenaires', 'financement'],
        ];
    }

    public function test_seuls_les_projets_valides_sont_visibles_des_autres(): void
    {
        $porteur = User::factory()->abonne()->create();
        $autre = User::factory()->abonne()->create();
        $valide = Project::create(['user_id' => $porteur->id, 'title' => 'Projet validé', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        $enCours = Project::create(['user_id' => $porteur->id, 'title' => 'Projet confidentiel', 'description' => str_repeat('b', 30), 'statut' => 'evaluation']);
        Project::create(['user_id' => $porteur->id, 'title' => 'Projet refusé', 'description' => str_repeat('c', 30), 'statut' => 'refuse', 'motif' => 'Hors cadre']);

        $t = $this->tokenFor($autre);
        $res = $this->withToken($t)->getJson('/api/projects')->assertOk()->json();
        $this->assertSame(['Projet validé'], array_column($res['projects'], 'title'));
        $this->assertSame([], $res['mes_projets']);
        $this->assertArrayNotHasKey('motif', $res['projects'][0]);
        $this->withToken($t)->getJson("/api/projects/{$enCours->id}")->assertStatus(404);
        $this->withToken($t)->getJson("/api/projects/{$valide->id}")->assertOk();
        $this->assertSame(1, $valide->fresh()->vues);

        // Le porteur voit tous les siens, avec le motif.
        $mes = $this->withToken($this->tokenFor($porteur))->getJson('/api/projects')->json('mes_projets');
        $this->assertCount(3, $mes);
        $this->assertSame('Hors cadre', collect($mes)->firstWhere('title', 'Projet refusé')['motif']);
    }

    public function test_formulaire_complet_et_validation(): void
    {
        $t = $this->tokenFor(User::factory()->abonne()->create());

        $this->withToken($t)->postJson('/api/projects', $this->donnees(['group_id' => null]))
            ->assertStatus(422)->assertJsonPath('message', 'Choisissez le secteur de votre projet.');
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => ['inconnu']]))->assertStatus(422);

        $p = $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => ['partenaires', 'financement', 'partenaires']]))
            ->assertCreated()->json('project');
        $this->assertSame(['partenaires', 'financement'], $p['besoins']);
        $this->assertSame('Agriculture', $p['groupe']['nom']);
        $this->assertSame('Un séchoir solaire coopératif.', $p['solution']);

        // Au plus 3 projets en évaluation en même temps.
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertCreated();
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertCreated();
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertStatus(422);
    }

    public function test_circuit_completer_resoumettre_valider_refuser(): void
    {
        $porteur = User::factory()->abonne()->create();
        $t = $this->tokenFor($porteur);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $id = $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->json('project.id');

        // Précisions demandées : motif obligatoire, porteur notifié.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'completer'])->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'completer', 'motif' => 'Précisez votre public cible.'])
            ->assertOk()->assertJsonPath('project.statut', 'a_completer');
        $notif = MemberNotification::where('user_id', $porteur->id)->latest('id')->first();
        $this->assertStringStartsWith('Projet à compléter', $notif->title);
        $this->assertStringContainsString('Précisez votre public cible.', $notif->body);
        $this->assertSame("/espace-membre/projets?projet={$id}", $notif->link);

        // Le porteur complète : retour en évaluation.
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees(['cible' => 'Coopératives de femmes du Nord']))
            ->assertOk()->assertJsonPath('resoumis', true)->assertJsonPath('project.statut', 'evaluation');

        // Validation avec stade.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'valider', 'stade' => 'lance'])->assertOk();
        $this->assertSame(['valide', 'lance', null], [Project::find($id)->statut, Project::find($id)->stade, Project::find($id)->motif]);
        $this->assertTrue(MemberNotification::where('user_id', $porteur->id)->where('title', 'like', 'Projet validé%')->exists());

        // Modifier un projet validé le laisse validé.
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertJsonPath('resoumis', false)->assertJsonPath('project.statut', 'valide');

        // Refus motivé, puis le porteur peut retravailler et resoumettre.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'refuser', 'motif' => 'Projet hors du cadre du réseau.'])->assertOk();
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertJsonPath('project.statut', 'evaluation');

        // Retrait : plus visible, plus modifiable.
        $this->withToken($t)->postJson("/api/projects/{$id}/retirer")->assertOk();
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertStatus(422);
        $this->withToken($t)->deleteJson("/api/projects/{$id}")->assertOk();
        $this->assertNull(Project::find($id));
    }

    public function test_un_membre_ne_modifie_pas_le_projet_d_un_autre(): void
    {
        $p = Project::create(['user_id' => User::factory()->create()->id, 'title' => 'Projet', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        $t = $this->tokenFor(User::factory()->abonne()->create());

        $this->withToken($t)->putJson("/api/projects/{$p->id}", $this->donnees())->assertStatus(404);
        $this->withToken($t)->postJson("/api/projects/{$p->id}/retirer")->assertStatus(404);
        $this->withToken($t)->deleteJson("/api/projects/{$p->id}")->assertOk();
        $this->assertNotNull($p->fresh());
    }

    public function test_admin_liste_filtre_et_compteurs(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        Project::create(['title' => 'Alpha', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        Project::create(['title' => 'Bêta', 'description' => str_repeat('b', 30), 'statut' => 'evaluation', 'soumis_at' => now()]);

        $res = $this->withToken($admin)->getJson('/api/admin/projects')->assertOk()->json();
        $this->assertSame('Bêta', $res['projects'][0]['title']); // à traiter en premier
        $this->assertSame(['evaluation' => 1, 'valide' => 1], collect($res['compteurs'])->sortKeys()->all());
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/projects?statut=valide')->json('projects'));
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/projects?q=alph')->json('projects'));
    }

    // ── Collaborer ─────────────────────────────────────────────────────

    private function projetValide(User $porteur): Project
    {
        return Project::create(['user_id' => $porteur->id, 'title' => 'BâtiJeunes', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
    }

    public function test_invitation_acceptee_et_equipe_sur_la_bio(): void
    {
        $porteur = User::factory()->abonne()->create();
        $paul = User::factory()->abonne()->create(['prenom' => 'Paul', 'nom' => 'Assi']);
        $p = $this->projetValide($porteur);
        $tp = $this->tokenFor($porteur);
        $tpaul = $this->tokenFor($paul);

        $this->withToken($tp)->getJson("/api/projects/{$p->id}/candidats?q=paul as")->assertOk()->assertJsonPath('membres.0.id', $paul->id);
        $this->withToken($tp)->postJson("/api/projects/{$p->id}/equipe/inviter", ['user_id' => $paul->id, 'role' => 'Chef de chantier'])
            ->assertOk()->assertJsonPath('statut', 'invite');
        $this->assertTrue(MemberNotification::where('user_id', $paul->id)->where('title', 'like', 'Invitation%')->exists());

        // L'invité voit le projet dans « Mes équipes » et accepte.
        $this->assertSame('invite', $this->withToken($tpaul)->getJson('/api/projects')->json('mes_equipes.0.relation'));
        $lien = $this->withToken($tpaul)->getJson("/api/projects/{$p->id}")->json('project');
        $this->assertSame('invite', $lien['relation']);
        $lienId = \App\Models\ProjectMember::first()->id;
        $this->withToken($tpaul)->postJson("/api/projects/{$p->id}/equipe/{$lienId}/accepter")->assertOk()->assertJsonPath('statut', 'membre');
        $this->assertTrue(MemberNotification::where('user_id', $porteur->id)->where('title', 'like', 'Nouvelle recrue%')->exists());

        $fiche = $this->withToken($tp)->getJson("/api/projects/{$p->id}")->json('project');
        $this->assertSame(2, $fiche['equipe_taille']);
        $this->assertSame('Chef de chantier', $fiche['equipe'][0]['role']);

        // La bio de Paul affiche le projet et son rôle.
        $bio = \App\Support\MemberProfile::payload($paul->fresh(), true);
        $this->assertSame('Chef de chantier', $bio['projets'][0]['role']);

        // Un tiers ne peut pas accepter à la place de Paul ni retirer un membre.
        $tiers = $this->tokenFor(User::factory()->abonne()->create());
        $this->withToken($tiers)->deleteJson("/api/projects/{$p->id}/equipe/{$lienId}")->assertStatus(403);
        // Paul quitte l'équipe : le porteur est prévenu.
        $this->withToken($tpaul)->deleteJson("/api/projects/{$p->id}/equipe/{$lienId}")->assertOk();
        $this->assertTrue(MemberNotification::where('user_id', $porteur->id)->where('title', 'like', "Départ de l'équipe%")->exists());
    }

    public function test_demande_pour_rejoindre_puis_refus(): void
    {
        $porteur = User::factory()->abonne()->create();
        $awa = User::factory()->abonne()->create();
        $p = $this->projetValide($porteur);
        $ta = $this->tokenFor($awa);

        $this->withToken($ta)->postJson("/api/projects/{$p->id}/equipe/rejoindre", ['message' => 'court'])->assertStatus(422);
        $this->withToken($ta)->postJson("/api/projects/{$p->id}/equipe/rejoindre", ['message' => 'Je suis comptable et je peux tenir vos comptes.', 'role' => 'Comptable'])
            ->assertOk()->assertJsonPath('statut', 'demande');
        $this->withToken($ta)->postJson("/api/projects/{$p->id}/equipe/rejoindre", ['message' => 'Encore une demande !'])->assertStatus(422);

        $fiche = $this->withToken($this->tokenFor($porteur))->getJson("/api/projects/{$p->id}")->json('project');
        $this->assertSame('demande', $fiche['en_attente'][0]['statut']);
        // L'équipe en attente n'est pas visible des autres membres.
        $this->assertSame([], $this->withToken($ta)->getJson("/api/projects/{$p->id}")->json('project.en_attente'));

        $this->withToken($this->tokenFor($porteur))->deleteJson("/api/projects/{$p->id}/equipe/{$fiche['en_attente'][0]['id']}")->assertOk();
        $this->assertTrue(MemberNotification::where('user_id', $awa->id)->where('title', 'like', 'Demande non retenue%')->exists());
    }

    public function test_suivre_et_avancees_notifiees(): void
    {
        $porteur = User::factory()->abonne()->create();
        $fan = User::factory()->abonne()->create();
        $p = $this->projetValide($porteur);
        $tf = $this->tokenFor($fan);
        $tp = $this->tokenFor($porteur);

        $this->withToken($tf)->postJson("/api/projects/{$p->id}/suivre")->assertOk()->assertJsonPath('suivi', true)->assertJsonPath('nb_suivis', 1);
        $this->withToken($tf)->postJson("/api/projects/{$p->id}/avancees", ['body' => 'Je ne suis pas de l\'équipe'])->assertStatus(403);
        $this->withToken($tp)->postJson("/api/projects/{$p->id}/avancees", ['body' => 'Premier chantier-école lancé à Yamoussoukro !'])
            ->assertOk()->assertJsonPath('notifies', 1);
        $this->assertTrue(MemberNotification::where('user_id', $fan->id)->where('title', 'Du nouveau sur le projet BâtiJeunes')->exists());

        $fiche = $this->withToken($tf)->getJson("/api/projects/{$p->id}")->json('project');
        $this->assertTrue($fiche['suivi']);
        $this->assertSame('Premier chantier-école lancé à Yamoussoukro !', $fiche['avancees'][0]['body']);
        $this->assertFalse($fiche['avancees'][0]['supprimable']);
        $this->withToken($tf)->deleteJson("/api/projects/{$p->id}/avancees/{$fiche['avancees'][0]['id']}")->assertStatus(403);
        $this->withToken($tp)->deleteJson("/api/projects/{$p->id}/avancees/{$fiche['avancees'][0]['id']}")->assertOk();

        $this->withToken($tf)->postJson("/api/projects/{$p->id}/suivre")->assertJsonPath('suivi', false);
    }

    public function test_message_a_propos_du_projet(): void
    {
        $porteur = User::factory()->abonne()->create();
        $p = $this->projetValide($porteur);
        $t = $this->tokenFor(User::factory()->abonne()->create());

        $this->withToken($t)->postJson('/api/messages', ['recipient_id' => $porteur->id, 'body' => 'Je peux vous aider.', 'project_id' => $p->id])->assertOk();
        $notif = MemberNotification::where('user_id', $porteur->id)->where('type', 'message')->first();
        $this->assertStringContainsString('À propos du projet « BâtiJeunes »', $notif->body);
        $fil = $this->withToken($this->tokenFor($porteur))->getJson('/api/messages/'.\App\Models\Message::first()->sender_id)->json('messages');
        $this->assertSame('BâtiJeunes', $fil[0]['projet']['title']);
    }

    // ── Découverte ─────────────────────────────────────────────────────

    public function test_recherche_filtres_et_tri(): void
    {
        $agro = Group::create(['name' => 'Agriculture', 'slug' => 'agriculture']);
        $btp = Group::create(['name' => 'BTP & Construction', 'slug' => 'btp']);
        $esther = User::factory()->abonne()->create(['prenom' => 'Esther', 'nom' => 'Kouamé']);
        Project::create(['user_id' => $esther->id, 'group_id' => $btp->id, 'title' => 'BâtiJeunes', 'description' => 'Chantiers-écoles pour les jeunes maçons.', 'statut' => 'valide', 'stade' => 'developpement', 'ville' => 'Yamoussoukro', 'besoins' => ['mentor'], 'vues' => 2, 'decide_at' => now()->subDay()]);
        $agv = Project::create(['user_id' => $esther->id, 'group_id' => $agro->id, 'title' => 'AgroVert', 'description' => 'Séchage de mangues pour l’export.', 'statut' => 'valide', 'stade' => 'lance', 'ville' => 'Korhogo', 'besoins' => ['financement', 'partenaires'], 'vues' => 9, 'decide_at' => now()]);
        Project::create(['user_id' => $esther->id, 'group_id' => $agro->id, 'title' => 'Secret', 'description' => 'En évaluation, invisible.', 'statut' => 'evaluation']);
        \App\Models\ProjectFollow::create(['project_id' => $agv->id, 'user_id' => User::factory()->create()->id]);
        $t = $this->tokenFor(User::factory()->abonne()->create());
        $titres = fn ($params) => array_column($this->withToken($t)->getJson('/api/projects?'.http_build_query($params))->json('projects'), 'title');

        $this->assertSame(['AgroVert', 'BâtiJeunes'], $titres([]));
        $this->assertSame(['BâtiJeunes'], $titres(['q' => 'maçon chantiers']));
        $this->assertSame(['AgroVert', 'BâtiJeunes'], $titres(['q' => 'esther', 'tri' => 'vues'])); // recherche sur le porteur, tri par vues
        $this->assertSame(['AgroVert'], $titres(['groupe' => $agro->id]));
        $this->assertSame(['BâtiJeunes'], $titres(['stade' => 'developpement']));
        $this->assertSame(['AgroVert'], $titres(['besoin' => 'financement']));
        $this->assertSame(['BâtiJeunes'], $titres(['ville' => 'Yamoussoukro']));
        $this->assertSame(['AgroVert', 'BâtiJeunes'], $titres(['tri' => 'suivis']));
        $this->assertSame(['Korhogo', 'Yamoussoukro'], $this->withToken($t)->getJson('/api/projects')->json('villes'));
    }

    public function test_apercu_pour_les_non_abonnes(): void
    {
        \App\Support\SubscriptionMode::set(true);
        $agro = Group::create(['name' => 'Agriculture', 'slug' => 'agriculture']);
        Project::create(['user_id' => User::factory()->create()->id, 'group_id' => $agro->id, 'title' => 'AgroVert', 'description' => str_repeat('a', 30), 'statut' => 'valide', 'besoins' => ['financement']]);
        Project::create(['title' => 'Secret', 'description' => str_repeat('b', 30), 'statut' => 'evaluation']);
        $t = $this->tokenFor(User::factory()->create()); // sans abonnement

        $this->withToken($t)->getJson('/api/projects')->assertStatus(402);
        $a = $this->withToken($t)->getJson('/api/projects-apercu')->assertOk()->json('apercu');
        $this->assertSame(1, $a['projets']);
        $this->assertSame(['Agriculture' => 1], $a['secteurs']);
        $this->assertSame(['Financement' => 1], $a['besoins']);
        $this->assertSame(['AgroVert'], array_column($a['exemples'], 'title'));
        $this->assertArrayNotHasKey('porteur', $a['exemples'][0]);
    }

    // ── Administration ─────────────────────────────────────────────────

    public function test_a_la_une_et_export(): void
    {
        $porteur = User::factory()->abonne()->create(['prenom' => 'Esther', 'nom' => 'Kouamé']);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $p = Project::create(['user_id' => $porteur->id, 'title' => 'BâtiJeunes', 'description' => str_repeat('a', 30), 'statut' => 'valide', 'public_ok' => true, 'besoins' => ['mentor'], 'decide_at' => now()->subWeek()]);
        Project::create(['user_id' => $porteur->id, 'title' => 'Plus récent', 'description' => str_repeat('b', 30), 'statut' => 'valide', 'decide_at' => now()]);
        $enEval = Project::create(['user_id' => $porteur->id, 'title' => 'En évaluation', 'description' => str_repeat('c', 30), 'statut' => 'evaluation']);

        $this->withToken($admin)->postJson("/api/admin/projects/{$enEval->id}/une")->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/projects/{$p->id}/une")->assertOk()->assertJsonPath('a_la_une', true);
        $this->assertStringContainsString('site public', MemberNotification::where('user_id', $porteur->id)->latest('id')->first()->body);

        // À la une : en tête de la liste des membres.
        $t = $this->tokenFor(User::factory()->abonne()->create());
        $this->assertSame('BâtiJeunes', $this->withToken($t)->getJson('/api/projects')->json('projects.0.title'));

        $export = $this->withToken($admin)->getJson('/api/admin/export/projets')->assertOk()->json();
        $this->assertContains('À la une', $export['columns']);
        $ligne = collect($export['rows'])->firstWhere(0, 'BâtiJeunes');
        $this->assertSame(['Esther Kouamé', 'Validé', 'Un mentor', 'Oui', 'Oui'], [$ligne[1], $ligne[6], $ligne[8], $ligne[12], $ligne[13]]);
    }

    // ── Site public ────────────────────────────────────────────────────

    public function test_vitrine_publique_avec_accord_du_porteur(): void
    {
        $porteur = User::factory()->create(['prenom' => 'Esther', 'nom' => 'Kouamé', 'email' => 'esther@example.com']);
        $ok = Project::create(['user_id' => $porteur->id, 'title' => 'BâtiJeunes', 'description' => str_repeat('a', 30), 'statut' => 'valide', 'public_ok' => true, 'besoins' => ['mentor'], 'solution' => 'Chantiers-écoles']);
        Project::create(['user_id' => $porteur->id, 'title' => 'Une', 'description' => str_repeat('a', 30), 'statut' => 'valide', 'public_ok' => true, 'a_la_une' => true]);
        $prive = Project::create(['user_id' => $porteur->id, 'title' => 'Sans accord', 'description' => str_repeat('b', 30), 'statut' => 'valide', 'public_ok' => false]);
        Project::create(['user_id' => $porteur->id, 'title' => 'Non validé', 'description' => str_repeat('c', 30), 'statut' => 'evaluation', 'public_ok' => true]);

        $liste = $this->getJson('/api/public-projects')->assertOk()->json('projects');
        $this->assertSame(['Une', 'BâtiJeunes'], array_column($liste, 'title'));
        $this->assertSame('Esther', $liste[1]['porteur']);
        $this->assertStringNotContainsString('esther@example.com', json_encode($liste));
        $this->assertStringNotContainsString('Kouamé', json_encode($liste));

        $this->getJson("/api/public-projects/{$ok->id}")->assertOk()->assertJsonPath('project.solution', 'Chantiers-écoles')
            ->assertJsonPath('project.besoins.0', 'Un mentor');
        $this->getJson("/api/public-projects/{$prive->id}")->assertStatus(404);

        // Le porteur donne son accord depuis son formulaire.
        $t = $this->tokenFor(User::factory()->abonne()->create());
        $this->assertTrue($this->withToken($t)->postJson('/api/projects', $this->donnees(['public_ok' => true]))->assertCreated()->json('project.public_ok'));
    }
}
