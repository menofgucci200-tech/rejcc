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

    // ── Rubrique « contenu » sans être administrateur général ───────────

    /** Comptes ayant la rubrique « contenu » sans être administrateur général. */
    private function comptesContenu(): array
    {
        return [
            'communication' => User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => ['actualites', 'evenements', 'contenu', 'salons']]),
            'restreint' => User::factory()->create(['role' => 'admin', 'permissions' => ['contenu']]),
            'testeur' => User::factory()->create(['role' => 'admin', 'departement' => 'Communication', 'permissions' => ['contenu']]),
        ];
    }

    public function test_un_admin_restreint_contenu_ne_voit_pas_les_identifiants_de_paiement(): void
    {
        SiteSetting::create(['key' => 'identity.slogan', 'value' => 'Ensemble']);
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'sk_super_secret']);
        SiteSetting::create(['key' => 'payment.cinetpay_site_id', 'value' => '123456']);

        foreach ($this->comptesContenu() as $nom => $compte) {
            $settings = $this->withToken($this->tokenFor($compte))
                ->getJson('/api/admin/site-settings')
                ->assertOk()
                ->json('settings');

            $this->assertSame('Ensemble', $settings['identity.slogan'], $nom);
            $this->assertArrayNotHasKey('payment.cinetpay_api_key', $settings, $nom);
            $this->assertArrayNotHasKey('payment.cinetpay_site_id', $settings, $nom);
            $this->assertStringNotContainsString('sk_super_secret', json_encode($settings), $nom);
        }
    }

    public function test_un_admin_restreint_contenu_ne_peut_pas_modifier_les_identifiants_de_paiement(): void
    {
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'sk_origine']);

        foreach ($this->comptesContenu() as $nom => $compte) {
            $this->withToken($this->tokenFor($compte))->putJson('/api/admin/site-settings', [
                'settings' => [
                    'identity.slogan' => 'Glissé avec la clé',
                    'payment.cinetpay_api_key' => 'sk_pirate',
                    'payment.cinetpay_site_id' => '000',
                ],
            ])->assertStatus(403);
        }

        // Rien n'est enregistré, pas même le réglage autorisé envoyé avec.
        $this->assertSame('sk_origine', SiteSetting::where('key', 'payment.cinetpay_api_key')->value('value'));
        $this->assertDatabaseMissing('site_settings', ['key' => 'payment.cinetpay_site_id']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'identity.slogan']);
    }

    public function test_les_autres_reglages_restent_modifiables_par_la_rubrique_contenu(): void
    {
        foreach ($this->comptesContenu() as $nom => $compte) {
            $this->withToken($this->tokenFor($compte))->putJson('/api/admin/site-settings', [
                'settings' => [
                    'identity.slogan' => "Slogan {$nom}",
                    'social.facebook' => 'https://facebook.com/rejcc',
                    'banner.enabled' => true,
                    'seo.accueil.title' => 'REJCC',
                ],
            ])->assertOk();

            $this->assertSame("Slogan {$nom}", SiteSetting::where('key', 'identity.slogan')->value('value'));
        }
    }

    public function test_l_admin_general_lit_et_modifie_les_identifiants_de_paiement(): void
    {
        SiteSetting::create(['key' => 'payment.cinetpay_site_id', 'value' => '123456']);
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));

        $this->withToken($token)->getJson('/api/admin/site-settings')
            ->assertOk()->assertJsonPath('settings', fn ($s) => ($s['payment.cinetpay_site_id'] ?? null) === '123456');

        $this->withToken($token)->putJson('/api/admin/site-settings', [
            'settings' => ['payment.cinetpay_api_key' => 'sk_general', 'identity.slogan' => 'Ensemble'],
        ])->assertOk();

        $this->assertSame('sk_general', SiteSetting::where('key', 'payment.cinetpay_api_key')->value('value'));
    }

    public function test_un_compte_de_departement_sans_restriction_n_est_pas_administrateur_general(): void
    {
        SiteSetting::create(['key' => 'payment.cinetpay_api_key', 'value' => 'sk_super_secret']);
        // Compte de département dont les permissions seraient restées vides (null).
        $compte = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => null]);
        $token = $this->tokenFor($compte);

        $settings = $this->withToken($token)->getJson('/api/admin/site-settings')->assertOk()->json('settings');
        $this->assertArrayNotHasKey('payment.cinetpay_api_key', $settings);
        $this->withToken($token)->putJson('/api/admin/site-settings', ['settings' => ['payment.cinetpay_api_key' => 'x']])
            ->assertStatus(403);
    }

    public function test_la_route_publique_ne_publie_que_les_reglages_de_la_vitrine(): void
    {
        SiteSetting::create(['key' => 'identity.slogan', 'value' => 'Ensemble']);
        SiteSetting::create(['key' => 'abonnement.montant', 'value' => 10000]);
        SiteSetting::create(['key' => 'spirit.paroles', 'value' => [['verset' => 'V', 'reference' => 'R']]]);
        SiteSetting::create(['key' => 'google_meet', 'value' => ['email' => 'contact@exemple.ci', 'refresh_token' => 'jeton-chiffre']]);
        SiteSetting::create(['key' => 'communaute.mots_interdits', 'value' => 'mot1, mot2']);
        SiteSetting::create(['key' => 'certificats.reglages', 'value' => ['lieu' => 'Abidjan']]);
        SiteSetting::create(['key' => 'subscription.enforced', 'value' => true]);

        $settings = $this->getJson('/api/site-settings')->assertOk()->json('settings');

        $this->assertSame('Ensemble', $settings['identity.slogan']);
        $this->assertSame(10000, $settings['abonnement.montant']);
        $this->assertArrayHasKey('spirit.paroles', $settings);
        foreach (['google_meet', 'communaute.mots_interdits', 'certificats.reglages', 'subscription.enforced'] as $cle) {
            $this->assertArrayNotHasKey($cle, $settings, $cle);
        }
    }

    public function test_le_jeton_google_meet_n_est_jamais_renvoye_par_la_route_admin(): void
    {
        SiteSetting::create(['key' => 'google_meet', 'value' => ['email' => 'contact@exemple.ci', 'refresh_token' => 'jeton-chiffre']]);
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $settings = $this->withToken($token)->getJson('/api/admin/site-settings')->assertOk()->json('settings');

        $this->assertArrayNotHasKey('google_meet', $settings);
    }
}
