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
}
