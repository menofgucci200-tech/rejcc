<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Interrupteur général des abonnements (tableau de bord admin). */
class SubscriptionModeTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_sans_abonnement_obligatoire_tout_le_monde_accede_a_tout(): void
    {
        SubscriptionMode::set(false);
        $membre = User::factory()->create(['role' => 'member', 'subscription_expires_at' => null]);
        $token = $this->tokenFor($membre);

        $this->withToken($token)->getJson('/api/members')->assertOk();
        $this->withToken($token)->getJson('/api/messages')->assertOk();
        $this->withToken($token)->getJson('/api/projects')->assertOk();

        $me = $this->withToken($token)->getJson('/api/auth/me')->json('user');
        $this->assertTrue($me['subscription_active']);
        $this->assertFalse($me['subscription_paid']);
        $this->assertFalse($me['subscriptions_enforced']);

        // La carte publique n'est pas verrouillée.
        $this->assertArrayNotHasKey('locked', $this->getJson('/api/member-card/'.$membre->id)->json('card'));

        // Pas de paiement possible tant que les abonnements ne sont pas ouverts.
        $this->withToken($token)->postJson('/api/subscription/pay')->assertStatus(422);
    }

    public function test_avec_abonnement_obligatoire_les_restrictions_s_appliquent(): void
    {
        SubscriptionMode::set(true);
        $membre = User::factory()->create(['role' => 'member', 'subscription_expires_at' => null]);
        $abonne = User::factory()->abonne()->create(['role' => 'member']);

        $this->withToken($this->tokenFor($membre))->getJson('/api/members')->assertStatus(402);
        $this->withToken($this->tokenFor($abonne))->getJson('/api/members')->assertOk();
        $this->assertTrue($this->getJson('/api/member-card/'.$membre->id)->json('card.locked'));
    }

    public function test_l_admin_active_et_desactive_les_abonnements(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'permissions' => null]);
        $membre = User::factory()->create(['role' => 'member', 'subscription_expires_at' => null]);
        $tokenAdmin = $this->tokenFor($admin);
        $tokenMembre = $this->tokenFor($membre);

        $this->withToken($tokenAdmin)->putJson('/api/admin/subscription-mode', ['enforced' => false])
            ->assertOk()->assertJson(['enforced' => false, 'membres' => 1, 'abonnes' => 0]);
        $this->withToken($tokenMembre)->getJson('/api/members')->assertOk();

        $this->withToken($tokenAdmin)->putJson('/api/admin/subscription-mode', ['enforced' => true])
            ->assertOk()->assertJson(['enforced' => true]);
        $this->withToken($tokenMembre)->getJson('/api/members')->assertStatus(402);

        // Un membre ne peut pas toucher à l'interrupteur.
        $this->withToken($tokenMembre)->putJson('/api/admin/subscription-mode', ['enforced' => false])->assertStatus(403);
    }
}
