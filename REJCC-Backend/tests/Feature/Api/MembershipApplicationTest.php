<?php

namespace Tests\Feature\Api;

use App\Mail\CandidatureAcceptee;
use App\Mail\CandidatureRecue;
use App\Mail\CandidatureRefusee;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
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

    private function soumettre(array $overrides = [])
    {
        return $this->postJson('/api/membership-applications', $overrides + [
            'prenom' => 'Marie',
            'nom' => 'Aka',
            'sexe' => 'Femme',
            'tranche_age' => '18-25 ans',
            'whatsapp' => '0102030405',
            'email' => 'marie@example.com',
            'diocese' => 'Abidjan',
            'ville' => 'Abidjan',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'paroisse' => 'Sainte-Anne',
            'statut_actuel' => ['Étudiant'],
            'niveau_etudes' => 'Licence',
            'domaines_formation' => 'Gestion',
            'competences' => ['Vente'],
            'a_activite' => 'Non',
            'domaines_futurs' => ['Commerce'],
            'attentes' => ['Formation'],
            'formations_interet' => ['Finance'],
            'defi_principal' => 'Financement',
            'revenu_mensuel' => 'Aucun revenu',
        ]);
    }

    public function test_une_candidature_est_enregistree_et_confirmee_par_email(): void
    {
        Mail::fake();

        $this->soumettre()->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('membership_applications', ['email' => 'marie@example.com', 'statut' => 'en_attente']);
        Mail::assertSent(CandidatureRecue::class, fn ($mail) => $mail->hasTo('marie@example.com'));
    }

    public function test_l_acceptation_cree_le_compte_et_le_candidat_peut_se_connecter(): void
    {
        Mail::fake();
        $id = $this->soumettre()->json('id');

        $this->withToken($this->adminToken())
            ->postJson("/api/admin/membership-applications/{$id}/accept")
            ->assertOk();

        Mail::assertSent(CandidatureAcceptee::class, fn ($mail) => $mail->hasTo('marie@example.com'));

        // Le candidat se connecte avec le mot de passe choisi à l'inscription.
        $this->postJson('/api/auth/login', [
            'email' => 'marie@example.com',
            'password' => 'motdepasse123',
        ])->assertOk()->assertJsonPath('user.role', 'member');
    }

    public function test_le_refus_notifie_le_candidat(): void
    {
        Mail::fake();
        $id = $this->soumettre()->json('id');

        $this->withToken($this->adminToken())
            ->postJson("/api/admin/membership-applications/{$id}/reject")
            ->assertOk();

        Mail::assertSent(CandidatureRefusee::class, fn ($mail) => $mail->hasTo('marie@example.com'));
        $this->assertDatabaseHas('membership_applications', ['email' => 'marie@example.com', 'statut' => 'refuse']);
    }

    public function test_le_suivi_de_candidature_renvoie_le_statut(): void
    {
        Mail::fake();
        $this->soumettre();

        $this->postJson('/api/membership-applications/status', ['email' => 'marie@example.com'])
            ->assertOk()
            ->assertJsonPath('statut', 'en_attente');
    }
}
