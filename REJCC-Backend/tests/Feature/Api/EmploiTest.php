<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmploiTest extends TestCase
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
            'title' => 'Développeur web junior',
            'type' => 'emploi',
            'contrat' => 'cdd',
            'entreprise' => 'Ivoire Tech',
            'group_id' => Group::firstOrCreate(['slug' => 'info'], ['name' => 'Informatique & Technologie'])->id,
            'lieu' => 'Abidjan, Cocody',
            'teletravail' => 'hybride',
            'remuneration' => '350 000 F / mois',
            'description' => 'Nous recherchons un développeur web junior pour notre équipe produit.',
            'missions' => 'Développer les interfaces.',
            'profil' => 'Bac+2 en informatique.',
            'competences' => ['Laravel', ' PHP ', 'Laravel', ''],
        ];
    }

    public function test_publication_reservee_aux_abonnes(): void
    {
        SubscriptionMode::set(true);
        $this->withToken($this->tokenFor(User::factory()->create()))->postJson('/api/opportunities', $this->donnees())->assertStatus(402);
        $this->withToken($this->tokenFor(User::factory()->abonne()->create()))->postJson('/api/opportunities', $this->donnees())->assertCreated();
    }

    public function test_circuit_de_validation(): void
    {
        $auteur = User::factory()->abonne()->create();
        $t = $this->tokenFor($auteur);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $autre = $this->tokenFor(User::factory()->create());

        $this->withToken($t)->postJson('/api/opportunities', $this->donnees(['deadline' => today()->subDay()->toDateString()]))
            ->assertStatus(422)->assertJsonPath('message', "La date limite de candidature doit être aujourd'hui ou plus tard.");
        $this->withToken($t)->postJson('/api/opportunities', $this->donnees(['type' => 'annonce']))->assertStatus(422);

        $o = $this->withToken($t)->postJson('/api/opportunities', $this->donnees())->assertCreated()->json('opportunity');
        $this->assertSame('en_attente', $o['statut']);
        $this->assertSame(['Laravel', 'PHP'], $o['competences']);
        $this->assertSame('cdd', $o['contrat']);

        // Pas visible des autres avant validation.
        $this->assertSame([], $this->withToken($autre)->getJson('/api/opportunities')->json('opportunities'));
        $this->withToken($autre)->getJson("/api/opportunities/{$o['id']}")->assertStatus(404);
        $this->assertCount(1, $this->withToken($t)->getJson('/api/opportunities')->json('mes_offres'));

        // Correction demandée → l'auteur corrige → renvoyée en validation.
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$o['id']}/decision", ['decision' => 'corriger'])->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$o['id']}/decision", ['decision' => 'corriger', 'motif' => 'Précisez la rémunération.'])->assertOk();
        $this->assertTrue(MemberNotification::where('user_id', $auteur->id)->where('title', 'like', 'Offre à corriger%')->exists());
        $this->withToken($t)->putJson("/api/opportunities/{$o['id']}", $this->donnees())->assertOk()->assertJsonPath('resoumise', true)
            ->assertJsonPath('opportunity.statut', 'en_attente');

        // Publication : visible, expiration à 60 jours, auteur notifié.
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$o['id']}/decision", ['decision' => 'publier'])->assertOk();
        $offre = Opportunity::find($o['id']);
        $this->assertSame(today()->addDays(60)->toDateString(), $offre->expire_le->toDateString());
        $this->assertSame('Développeur web junior', $this->withToken($autre)->getJson('/api/opportunities')->json('opportunities.0.title'));
        $fiche = $this->withToken($autre)->getJson("/api/opportunities/{$o['id']}")->assertOk()->json('opportunity');
        $this->assertArrayNotHasKey('contact', $fiche);
        $this->assertSame('Bac+2 en informatique.', $fiche['profil']);
        $this->assertSame(1, $offre->fresh()->vues);
    }

    public function test_pourvue_prolongee_expiree_et_rappel(): void
    {
        $auteur = User::factory()->abonne()->create();
        $t = $this->tokenFor($auteur);
        $autre = $this->tokenFor(User::factory()->create());
        $o = Opportunity::create($this->donnees() + ['author_id' => $auteur->id, 'statut' => 'publiee', 'publie_at' => now(), 'expire_le' => today()->addDays(2)]);
        unset($o->competences);

        // Rappel 3 jours avant l'expiration, une seule fois.
        $this->assertSame(1, Opportunity::rappelsExpiration());
        $this->assertSame(0, Opportunity::rappelsExpiration());
        $this->assertTrue(MemberNotification::where('user_id', $auteur->id)->where('title', 'like', 'Votre offre expire bientôt%')->exists());

        // Prolonger : +30 jours.
        $this->withToken($t)->postJson("/api/opportunities/{$o->id}/prolonger")->assertOk()
            ->assertJsonPath('expire_le', today()->addDays(32)->toDateString());

        // Expirée : masquée des membres, signalée à l'auteur.
        $o->update(['expire_le' => today()->subDay()]);
        $this->assertSame([], $this->withToken($autre)->getJson('/api/opportunities')->json('opportunities'));
        $this->assertSame('expiree', $this->withToken($t)->getJson('/api/opportunities')->json('mes_offres.0.statut'));

        // Pourvue puis rouverte.
        $o->update(['expire_le' => today()->addDays(10)]);
        $this->withToken($t)->postJson("/api/opportunities/{$o->id}/statut", ['statut' => 'pourvue'])->assertOk();
        $this->assertSame([], $this->withToken($autre)->getJson('/api/opportunities')->json('opportunities'));
        $this->withToken($t)->postJson("/api/opportunities/{$o->id}/statut", ['statut' => 'publiee'])->assertOk();
        $this->assertCount(1, $this->withToken($autre)->getJson('/api/opportunities')->json('opportunities'));

        // Un autre membre ne peut ni modifier ni supprimer.
        $this->withToken($autre)->putJson("/api/opportunities/{$o->id}", $this->donnees())->assertStatus(404);
        $this->withToken($autre)->deleteJson("/api/opportunities/{$o->id}")->assertOk();
        $this->assertNotNull($o->fresh());
    }

    public function test_admin_liste_publie_et_retire(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $auteur = User::factory()->abonne()->create();
        Opportunity::create($this->donnees(['title' => 'En attente']) + ['author_id' => $auteur->id, 'statut' => 'en_attente']);
        $vieille = Opportunity::create($this->donnees(['title' => 'Expirée']) + ['author_id' => $auteur->id, 'statut' => 'publiee', 'expire_le' => today()->subDay()]);

        $res = $this->withToken($admin)->getJson('/api/admin/opportunities')->assertOk()->json();
        $this->assertSame('En attente', $res['opportunities'][0]['title']);
        $this->assertSame(1, $res['compteurs']['expiree']);
        $this->assertSame(0, $res['compteurs']['publiee']);
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/opportunities?statut=expiree')->json('opportunities'));

        // L'admin publie directement.
        $this->withToken($admin)->postJson('/api/admin/opportunities', $this->donnees(['title' => 'Offre REJCC']))->assertCreated()
            ->assertJsonPath('opportunity.statut', 'publiee');

        // Retrait motivé.
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$vieille->id}/decision", ['decision' => 'retirer', 'motif' => 'Offre frauduleuse.'])->assertOk();
        $this->assertTrue(MemberNotification::where('user_id', $auteur->id)->where('title', 'like', 'Offre retirée%')->exists());
    }

    // ── Candidatures ───────────────────────────────────────────────────

    private function offreEnLigne(User $auteur): Opportunity
    {
        return Opportunity::create($this->donnees(['competences' => ['Laravel']]) + ['author_id' => $auteur->id, 'statut' => 'publiee', 'publie_at' => now(), 'expire_le' => today()->addMonth()]);
    }

    public function test_postuler_et_suivi_des_candidatures(): void
    {
        SubscriptionMode::set(true);
        $recruteur = User::factory()->abonne()->create();
        $candidat = User::factory()->create(['prenom' => 'Koffi', 'nom' => 'Yao', 'email' => 'koffi@example.com']); // non abonné : peut postuler
        $o = $this->offreEnLigne($recruteur);
        $tr = $this->tokenFor($recruteur);
        $tc = $this->tokenFor($candidat);

        $this->withToken($tr)->postJson("/api/opportunities/{$o->id}/postuler", ['message' => str_repeat('a', 40)])->assertStatus(422);
        $this->withToken($tc)->postJson("/api/opportunities/{$o->id}/postuler", ['message' => 'Trop court'])->assertStatus(422);
        $this->withToken($tc)->postJson("/api/opportunities/{$o->id}/postuler", [
            'message' => 'Développeur Laravel depuis deux ans, je suis très motivé par votre offre.',
            'cv_url' => 'https://rejcc.site/uploads/cv-koffi.pdf', 'cv_name' => 'cv-koffi.pdf',
        ])->assertCreated();
        $this->withToken($tc)->postJson("/api/opportunities/{$o->id}/postuler", ['message' => str_repeat('b', 40)])
            ->assertStatus(422)->assertJsonPath('message', 'Vous avez déjà postulé à cette offre.');
        $this->assertTrue(MemberNotification::where('user_id', $recruteur->id)->where('title', 'like', 'Nouvelle candidature%')->exists());

        // Le candidat voit son statut ; le recruteur voit le nombre de nouvelles candidatures.
        $this->assertSame('recue', $this->withToken($tc)->getJson("/api/opportunities/{$o->id}")->json('opportunity.ma_candidature.statut'));
        $this->assertSame(1, $this->withToken($tr)->getJson("/api/opportunities/{$o->id}")->json('opportunity.nb_nouvelles'));

        // Liste des candidatures : réservée au recruteur, avec coordonnées du candidat.
        $this->withToken($tc)->getJson("/api/opportunities/{$o->id}/candidatures")->assertStatus(404);
        $c = $this->withToken($tr)->getJson("/api/opportunities/{$o->id}/candidatures")->assertOk()->json('candidatures.0');
        $this->assertSame('koffi@example.com', $c['candidat']['email']);
        $this->assertSame('cv-koffi.pdf', $c['cv_name']);
        $this->assertSame(0, $this->withToken($tr)->getJson("/api/opportunities/{$o->id}")->json('opportunity.nb_nouvelles'));

        // Présélection puis retenue : le candidat est notifié, la note reste privée.
        $this->withToken($tr)->postJson("/api/opportunities/{$o->id}/candidatures/{$c['id']}/statut", ['statut' => 'preselection', 'note' => 'Profil solide'])->assertOk();
        $this->withToken($tr)->postJson("/api/opportunities/{$o->id}/candidatures/{$c['id']}/statut", ['statut' => 'retenue', 'message' => 'Je vous appelle demain.'])->assertOk();
        $n = MemberNotification::where('user_id', $candidat->id)->where('title', 'like', 'Candidature retenue%')->first();
        $this->assertStringContainsString('Je vous appelle demain.', $n->body);
        $this->assertTrue(MemberNotification::where('user_id', $candidat->id)->where('title', 'like', 'Candidature présélectionnée%')->exists());

        $mes = $this->withToken($tc)->getJson('/api/mes-candidatures')->assertOk()->json('candidatures');
        $this->assertSame('retenue', $mes[0]['statut']);
        $this->assertArrayNotHasKey('note', $mes[0]);

        // Une candidature retenue ne se retire plus.
        $this->withToken($tc)->deleteJson("/api/opportunities/{$o->id}/candidature")->assertStatus(422);
    }

    public function test_retrait_et_offre_fermee(): void
    {
        $recruteur = User::factory()->abonne()->create();
        $o = $this->offreEnLigne($recruteur);
        $tc = $this->tokenFor(User::factory()->create());

        $this->withToken($tc)->postJson("/api/opportunities/{$o->id}/postuler", ['message' => str_repeat('Motivé. ', 6)])->assertCreated();
        $this->withToken($tc)->deleteJson("/api/opportunities/{$o->id}/candidature")->assertOk();
        $this->assertSame(0, $this->withToken($this->tokenFor($recruteur))->getJson("/api/opportunities/{$o->id}")->json('opportunity.nb_candidatures'));
        // Après retrait, on peut postuler de nouveau.
        $this->withToken($tc)->postJson("/api/opportunities/{$o->id}/postuler", ['message' => str_repeat('Motivé. ', 6)])->assertCreated();

        $o->update(['statut' => 'pourvue']);
        $this->withToken($this->tokenFor(User::factory()->create()))->postJson("/api/opportunities/{$o->id}/postuler", ['message' => str_repeat('Motivé. ', 6)])
            ->assertStatus(422)->assertJsonPath('message', "Cette offre n'accepte plus de candidatures.");
    }

    public function test_message_a_propos_de_l_offre(): void
    {
        $recruteur = User::factory()->abonne()->create();
        $candidat = User::factory()->abonne()->create();
        $o = $this->offreEnLigne($recruteur);
        \App\Models\OpportunityApplication::create(['opportunity_id' => $o->id, 'user_id' => $candidat->id, 'message' => str_repeat('a', 40)]);

        $this->withToken($this->tokenFor($recruteur))->postJson('/api/messages', ['recipient_id' => $candidat->id, 'body' => 'Êtes-vous disponible lundi ?', 'opportunity_id' => $o->id])->assertOk();
        $this->assertSame($o->id, \App\Models\Message::first()->opportunity_id);
        $this->assertStringContainsString("À propos de l'offre", MemberNotification::where('user_id', $candidat->id)->where('type', 'message')->first()->body);

        // Un tiers ne peut pas rattacher l'offre.
        $tiers = User::factory()->abonne()->create();
        $this->withToken($this->tokenFor($tiers))->postJson('/api/messages', ['recipient_id' => $recruteur->id, 'body' => 'Bonjour', 'opportunity_id' => $o->id])->assertOk();
        $this->assertNull(\App\Models\Message::latest('id')->first()->opportunity_id);
    }

    // ── Découverte et alertes ──────────────────────────────────────────

    public function test_recherche_filtres_favoris(): void
    {
        $auteur = User::factory()->abonne()->create();
        $agro = Group::create(['name' => 'Agriculture', 'slug' => 'agro']);
        $base = ['author_id' => $auteur->id, 'statut' => 'publiee', 'publie_at' => now(), 'expire_le' => today()->addMonth()];
        $dev = Opportunity::create($this->donnees(['competences' => ['Laravel']]) + $base + ['vues' => 3]);
        $stage = Opportunity::create($this->donnees(['title' => 'Stage agronome', 'type' => 'stage', 'contrat' => null, 'group_id' => $agro->id, 'lieu' => 'Korhogo', 'teletravail' => 'sur_site', 'description' => 'Suivi des plantations de mangues.', 'missions' => null, 'profil' => null, 'competences' => []]) + $base + ['deadline' => today()->addDays(5)]);
        $t = $this->tokenFor(User::factory()->create());
        $titres = fn ($p) => array_column($this->withToken($t)->getJson('/api/opportunities?'.http_build_query($p))->json('opportunities'), 'title');

        $this->assertSame(['Stage agronome'], $titres(['type' => 'stage']));
        $this->assertSame(['Stage agronome'], $titres(['q' => 'plantations mangues']));
        $this->assertSame(['Développeur web junior'], $titres(['q' => 'laravel']));
        $this->assertSame(['Stage agronome'], $titres(['groupe' => $agro->id]));
        $this->assertSame(['Stage agronome'], $titres(['ville' => 'korhogo']));
        $this->assertSame(['Développeur web junior'], $titres(['teletravail' => 'hybride']));
        $this->assertSame(['Stage agronome', 'Développeur web junior'], $titres(['tri' => 'limite']));
        $this->assertSame(['Abidjan', 'Korhogo'], $this->withToken($t)->getJson('/api/opportunities')->json('villes'));

        $this->withToken($t)->postJson("/api/opportunities/{$dev->id}/favori")->assertJsonPath('favori', true);
        $this->assertSame(['Développeur web junior'], $titres(['favoris' => 1]));
        $this->withToken($t)->postJson("/api/opportunities/{$dev->id}/favori")->assertJsonPath('favori', false);
    }

    public function test_alertes_notifiees_a_la_publication(): void
    {
        $auteur = User::factory()->abonne()->create();
        $chercheur = User::factory()->create();
        $autre = User::factory()->create();
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $t = $this->tokenFor($chercheur);
        $groupe = Group::firstOrCreate(['slug' => 'info'], ['name' => 'Informatique & Technologie']);

        $this->withToken($t)->postJson('/api/job-alerts', ['type' => 'emploi', 'group_id' => $groupe->id, 'q' => 'développeur'])->assertCreated()
            ->assertJsonPath('alerte.libelle', 'Emploi · Informatique & Technologie · « développeur »');
        $this->withToken($t)->postJson('/api/job-alerts', ['type' => 'emploi', 'group_id' => $groupe->id, 'q' => 'développeur'])->assertStatus(422);
        $this->withToken($this->tokenFor($autre))->postJson('/api/job-alerts', ['type' => 'stage'])->assertCreated();

        $o = $this->withToken($this->tokenFor($auteur))->postJson('/api/opportunities', $this->donnees())->json('opportunity');
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$o['id']}/decision", ['decision' => 'publier'])->assertOk()->assertJsonPath('alertes', 1);
        $this->assertTrue(MemberNotification::where('user_id', $chercheur->id)->where('title', 'Nouvelle offre : Développeur web junior')->exists());
        $this->assertFalse(MemberNotification::where('user_id', $autre->id)->where('title', 'like', 'Nouvelle offre%')->exists());

        // Les alertes sont listées et supprimables.
        $alertes = $this->withToken($t)->getJson('/api/opportunities')->json('alertes');
        $this->assertCount(1, $alertes);
        $this->withToken($t)->deleteJson("/api/job-alerts/{$alertes[0]['id']}")->assertOk();
        $this->assertSame([], $this->withToken($t)->getJson('/api/opportunities')->json('alertes'));
    }

    // ── Signalements, export, vitrine ──────────────────────────────────

    public function test_signalement_retrait_et_export(): void
    {
        $auteur = User::factory()->abonne()->create();
        $o = $this->offreEnLigne($auteur);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $t = $this->tokenFor(User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré']));

        $this->withToken($t)->postJson("/api/opportunities/{$o->id}/signaler", ['motif' => 'x'])->assertStatus(422);
        $this->withToken($t)->postJson("/api/opportunities/{$o->id}/signaler", ['motif' => 'On me demande de payer des frais de dossier.'])->assertOk();
        $this->assertTrue($this->withToken($t)->getJson("/api/opportunities/{$o->id}")->json('opportunity.deja_signalee'));

        $res = $this->withToken($admin)->getJson('/api/admin/opportunities?statut=signalee')->assertOk()->json();
        $this->assertSame(1, $res['signalees']);
        $this->assertSame('Awa Traoré', $res['opportunities'][0]['signalements'][0]['par']);

        // Retrait : signalements classés, auteur prévenu.
        $this->withToken($admin)->postJson("/api/admin/opportunities/{$o->id}/decision", ['decision' => 'retirer', 'motif' => 'Frais de dossier demandés aux candidats.'])->assertOk();
        $this->assertSame(0, $this->withToken($admin)->getJson('/api/admin/opportunities')->json('signalees'));

        $export = $this->withToken($admin)->getJson('/api/admin/export/opportunites')->assertOk()->json();
        $this->assertContains('Candidatures', $export['columns']);
        $this->assertSame('Refusée', $export['rows'][0][8]);
    }

    public function test_vitrine_publique_sans_contact(): void
    {
        $auteur = User::factory()->abonne()->create(['email' => 'recruteur@example.com']);
        $o = Opportunity::create($this->donnees(['contact' => 'rh@ivoire-tech.ci']) + ['author_id' => $auteur->id, 'statut' => 'publiee', 'publie_at' => now(), 'expire_le' => today()->addMonth()]);
        Opportunity::create($this->donnees(['title' => 'En attente']) + ['author_id' => $auteur->id, 'statut' => 'en_attente']);

        $liste = $this->getJson('/api/public-opportunities')->assertOk()->json('opportunities');
        $this->assertSame(['Développeur web junior'], array_column($liste, 'title'));
        $json = json_encode($this->getJson("/api/public-opportunities/{$o->id}")->assertOk()->json());
        $this->assertStringNotContainsString('rh@ivoire-tech.ci', $json);
        $this->assertStringNotContainsString('recruteur@example.com', $json);
        $this->assertStringContainsString('Bac+2', $json);

        $o->update(['statut' => 'pourvue']);
        $this->getJson("/api/public-opportunities/{$o->id}")->assertStatus(404);
    }
}
