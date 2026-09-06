<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteSettingsPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_les_identifiants_de_paiement_ne_sont_pas_exposes_par_la_route_publique(): void
    {
        SiteSetting::create(['key' => 'identity.slogan', 'value' => 'Ensemble pour l\'excellence']);
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'sk_super_secret']);
        SiteSetting::create(['key' => 'payment.cinetpay_site_id', 'value' => '123456']);

        $settings = $this->getJson('/api/site-settings')->assertOk()->json('settings');

        $this->assertSame("Ensemble pour l'excellence", $settings['identity.slogan']);
        $this->assertArrayNotHasKey('payment.cinetpay_api_key', $settings);
        $this->assertArrayNotHasKey('payment.cinetpay_site_id', $settings);
    }

    public function test_l_admin_peut_lire_les_identifiants_de_paiement_via_la_route_admin(): void
    {
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'sk_super_secret']);
        $admin = User::factory()->create(['role' => 'admin']);

        $settings = $this->withToken($this->tokenFor($admin))
            ->getJson('/api/admin/site-settings')
            ->assertOk()
            ->json('settings');

        $this->assertSame('sk_super_secret', $settings['payment.cinetpay_api_key']);
    }

    public function test_un_non_admin_ne_peut_pas_lire_les_reglages_admin(): void
    {
        $membre = User::factory()->create();

        $this->withToken($this->tokenFor($membre))
            ->getJson('/api/admin/site-settings')
            ->assertStatus(403);
    }

    public function test_l_admin_peut_enregistrer_les_identifiants_cinetpay(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withToken($this->tokenFor($admin))->putJson('/api/admin/site-settings', [
            'settings' => [
                'payment.cinetpay_api_key' => 'sk_nouvelle_cle',
                'payment.cinetpay_site_id' => '987654',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('site_settings', ['key' => 'payment.cinetpay_api_key']);
        $this->assertSame('sk_nouvelle_cle', SiteSetting::where('key', 'payment.cinetpay_api_key')->first()->value);
    }
}
