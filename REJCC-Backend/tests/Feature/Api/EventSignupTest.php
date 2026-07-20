<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\EventParticipant;
use App\Models\RegistrationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventSignupTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'prenom' => 'Marie',
            'nom' => 'Aka',
            'telephone' => '0102030405',
        ];
    }

    // ── Inscription publique ─────────────────────────────────────────────

    public function test_une_personne_s_inscrit_a_un_evenement(): void
    {
        $event = RegistrationEvent::create(['title' => 'Lancement REJCC', 'slug' => 'lancement-rejcc']);

        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload())
            ->assertOk()->assertJsonPath('ok', true)
            ->assertJsonPath('event.count', 1);

        $this->assertDatabaseHas('event_participants', [
            'registration_event_id' => $event->id,
            'telephone' => '0102030405',
        ]);
    }

    public function test_le_meme_numero_ne_s_inscrit_pas_deux_fois(): void
    {
        RegistrationEvent::create(['title' => 'Lancement', 'slug' => 'lancement']);

        $this->postJson('/api/event-signup/lancement', $this->payload())->assertOk();
        $this->postJson('/api/event-signup/lancement', $this->payload(['prenom' => 'Autre']))
            ->assertStatus(422);

        $this->assertSame(1, EventParticipant::count());
    }

    public function test_inscription_refusee_si_evenement_complet(): void
    {
        $event = RegistrationEvent::create(['title' => 'Atelier', 'slug' => 'atelier', 'capacity' => 1]);
        EventParticipant::create(['registration_event_id' => $event->id, 'prenom' => 'A', 'nom' => 'B', 'telephone' => '0700000000']);

        $this->postJson('/api/event-signup/atelier', $this->payload())
            ->assertStatus(422)->assertJsonPath('ok', false);

        $this->assertSame(1, EventParticipant::count());
    }

    public function test_inscription_refusee_si_fermee(): void
    {
        RegistrationEvent::create(['title' => 'Gala', 'slug' => 'gala', 'is_open' => false]);

        $this->postJson('/api/event-signup/gala', $this->payload())
            ->assertStatus(422)->assertJsonPath('ok', false);

        $this->assertSame(0, EventParticipant::count());
    }

    public function test_page_publique_renvoie_l_etat_des_inscriptions(): void
    {
        RegistrationEvent::create(['title' => 'Forum', 'slug' => 'forum', 'capacity' => 100]);

        $this->getJson('/api/event-signup/forum')->assertOk()
            ->assertJsonPath('event.title', 'Forum')
            ->assertJsonPath('event.remaining', 100)
            ->assertJsonPath('event.accepts', true);

        $this->getJson('/api/event-signup/inconnu')->assertStatus(404);
    }

    // ── Administration ───────────────────────────────────────────────────

    public function test_l_admin_cree_gere_et_ferme_un_evenement(): void
    {
        $token = $this->adminToken();

        $event = $this->withToken($token)->postJson('/api/admin/registration-events', [
            'title' => 'Lancement officiel',
            'capacity' => 500,
        ])->assertCreated()->json('event');

        $this->assertSame('lancement-officiel', $event['slug']);
        $this->assertTrue($event['is_open']);

        // Fermeture des inscriptions.
        $this->withToken($token)->postJson("/api/admin/registration-events/{$event['id']}/toggle")
            ->assertOk()->assertJsonPath('is_open', false);

        // La liste expose le compteur d'inscrits.
        $this->withToken($token)->getJson('/api/admin/registration-events')
            ->assertOk()->assertJsonPath('events.0.count', 0);
    }

    public function test_les_participants_sont_listes_et_exportables(): void
    {
        $token = $this->adminToken();
        $event = RegistrationEvent::create(['title' => 'Lancement', 'slug' => 'lancement']);
        EventParticipant::create(['registration_event_id' => $event->id, 'prenom' => 'Jean', 'nom' => 'Kouassi', 'telephone' => '0102030405', 'is_member' => true]);

        $this->withToken($token)->getJson("/api/admin/registration-events/{$event->id}/participants")
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('participants.0.nom', 'Kouassi');

        $export = $this->withToken($token)->getJson('/api/admin/export/participants?event='.$event->id)
            ->assertOk()->json();
        $this->assertSame(['Événement', 'Prénom', 'Nom', 'Téléphone', 'Email', 'Membre', 'Inscrit le'], $export['columns']);
        $this->assertCount(1, $export['rows']);
    }

    public function test_un_membre_ne_peut_pas_gerer_les_evenements(): void
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        $this->withToken($plain)->getJson('/api/admin/registration-events')->assertStatus(403);
    }
}
