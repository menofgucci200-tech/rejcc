<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DirectoryTest extends TestCase
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

    public function test_la_carte_annuaire_donne_titre_competences_et_nouveaute(): void
    {
        $this->abonne(['prenom' => 'Ancien', 'created_at' => now()->subMonths(3), 'titre' => 'Comptable', 'competences' => ['Excel', 'Fiscalité', 'Audit', 'Paie']]);
        $this->abonne(['prenom' => 'Nouveau', 'created_at' => now()->subDays(3)]);
        $token = $this->tokenFor($this->abonne());

        $membres = collect($this->withToken($token)->getJson('/api/members')->assertOk()->json('members'))->keyBy('prenom');
        $this->assertSame('Comptable', $membres['Ancien']['titre']);
        $this->assertSame(['Excel', 'Fiscalité', 'Audit'], $membres['Ancien']['competences']);
        $this->assertFalse($membres['Ancien']['nouveau']);
        $this->assertTrue($membres['Nouveau']['nouveau']);
    }
}
