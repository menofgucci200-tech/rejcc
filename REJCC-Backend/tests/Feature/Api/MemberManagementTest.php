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

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

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
        $membre = User::factory()->abonne()->create(['prenom' => 'Marie', 'nom' => 'Aka', 'ville' => 'Abidjan']);
        $code = str_pad((string) $membre->id, 4, '0', STR_PAD_LEFT);

        $card = $this->getJson("/api/member-card/{$code}")->assertOk()->json('card');

        $this->assertSame('Marie', $card['prenom']);
        $this->assertSame($code, $card['code']);
        $this->assertSame('member', $card['role']);
        $this->assertSame('Membre officiel', $card['role_label']);
        $this->assertTrue($card['is_active']);

        // N° membre : REJCC-{année}-{jour}{mois}-{code}
        $attendu = 'REJCC-'.$membre->created_at->format('Y').'-'.$membre->created_at->format('dm').'-'.$code;
        $this->assertSame($attendu, $card['numero']);

        // Page publique (sans connexion) : le contact est masqué par défaut,
        // pour qu'on ne puisse pas collecter les coordonnées en essayant les codes.
        $this->assertNull($card['email']);
        $this->assertNull($card['telephone']);
        $this->assertSame([], $card['listings']);

        // Validité de la carte = fin de l'abonnement annuel (date anniversaire).
        $this->assertTrue($card['a_jour']);
        $this->assertSame($membre->subscription_expires_at->toDateString(), $card['valable_jusqu']);
    }

    public function test_le_contact_public_n_apparait_que_sur_choix_du_membre(): void
    {
        $membre = User::factory()->abonne()->create([
            'preferences' => ['visibilite_profil' => true, 'coordonnees_publiques' => true],
        ]);

        $card = $this->getJson('/api/member-card/'.$membre->id)->assertOk()->json('card');

        $this->assertSame($membre->email, $card['email']);
        $this->assertSame($membre->telephone, $card['telephone']);
    }

    public function test_la_page_biographique_publie_les_informations_du_membre(): void
    {
        $membre = User::factory()->abonne()->create([
            'titre' => 'Fondatrice d\'AgroVert',
            'diocese' => 'Archidiocèse d\'Abidjan',
            'competences' => ['Agrobusiness', 'Export'],
            'parcours' => [['periode' => '2022 – auj.', 'titre' => 'Fondatrice', 'structure' => 'AgroVert']],
            'liens' => ['linkedin' => 'https://www.linkedin.com/in/test'],
        ]);
        \App\Models\Project::create(['user_id' => $membre->id, 'title' => 'Séchage de mangues', 'description' => 'Unité de transformation.', 'statut' => 'valide']);
        \App\Models\Project::create(['user_id' => $membre->id, 'title' => 'Projet confidentiel', 'description' => 'Ne doit pas être public.', 'statut' => 'evaluation']);

        $card = $this->getJson('/api/member-card/'.$membre->id)->assertOk()->json('card');

        $this->assertSame('Fondatrice d\'AgroVert', $card['titre']);
        $this->assertSame(['Agrobusiness', 'Export'], $card['competences']);
        $this->assertSame('Fondatrice', $card['parcours'][0]['titre']);
        $this->assertSame('https://www.linkedin.com/in/test', $card['liens']['linkedin']);
        // Un projet encore en évaluation n'est pas rendu public.
        $this->assertSame(['Séchage de mangues'], array_column($card['projets'], 'title'));
        $this->assertSame(['formations_terminees' => 0, 'evenements' => 0, 'certificats' => 0], $card['engagement']);
    }

    public function test_le_contact_est_masque_si_le_profil_n_est_pas_visible(): void
    {
        $membre = User::factory()->abonne()->create([
            'bio' => 'Entrepreneur dans l\'agro-transformation.',
            'preferences' => ['visibilite_profil' => false],
        ]);

        $card = $this->getJson('/api/member-card/'.$membre->id)->assertOk()->json('card');

        $this->assertNull($card['email']);
        $this->assertNull($card['telephone']);
        // Les informations professionnelles restent affichées.
        $this->assertSame('Entrepreneur dans l\'agro-transformation.', $card['bio']);
    }

    public function test_le_libelle_de_role_varie_selon_le_statut(): void
    {
        $mentor = User::factory()->abonne()->create(['role' => 'mentor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame('Mentor', $this->getJson('/api/member-card/'.$mentor->id)->json('card.role_label'));
        $this->assertSame('Administrateur', $this->getJson('/api/member-card/'.$admin->id)->json('card.role_label'));
    }

    public function test_la_carte_membre_renvoie_404_pour_un_code_inconnu(): void
    {
        $this->getJson('/api/member-card/9999')->assertStatus(404);
        $this->getJson('/api/member-card/abcd')->assertStatus(404);
    }

    public function test_la_carte_membre_est_verrouillee_sans_abonnement_actif(): void
    {
        $membre = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $code = str_pad((string) $membre->id, 4, '0', STR_PAD_LEFT);

        $card = $this->getJson("/api/member-card/{$code}")->assertOk()->json('card');

        $this->assertTrue($card['locked']);
        // Aucune donnée personnelle, pas même le nom.
        $this->assertArrayNotHasKey('prenom', $card);
        $this->assertArrayNotHasKey('nom', $card);
        $this->assertArrayNotHasKey('email', $card);
        $this->assertArrayNotHasKey('role', $card);
    }

    public function test_la_fiche_detaillee_d_un_membre_est_reservee_aux_abonnes(): void
    {
        $viewer = User::factory()->create(); // pas d'abonnement
        $cible = User::factory()->create();

        $this->withToken($this->tokenFor($viewer))->getJson("/api/members/{$cible->id}")
            ->assertStatus(402);
    }

    public function test_la_fiche_detaillee_d_un_membre_expose_le_profil_complet(): void
    {
        $viewer = User::factory()->abonne()->create();
        $cible = User::factory()->create([
            'prenom' => 'Awa', 'nom' => 'Koffi', 'bio' => 'Plombier depuis 8 ans.',
            'ville' => 'Abidjan', 'secteur' => 'BTP', 'organisation' => 'Awa Plomberie',
        ]);

        $fiche = $this->withToken($this->tokenFor($viewer))->getJson("/api/members/{$cible->id}")
            ->assertOk()->json('member');

        $this->assertSame('Awa', $fiche['prenom']);
        $this->assertSame('Plombier depuis 8 ans.', $fiche['bio']);
        $this->assertSame('Awa Plomberie', $fiche['organisation']);
        $this->assertSame($cible->telephone, $fiche['telephone']);
    }
}
