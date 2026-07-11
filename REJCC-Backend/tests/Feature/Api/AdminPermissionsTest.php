<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
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

    public function test_un_admin_complet_accede_a_toutes_les_sections(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));

        $this->withToken($token)->getJson('/api/admin/formations')->assertOk();
        $this->withToken($token)->getJson('/api/admin/events')->assertOk();
        $this->withToken($token)->getJson('/api/admin/members')->assertOk();
    }

    public function test_un_admin_restreint_n_accede_qu_a_ses_sections(): void
    {
        $token = $this->tokenFor(User::factory()->create([
            'role' => 'admin',
            'permissions' => ['formations'],
        ]));

        // Sa section : OK.
        $this->withToken($token)->getJson('/api/admin/formations')->assertOk();
        // Le tableau de bord (stats) reste accessible à tout admin.
        $this->withToken($token)->getJson('/api/admin/stats')->assertOk();
        // Les autres sections : refusées.
        $this->withToken($token)->getJson('/api/admin/events')->assertStatus(403);
        $this->withToken($token)->getJson('/api/admin/members')->assertStatus(403);
        $this->withToken($token)->postJson('/api/admin/news', [])->assertStatus(403);
    }

    public function test_l_admin_cree_un_mentor_et_un_admin_restreint(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        // Un mentor.
        $this->withToken($token)->postJson('/api/admin/members', [
            'prenom' => 'Paul',
            'nom' => 'Mentor',
            'email' => 'mentor@example.com',
            'telephone' => '0102030405',
            'password' => 'motdepasse123',
            'role' => 'mentor',
        ])->assertOk()->assertJsonPath('member.role', 'mentor');

        // Un admin limité aux formations.
        $restreint = $this->withToken($token)->postJson('/api/admin/members', [
            'prenom' => 'Alice',
            'nom' => 'Restreinte',
            'email' => 'alice@example.com',
            'telephone' => '0102030406',
            'password' => 'motdepasse123',
            'role' => 'admin',
            'permissions' => ['formations'],
        ])->assertOk()->json('member');

        $this->assertSame(['formations'], $restreint['permissions']);

        // Le nouvel admin restreint peut se connecter et son payload expose ses permissions.
        $login = $this->postJson('/api/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'motdepasse123',
        ])->assertOk();
        $this->assertSame(['formations'], $login->json('user.permissions'));

        // Les permissions d'un non-admin sont ignorées.
        $membre = $this->withToken($token)->postJson('/api/admin/members', [
            'prenom' => 'Jean',
            'nom' => 'Simple',
            'email' => 'jean-simple@example.com',
            'telephone' => '0102030407',
            'password' => 'motdepasse123',
            'role' => 'member',
            'permissions' => ['formations'],
        ])->json('member');
        $this->assertNull($membre['permissions']);
    }

    public function test_le_rejet_d_une_candidature_enregistre_le_motif(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $application = \App\Models\MembershipApplication::create([
            'prenom' => 'Test', 'nom' => 'Refus', 'sexe' => 'Homme', 'tranche_age' => '18-25 ans',
            'whatsapp' => '0102030405', 'email' => 'refus@example.com', 'diocese' => 'Abidjan',
            'ville' => 'Abidjan', 'password' => 'motdepasse123', 'connotation_religieuse' => 'Catholique',
            'paroisse' => 'Sainte-Anne', 'statut_actuel' => ['Étudiant'], 'niveau_etudes' => 'Licence',
            'domaines_formation' => 'Gestion', 'competences' => ['Vente'], 'a_activite' => 'Non',
            'domaines_futurs' => ['Commerce'], 'attentes' => ['Formation'], 'formations_interet' => ['Finance'],
            'defi_principal' => 'Financement', 'revenu_mensuel' => 'Aucun revenu', 'statut' => 'en_attente',
        ]);

        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->postJson("/api/admin/membership-applications/{$application->id}/reject", [
            'motif' => 'Dossier incomplet : merci de préciser votre paroisse d\'attache.',
        ])->assertOk();

        $application->refresh();
        $this->assertSame('refuse', $application->statut);
        $this->assertStringContainsString('Dossier incomplet', $application->reject_reason);

        \Illuminate\Support\Facades\Mail::assertSent(
            \App\Mail\CandidatureRefusee::class,
            fn ($mail) => $mail->hasTo('refus@example.com'),
        );
    }
}
