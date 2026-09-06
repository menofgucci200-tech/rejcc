<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_le_statut_est_inactif_par_defaut(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))->getJson('/api/subscription/status')
            ->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('amount', 10000)
            ->assertJsonPath('currency', 'XOF');
    }

    public function test_un_admin_est_toujours_considere_a_jour(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withToken($this->tokenFor($admin))->getJson('/api/subscription/status')
            ->assertOk()
            ->assertJsonPath('active', true);
    }

    public function test_initier_le_paiement_cree_une_transaction_pending_et_renvoie_l_url_cinetpay(): void
    {
        Http::fake([
            '*/v2/payment' => Http::response([
                'code' => '201',
                'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/abc123'],
            ]),
        ]);

        $user = User::factory()->create();

        $res = $this->withToken($this->tokenFor($user))->postJson('/api/subscription/pay')
            ->assertOk()
            ->json();

        $this->assertTrue($res['ok']);
        $this->assertSame('https://checkout.cinetpay.com/payment/abc123', $res['payment_url']);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'type' => 'abonnement',
            'status' => 'pending',
            'provider' => 'cinetpay',
            'amount' => 10000,
        ]);
    }

    public function test_les_identifiants_saisis_dans_les_reglages_admin_priment_sur_le_env(): void
    {
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'cle-depuis-admin']);
        SiteSetting::create(['key' => 'payment.cinetpay_site_id', 'value' => 'site-depuis-admin']);

        Http::fake([
            '*/v2/payment' => Http::response([
                'code' => '201',
                'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/xyz'],
            ]),
        ]);

        $user = User::factory()->create();
        $this->withToken($this->tokenFor($user))->postJson('/api/subscription/pay')->assertOk();

        Http::assertSent(function ($request) {
            return $request['apikey'] === 'cle-depuis-admin' && $request['site_id'] === 'site-depuis-admin';
        });
    }

    public function test_on_ne_peut_pas_payer_un_abonnement_deja_actif(): void
    {
        $user = User::factory()->abonne()->create();

        $this->withToken($this->tokenFor($user))->postJson('/api/subscription/pay')
            ->assertStatus(422);
    }

    public function test_le_webhook_cinetpay_active_l_abonnement_quand_le_paiement_est_accepte(): void
    {
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'abonnement',
            'reference' => 'ABO-TEST-123',
            'provider' => 'cinetpay',
            'amount' => 10000,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        Http::fake([
            '*/v2/payment/check' => Http::response([
                'data' => ['status' => 'ACCEPTED', 'operator_id' => 'OP123'],
            ]),
        ]);

        $this->postJson('/api/subscription/notify', ['cpm_trans_id' => $payment->reference])
            ->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame('success', $payment->status);
        $this->assertNotNull($user->subscription_expires_at);
        $this->assertTrue($user->subscription_expires_at->isFuture());
        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_le_webhook_cinetpay_marque_le_paiement_echoue_si_refuse(): void
    {
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'abonnement',
            'reference' => 'ABO-TEST-456',
            'provider' => 'cinetpay',
            'amount' => 10000,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        Http::fake([
            '*/v2/payment/check' => Http::response([
                'data' => ['status' => 'REFUSED'],
            ]),
        ]);

        $this->postJson('/api/subscription/notify', ['cpm_trans_id' => $payment->reference])
            ->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_renouveler_avant_expiration_prolonge_a_partir_de_la_date_d_expiration_actuelle(): void
    {
        $expiration = now()->addDays(10);
        $user = User::factory()->create(['subscription_expires_at' => $expiration]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'abonnement',
            'reference' => 'ABO-RENEW-1',
            'provider' => 'cinetpay',
            'amount' => 10000,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        Http::fake([
            '*/v2/payment/check' => Http::response(['data' => ['status' => 'ACCEPTED']]),
        ]);

        $this->postJson('/api/subscription/notify', ['cpm_trans_id' => $payment->reference])->assertOk();

        $user->refresh();
        $this->assertEqualsWithDelta($expiration->copy()->addYear()->timestamp, $user->subscription_expires_at->timestamp, 5);
    }
}
