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

    public function test_ils_participent_selon_l_annuaire_et_l_abonnement(): void
    {
        SubscriptionMode::set(true);
        $e = $this->evenement();
        $visible = User::factory()->create(['prenom' => 'Awa', 'subscription_expires_at' => now()->addYear()]);
        $masque = User::factory()->create(['prenom' => 'Masque', 'preferences' => ['apparaitre_annuaire' => false]]);
        $suspendu = User::factory()->create(['prenom' => 'Suspendu', 'is_active' => false]);
        foreach ([$visible, $masque, $suspendu] as $u) {
            EventRegistration::create(['event_id' => $e->id, 'user_id' => $u->id]);
        }

        $abonne = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $p = $this->withToken($this->tokenFor($abonne))->getJson("/api/events/{$e->id}")->assertOk()->json('event.participants');
        $this->assertSame(1, $p['total']);
        $this->assertSame(['Awa'], array_column($p['membres'], 'prenom'));

        $p = $this->withToken($this->tokenFor(User::factory()->create()))->getJson("/api/events/{$e->id}")->json('event.participants');
        $this->assertSame(1, $p['total']);
        $this->assertFalse($p['visible']);
        $this->assertSame([], $p['membres']);

        // On ne se voit pas soi-même dans la liste.
        $p = $this->withToken($this->tokenFor($visible))->getJson("/api/events/{$e->id}")->json('event.participants');
        $this->assertSame(0, $p['total']);
    }

    public function test_billet_visio_rappel_et_pointage(): void
    {
        $moi = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $t = $this->tokenFor($moi);
        $e = $this->evenement(['en_ligne' => true, 'lien_visio' => 'https://meet.exemple.ci/forum', 'starts_at' => now()->addHours(20)]);

        $this->withToken($this->tokenFor(User::factory()->create()))->getJson("/api/events/{$e->id}")->assertJsonPath('event.lien_visio', null);
        $this->withToken($t)->postJson("/api/events/{$e->id}/inscription")->assertOk();
        $fiche = $this->withToken($t)->getJson("/api/events/{$e->id}")->json('event');
        $this->assertMatchesRegularExpression('/^B-[A-Z0-9]{8}$/', $fiche['billet']);
        $this->assertSame('https://meet.exemple.ci/forum', $fiche['lien_visio']);

        // Rappel la veille, une seule fois.
        $this->artisan('evenements:rappels');
        $this->artisan('evenements:rappels');
        $this->assertSame(1, MemberNotification::where('user_id', $moi->id)->where('title', 'like', 'Rappel : Forum Agro%')->count());

        // Pointage par billet, puis doublon signalé.
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($admin)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => $fiche['billet']])
            ->assertOk()->assertJsonPath('deja', false)->assertJsonPath('membre.nom', 'Awa Traoré')->assertJsonPath('presents', 1);
        $this->withToken($admin)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => strtolower($fiche['billet'])])
            ->assertOk()->assertJsonPath('deja', true);
        $this->assertTrue($this->withToken($t)->getJson("/api/events/{$e->id}")->json('event.present'));

        // Carte membre d'un non-inscrit : refus, puis inscription sur place.
        $paul = User::factory()->create(['prenom' => 'Paul', 'nom' => 'Ahoua']);
        $url = 'http://rejcc.test/carte/'.str_pad((string) $paul->id, 4, '0', STR_PAD_LEFT);
        $this->withToken($admin)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => $url])
            ->assertStatus(404)->assertJsonPath('code', 'non_inscrit');
        $this->withToken($admin)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => $url, 'sur_place' => true])
            ->assertOk()->assertJsonPath('presents', 2)->assertJsonPath('inscrits', 2);

        $this->withToken($admin)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => 'B-INCONNU1'])->assertStatus(404);
        $liste = $this->withToken($admin)->getJson("/api/admin/events/{$e->id}/inscrits")->assertOk()->json();
        $this->assertSame(2, $liste['presents']);
    }
}
