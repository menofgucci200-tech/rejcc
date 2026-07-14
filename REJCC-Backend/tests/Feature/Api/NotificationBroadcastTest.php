<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationBroadcastTest extends TestCase
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

    public function test_la_diffusion_atteint_tous_les_membres(): void
    {
        $token = $this->adminToken();
        User::factory()->count(3)->create(['role' => 'member']);

        $this->withToken($token)->postJson('/api/admin/notifications/broadcast', [
            'title' => 'Nouvel atelier disponible',
            'type' => 'info',
        ])->assertOk()->assertJsonPath('sent_to', 3);

        $this->assertSame(3, MemberNotification::where('title', 'Nouvel atelier disponible')->count());
    }

    public function test_la_notification_peut_cibler_un_seul_membre(): void
    {
        $token = $this->adminToken();
        $cible = User::factory()->create(['role' => 'member']);
        $autre = User::factory()->create(['role' => 'member']);

        $this->withToken($token)->postJson('/api/admin/notifications/broadcast', [
            'title' => 'Votre dossier est validé',
            'type' => 'message',
            'user_id' => $cible->id,
        ])->assertOk()->assertJsonPath('sent_to', 1);

        $this->assertSame(1, MemberNotification::where('user_id', $cible->id)->where('title', 'Votre dossier est validé')->count());
        $this->assertSame(0, MemberNotification::where('user_id', $autre->id)->count());

        // Destinataire inexistant refusé.
        $this->withToken($token)->postJson('/api/admin/notifications/broadcast', [
            'title' => 'Test',
            'user_id' => 9999,
        ])->assertStatus(422);
    }
}
