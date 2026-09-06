<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_un_membre_sans_abonnement_peut_consulter_la_marketplace(): void
    {
        $vendeur = User::factory()->abonne()->create();
        MarketplaceListing::create([
            'user_id' => $vendeur->id, 'type' => 'service', 'title' => 'Dépannage plomberie',
            'category' => 'BTP', 'description' => 'Intervention rapide à domicile.', 'statut' => 'approuve',
        ]);

        $acheteur = User::factory()->create(); // pas d'abonnement

        $listings = $this->withToken($this->tokenFor($acheteur))->getJson('/api/marketplace')
            ->assertOk()->json('listings');

        $this->assertCount(1, $listings);
        $this->assertSame('Dépannage plomberie', $listings[0]['title']);
    }

    public function test_un_membre_sans_abonnement_ne_peut_pas_publier_ni_gerer_ses_annonces(): void
    {
        $membre = User::factory()->create(); // pas d'abonnement
        $token = $this->tokenFor($membre);

        $this->withToken($token)->postJson('/api/marketplace', [
            'type' => 'service', 'title' => 'Mes services', 'category' => 'BTP',
            'description' => 'Une description suffisamment longue pour passer la validation.',
        ])->assertStatus(402);

        $this->withToken($token)->getJson('/api/marketplace/mine')->assertStatus(402);
    }

    public function test_un_abonne_peut_publier_une_annonce(): void
    {
        $membre = User::factory()->abonne()->create();

        $this->withToken($this->tokenFor($membre))->postJson('/api/marketplace', [
            'type' => 'service', 'title' => 'Dépannage plomberie', 'category' => 'BTP',
            'description' => 'Une description suffisamment longue pour passer la validation.',
        ])->assertOk()->assertJsonPath('ok', true);
    }
}
