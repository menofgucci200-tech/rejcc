<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    public function test_le_fil_agrege_formations_evenements_et_annonces(): void
    {
        $user = User::factory()->create();

        $formation = Formation::create(['title' => 'Prise de parole', 'category' => 'Communication', 'modules_count' => 4]);
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id, 'progress' => 50]);

        $event = Event::create(['title' => 'Café des entrepreneurs', 'category' => 'Networking', 'starts_at' => now()->addDays(5)]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id]);

        Opportunity::create(['title' => 'Recherche associé', 'description' => 'Description de test suffisamment longue.', 'type' => 'annonce', 'author_id' => $user->id]);

        $activity = $this->withToken($this->tokenFor($user))
            ->getJson('/api/my-activity')
            ->assertOk()
            ->json('activity');

        $texts = implode(' | ', array_column($activity, 'text'));
        $this->assertStringContainsString('Inscription à la formation « Prise de parole »', $texts);
        $this->assertStringContainsString('Progression 50 %', $texts);
        $this->assertStringContainsString('Café des entrepreneurs', $texts);
        $this->assertStringContainsString('Recherche associé', $texts);
    }

    public function test_le_fil_ne_montre_que_l_activite_du_membre_courant(): void
    {
        $autre = User::factory()->create();
        Opportunity::create(['title' => 'Annonce d\'un autre membre', 'description' => 'Description de test suffisamment longue.', 'type' => 'annonce', 'author_id' => $autre->id]);

        $activity = $this->withToken($this->tokenFor(User::factory()->create()))
            ->getJson('/api/my-activity')
            ->assertOk()
            ->json('activity');

        $this->assertSame([], $activity);
    }
}
