<?php

namespace Tests\Feature\Api;

use App\Mail\ReinitialisationMotDePasse;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_envoie_un_email_avec_le_lien(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'membre@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'membre@example.com'])
            ->assertOk()->assertJsonPath('ok', true);

        Mail::assertSent(ReinitialisationMotDePasse::class, fn ($mail) => $mail->user->is($user));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'membre@example.com']);
    }

    public function test_forgot_password_repond_ok_meme_pour_un_email_inconnu(): void
    {
        Mail::fake();

        // Anti-énumération : même réponse, mais ni e-mail ni token.
        $this->postJson('/api/auth/forgot-password', ['email' => 'inconnu@example.com'])
            ->assertOk()->assertJsonPath('ok', true);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'inconnu@example.com']);
    }

    public function test_reset_password_avec_un_token_valide(): void
    {
        $user = User::factory()->create(['email' => 'membre@example.com']);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', 'ancien'), 'name' => 'web']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'membre@example.com',
            'token' => Hash::make('token-secret'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'membre@example.com',
            'token' => 'token-secret',
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertOk()->assertJsonPath('ok', true);

        // Le nouveau mot de passe fonctionne, le token de reset est consommé
        // et les sessions ouvertes avec l'ancien mot de passe sont révoquées.
        $this->assertTrue(Hash::check('nouveau-mdp-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'membre@example.com']);
        $this->assertSame(0, ApiToken::where('user_id', $user->id)->count());
    }

    public function test_reset_password_refuse_un_token_invalide_ou_expire(): void
    {
        User::factory()->create(['email' => 'membre@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'membre@example.com',
            'token' => Hash::make('token-secret'),
            'created_at' => now()->subHours(2),
        ]);

        // Token expiré (créé il y a 2 h)
        $this->postJson('/api/auth/reset-password', [
            'email' => 'membre@example.com',
            'token' => 'token-secret',
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertStatus(422)->assertJsonPath('ok', false);

        // Mauvais token
        DB::table('password_reset_tokens')->where('email', 'membre@example.com')
            ->update(['created_at' => now()]);
        $this->postJson('/api/auth/reset-password', [
            'email' => 'membre@example.com',
            'token' => 'mauvais-token',
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertStatus(422)->assertJsonPath('ok', false);
    }
}
