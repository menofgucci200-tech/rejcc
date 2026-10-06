<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventInscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function evenement(array $attrs = []): Event
    {
        return Event::create($attrs + ['title' => 'Forum Agro', 'slug' => 'forum-'.Str::random(5), 'category' => 'Forum',
            'starts_at' => now()->addDays(10), 'location' => 'Abidjan, Plateau', 'description' => 'Programme complet du forum.']);
    }

    public function test_inscription_confirmee_puis_annulation(): void
    {
        $moi = User::factory()->create();
        $t = $this->tokenFor($moi);
        $e = $this->evenement(['capacity' => 50]);

        $this->withToken($t)->postJson("/api/events/{$e->id}/inscription")->assertOk()->assertJsonPath('registered', true);
        $fiche = $this->withToken($t)->getJson("/api/events/{$e->id}")->assertOk()->json('event');
        $this->assertTrue($fiche['registered']);
        $this->assertSame(49, $fiche['places_restantes']);
        $this->assertSame('Programme complet du forum.', $fiche['description']);
        $notif = MemberNotification::where('user_id', $moi->id)->where('title', 'Inscription confirmée')->first();
        $this->assertStringContainsString('Forum Agro', $notif->body);
        $this->assertSame("/espace-membre/evenements?evenement={$e->id}", $notif->link);

        $this->withToken($t)->deleteJson("/api/events/{$e->id}/inscription")->assertOk()->assertJsonPath('registered', false);
        $this->assertSame(0, EventRegistration::count());
    }

    public function test_refus_complet_passe_ferme_annule_brouillon(): void
    {
        $t = $this->tokenFor(User::factory()->create());
        $complet = $this->evenement(['capacity' => 1]);
        EventRegistration::create(['event_id' => $complet->id, 'user_id' => User::factory()->create()->id]);

        $cas = [
            [$complet, "Toutes les places ont été réservées : l'événement est complet."],
            [$this->evenement(['starts_at' => now()->subDay()]), 'Cet événement a déjà eu lieu.'],
            [$this->evenement(['inscriptions_ouvertes' => false]), 'Les inscriptions sont fermées.'],
            [$this->evenement(['date_limite' => now()->subHour()]), "La date limite d'inscription est dépassée."],
            [$this->evenement(['statut' => 'annule']), 'Cet événement est annulé.'],
        ];
        foreach ($cas as [$e, $message]) {
            $this->withToken($t)->postJson("/api/events/{$e->id}/inscription")->assertStatus(422)->assertJsonPath('message', $message);
        }
        $this->withToken($t)->getJson("/api/events/{$complet->id}")->assertJsonPath('event.complet', true);

        // Brouillon : invisible pour les membres.
        $brouillon = $this->evenement(['statut' => 'brouillon']);
        $this->withToken($t)->getJson("/api/events/{$brouillon->id}")->assertStatus(404);
        $this->assertNotContains($brouillon->id, array_column($this->withToken($t)->getJson('/api/events')->json('events'), 'id'));
    }

    public function test_evenement_reserve_aux_abonnes(): void
    {
        SubscriptionMode::set(true);
        $e = $this->evenement(['reserve_abonnes' => true]);
        $this->withToken($this->tokenFor(User::factory()->create()))->postJson("/api/events/{$e->id}/inscription")
            ->assertStatus(422)->assertJsonPath('message', 'Cet événement est réservé aux membres à jour de leur abonnement annuel.');
        $this->withToken($this->tokenFor(User::factory()->create(['subscription_expires_at' => now()->addYear()])))
            ->postJson("/api/events/{$e->id}/inscription")->assertOk();
        // Ouvert à tous par défaut.
        $ouvert = $this->evenement();
        $this->withToken($this->tokenFor(User::factory()->create()))->postJson("/api/events/{$ouvert->id}/inscription")->assertOk();
    }
}
