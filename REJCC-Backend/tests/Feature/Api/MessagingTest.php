<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    protected function abonne(array $attrs = []): User
    {
        return User::factory()->create(['role' => 'member', 'subscription_expires_at' => now()->addYear()] + $attrs);
    }

    public function test_destinataires_invalides_et_messages_vides_sont_refuses_avec_un_message_clair(): void
    {
        $moi = $this->abonne();
        $token = $this->tokenFor($moi);
        $suspendu = $this->abonne(['is_active' => false]);

        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $moi->id, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce destinataire est introuvable.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => 99999, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce destinataire est introuvable.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $suspendu->id, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce compte est suspendu : il ne peut pas recevoir de messages.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $suspendu->id, 'body' => '   '])
            ->assertStatus(422)->assertJsonPath('message', "Écrivez votre message avant de l'envoyer.");
        $this->withToken($token)->getJson('/api/messages/99999')->assertStatus(404);
        $this->assertSame(0, Message::count());
    }

    public function test_fil_incremental_et_lecture(): void
    {
        $awa = $this->abonne(['prenom' => 'Awa']);
        $esther = $this->abonne(['prenom' => 'Esther']);
        $tA = $this->tokenFor($awa);
        $tE = $this->tokenFor($esther);

        $premier = $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => "Bonjour Esther,\nêtes-vous disponible ?"])
            ->assertOk()->json('message.id');
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => 'Samedi ?'])->assertOk();

        // Seuls les messages après « premier » sont renvoyés ; rien n'est encore lu.
        $fil = $this->withToken($tA)->getJson("/api/messages/{$esther->id}?after={$premier}")->assertOk()->json();
        $this->assertCount(1, $fil['messages']);
        $this->assertSame('Samedi ?', $fil['messages'][0]['body']);
        $this->assertSame(0, $fil['vu_jusqua']);
        $this->assertSame('Esther', $fil['partner']['prenom']);

        // Esther ouvre le fil : les deux messages sont lus, Awa voit « Vu ».
        $this->withToken($tE)->getJson("/api/messages/{$awa->id}")->assertOk()->assertJsonCount(2, 'messages');
        $fil = $this->withToken($tA)->getJson("/api/messages/{$esther->id}")->json();
        $this->assertSame($fil['messages'][1]['id'], $fil['vu_jusqua']);
        $this->assertSame("Bonjour Esther,\nêtes-vous disponible ?", $fil['messages'][0]['body']);

        $conv = $this->withToken($tA)->getJson('/api/messages')->json('conversations.0');
        $this->assertTrue($conv['last_moi']);
        $this->assertTrue($conv['last_vu']);
        $this->assertSame(0, $conv['unread']);
    }
}
