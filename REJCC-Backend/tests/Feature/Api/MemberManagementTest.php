<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberManagementTest extends TestCase
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

    public function test_le_dossier_membre_regroupe_profil_candidature_et_formations(): void
    {
        $membre = User::factory()->create([
            'prenom' => 'Marie', 'nom' => 'Aka', 'email' => 'marie@example.com', 'ville' => 'Abidjan',
        ]);

        \App\Models\MembershipApplication::create([
            'prenom' => 'Marie', 'nom' => 'Aka', 'sexe' => 'Femme', 'tranche_age' => '18-25 ans',
            'whatsapp' => '0102030405', 'email' => 'marie@example.com', 'diocese' => 'Abidjan',
            'ville' => 'Abidjan', 'password' => 'motdepasse123', 'connotation_religieuse' => 'Catholique',
            'paroisse' => 'Sainte-Anne', 'statut_actuel' => ['Étudiant'], 'niveau_etudes' => 'Licence',
            'domaines_formation' => 'Gestion', 'competences' => ['Vente'], 'a_activite' => 'Non',
            'domaines_futurs' => ['Commerce'], 'attentes' => ['Formation'], 'formations_interet' => ['Finance'],
            'defi_principal' => 'Financement', 'revenu_mensuel' => 'Aucun revenu', 'statut' => 'accepte',
        ]);

        $formation = Formation::create(['title' => 'Leadership', 'category' => 'Leadership', 'modules_count' => 4]);
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $membre->id, 'progress' => 50]);

        $dossier = $this->withToken($this->adminToken())
            ->getJson("/api/admin/members/{$membre->id}")
            ->assertOk()
            ->json();

        $this->assertSame('Marie', $dossier['member']['prenom']);
        $this->assertSame(str_pad((string) $membre->id, 4, '0', STR_PAD_LEFT), $dossier['member']['code']);
        $this->assertStringStartsWith('REJCC-', $dossier['member']['reference']);
        $this->assertSame('Sainte-Anne', $dossier['application']['paroisse']);
        $this->assertSame('Leadership', $dossier['formations'][0]['title']);
        $this->assertSame(50, $dossier['formations'][0]['progress']);
    }

    public function test_l_admin_modifie_les_informations_et_le_role_d_un_membre(): void
    {
        $membre = User::factory()->create(['prenom' => 'Jean', 'nom' => 'Kouassi']);

        $this->withToken($this->adminToken())->putJson("/api/admin/members/{$membre->id}", [
            'prenom' => 'Jean-Baptiste',
            'ville' => 'Bouaké',
            'role' => 'mentor',
        ])->assertOk()->assertJsonPath('member.role', 'mentor');

        $membre->refresh();
        $this->assertSame('Jean-Baptiste', $membre->prenom);
        $this->assertSame('Bouaké', $membre->ville);
        $this->assertStringContainsString('Jean-Baptiste', $membre->name);
    }

    public function test_la_carte_membre_publique_repond_au_code_a_4_chiffres(): void
    {
        $membre = User::factory()->create(['prenom' => 'Marie', 'nom' => 'Aka', 'ville' => 'Abidjan']);
        $code = str_pad((string) $membre->id, 4, '0', STR_PAD_LEFT);

        $card = $this->getJson("/api/member-card/{$code}")->assertOk()->json('card');

        $this->assertSame('Marie', $card['prenom']);
        $this->assertSame($code, $card['code']);
        $this->assertSame('member', $card['role']);
        $this->assertSame('Membre officiel', $card['role_label']);
        $this->assertTrue($card['is_active']);

        // N° membre : REJCC-{année}-{jour}{mois}{code}
        $attendu = 'REJCC-'.$membre->created_at->format('Y').'-'.$membre->created_at->format('dm').$code;
        $this->assertSame($attendu, $card['numero']);

        // La carte n'expose pas de données sensibles.
        $this->assertArrayNotHasKey('email', $card);
        $this->assertArrayNotHasKey('telephone', $card);
    }

    public function test_le_libelle_de_role_varie_selon_le_statut(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame('Mentor', $this->getJson('/api/member-card/'.$mentor->id)->json('card.role_label'));
        $this->assertSame('Administrateur', $this->getJson('/api/member-card/'.$admin->id)->json('card.role_label'));
    }

    public function test_la_carte_membre_renvoie_404_pour_un_code_inconnu(): void
    {
        $this->getJson('/api/member-card/9999')->assertStatus(404);
        $this->getJson('/api/member-card/abcd')->assertStatus(404);
    }
}
