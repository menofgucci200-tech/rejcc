<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_repond(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJsonPath('ok', true);
    }

    public function test_le_contenu_vitrine_est_public(): void
    {
        foreach (['/api/sectors', '/api/testimonials', '/api/partners', '/api/news', '/api/public-events'] as $path) {
            $this->getJson($path)->assertOk()->assertJsonPath('ok', true);
        }
    }

    public function test_contact_enregistre_un_message_valide(): void
    {
        $this->postJson('/api/contact', [
            'nom' => 'Jean Kouassi',
            'email' => 'jean@example.com',
            'sujet' => 'Question',
            'message' => 'Bonjour, je souhaite en savoir plus sur le réseau.',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('contacts', ['email' => 'jean@example.com']);
    }

    public function test_contact_refuse_un_message_incomplet(): void
    {
        $this->postJson('/api/contact', ['email' => 'jean@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_newsletter_accepte_les_doublons_sans_erreur(): void
    {
        $this->postJson('/api/newsletter', ['email' => 'abonne@example.com'])->assertOk();
        $this->postJson('/api/newsletter', ['email' => 'abonne@example.com'])->assertOk();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }
}
