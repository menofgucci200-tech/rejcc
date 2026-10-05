<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MentoratTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function abonne(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['subscription_expires_at' => now()->addYear()]);
    }

    public function test_un_mentor_est_dispense_d_abonnement(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->assertTrue($mentor->hasPaidSubscription());
        $me = $this->withToken($this->tokenFor($mentor))->getJson('/api/auth/me')->json('user');
        $this->assertTrue($me['subscription_exempt']);
        $this->assertSame(3, $me['mentor']['capacite']);

        // L'annuaire (réservé aux abonnés) lui est ouvert.
        $this->withToken($this->tokenFor($mentor))->getJson('/api/members')->assertOk();
    }

    public function test_le_mentor_renseigne_son_profil_de_mentor(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $token = $this->tokenFor($mentor);

        $this->withToken($token)->putJson('/api/mentorat/profil', [
            'expertises' => ['Finance', ' Marketing ', 'Finance'],
            'bio' => '20 ans dans la banque.',
            'disponibilites' => 'Mardi et jeudi soir',
            'format' => 'visio',
            'capacite' => 4,
            'accepte' => true,
        ])->assertOk()->assertJsonPath('mentor.expertises', ['Finance', 'Marketing'])
            ->assertJsonPath('mentor.format_label', 'En visio');

        // Un membre ne peut pas se créer un profil de mentor.
        $this->withToken($this->tokenFor($this->abonne()))->putJson('/api/mentorat/profil', ['capacite' => 2])->assertStatus(403);
    }

    public function test_les_mentors_apparaissent_dans_l_annuaire_et_leur_fiche_s_ouvre(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor', 'prenom' => 'Paul', 'mentor_expertises' => ['Agro-business']]);
        User::factory()->create(['role' => 'member', 'prenom' => 'Awa']);
        $token = $this->tokenFor($this->abonne());

        $membres = $this->withToken($token)->getJson('/api/members')->json('members');
        $this->assertContains('mentor', array_column($membres, 'role'));

        $seulsMentors = $this->withToken($token)->getJson('/api/members?mentors=1')->json('members');
        $this->assertSame(['Paul'], array_column($seulsMentors, 'prenom'));

        $this->withToken($token)->getJson("/api/members/{$mentor->id}")->assertOk()
            ->assertJsonPath('member.mentor.expertises', ['Agro-business']);
    }
}
