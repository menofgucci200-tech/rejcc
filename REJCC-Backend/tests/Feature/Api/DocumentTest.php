<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function cat(string $nom = 'Guides'): DocumentCategory
    {
        return DocumentCategory::firstOrCreate(['nom' => $nom]);
    }

    private function doc(array $o = []): Document
    {
        return Document::create($o + ['title' => 'Guide du business plan', 'category_id' => $this->cat()->id, 'fichier' => 'documents/guide.pdf',
            'fichier_nom' => 'guide.pdf', 'mime' => 'application/pdf', 'octets' => 2_400_000, 'statut' => 'publie', 'publie_at' => now()]);
    }

    public function test_admin_publie_un_fichier_avec_categorie_et_notification(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $membre = User::factory()->create();

        $this->withToken($admin)->postJson('/api/admin/documents', ['title' => 'Guide', 'category_id' => $this->cat()->id])
            ->assertStatus(422)->assertJsonPath('message', 'Ajoutez un fichier ou collez un lien.');
        $d = $this->withToken($admin)->postJson('/api/admin/documents', [
            'title' => 'Guide du business plan', 'category_id' => $this->cat()->id, 'description' => 'Construire son business plan pas à pas.',
            'fichier' => 'documents/2026/10/guide.pdf', 'fichier_nom' => 'guide.pdf', 'mime' => 'application/pdf', 'octets' => 2_400_000, 'notifier' => true,
        ])->assertCreated()->assertJsonPath('notifies', 1)->json('document');
        $this->assertSame(['PDF', '2,3 Mo'], [$d['type'], $d['taille']]);
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'Nouveau document : Guide du business plan')->exists());

        // Remplacement du fichier : l'ancien est signalé pour suppression.
        $r = $this->withToken($admin)->putJson("/api/admin/documents/{$d['id']}", [
            'title' => 'Guide du business plan', 'category_id' => $this->cat()->id, 'fichier' => 'documents/2026/10/guide-v2.pdf', 'fichier_nom' => 'guide-v2.pdf', 'octets' => 1000,
        ])->assertOk()->json();
        $this->assertSame('documents/2026/10/guide.pdf', $r['ancien_fichier']);
        $this->assertSame('1 Ko', $r['document']['taille']);
    }

    public function test_liste_recherche_et_le_fichier_n_est_jamais_expose(): void
    {
        $this->doc();
        $this->doc(['title' => 'Modèle de pitch deck', 'category_id' => $this->cat('Modèles')->id, 'description' => 'Présenter son projet aux investisseurs.']);
        $t = $this->tokenFor(User::factory()->create());

        $res = $this->withToken($t)->getJson('/api/documents')->assertOk()->json();
        $this->assertCount(2, $res['documents']);
        $this->assertStringNotContainsString('documents/guide.pdf', json_encode($res));
        $this->assertSame(['Modèle de pitch deck'], array_column($this->withToken($t)->getJson('/api/documents?q=investisseurs')->json('documents'), 'title'));
        $this->assertSame(['Guide du business plan'], array_column($this->withToken($t)->getJson('/api/documents?categorie='.$this->cat()->id)->json('documents'), 'title'));
    }

    public function test_acces_par_document(): void
    {
        SubscriptionMode::set(true);
        $groupe = Group::create(['name' => 'Agriculture', 'slug' => 'agro']);
        $tous = $this->doc();
        $abonnes = $this->doc(['title' => 'Annuaire des financements', 'acces' => 'abonnes']);
        $mentors = $this->doc(['title' => 'Guide du mentor', 'acces' => 'mentors']);
        $grp = $this->doc(['title' => 'Fiches cultures', 'acces' => 'groupe', 'group_id' => $groupe->id]);

        $simple = User::factory()->create();
        $t = $this->tokenFor($simple);
        $this->withToken($t)->getJson("/api/documents/{$tous->id}/acces?action=telechargement")->assertOk()->assertJsonPath('document.fichier', 'documents/guide.pdf');
        $this->assertSame(1, $tous->fresh()->telechargements);
        $this->withToken($t)->getJson("/api/documents/{$abonnes->id}/acces")->assertStatus(403)->assertJsonPath('code', 'acces_refuse');
        $this->withToken($t)->getJson("/api/documents/{$mentors->id}/acces")->assertStatus(403);
        $this->withToken($t)->getJson("/api/documents/{$grp->id}/acces")->assertStatus(403)->assertJsonPath('message', 'Document réservé aux membres du groupe « Agriculture ».');

        // La liste montre le document verrouillé avec la raison.
        $liste = collect($this->withToken($t)->getJson('/api/documents')->json('documents'))->keyBy('title');
        $this->assertTrue($liste['Annuaire des financements']['verrouille']);
        $this->assertFalse($liste['Guide du business plan']['verrouille']);

        $simple->groups()->attach($groupe->id);
        $this->withToken($t)->getJson("/api/documents/{$grp->id}/acces")->assertOk();
        $this->withToken($this->tokenFor(User::factory()->abonne()->create()))->getJson("/api/documents/{$abonnes->id}/acces?action=vue")->assertOk();
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'mentor'])))->getJson("/api/documents/{$mentors->id}/acces")->assertOk();
        $this->assertSame(1, $abonnes->fresh()->vues);
    }

    public function test_proposition_d_un_membre_validee(): void
    {
        SubscriptionMode::set(true);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($this->tokenFor(User::factory()->create()))->postJson('/api/documents', ['title' => 'Modèle', 'category_id' => $this->cat()->id, 'url' => 'https://exemple.ci/modele.pdf'])->assertStatus(402);

        $awa = User::factory()->abonne()->create(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $t = $this->tokenFor($awa);
        // Jamais le fichier d'un autre : le chemin doit être dans le dossier du membre.
        $this->withToken($t)->postJson('/api/documents', ['title' => 'Modèle de facture', 'category_id' => $this->cat()->id, 'fichier' => 'documents/propositions/999/autre.pdf'])->assertStatus(422);
        $this->withToken($t)->postJson('/api/documents', ['title' => 'Modèle de facture', 'category_id' => $this->cat()->id, 'fichier' => "documents/propositions/{$awa->id}/../999/autre.pdf"])->assertStatus(422);
        $p = $this->withToken($t)->postJson('/api/documents', ['title' => 'Modèle de facture', 'category_id' => $this->cat()->id, 'fichier' => "documents/propositions/{$awa->id}/facture.xlsx", 'fichier_nom' => 'facture.xlsx'])
            ->assertCreated()->json('document');
        $this->assertSame(['en_attente', 'Excel'], [$p['statut'], $p['type']]);
        $autre = $this->tokenFor(User::factory()->create());
        $this->assertSame([], $this->withToken($autre)->getJson('/api/documents')->json('documents'));
        $this->assertCount(1, $this->withToken($t)->getJson('/api/documents')->json('mes_propositions'));

        $this->withToken($admin)->postJson("/api/admin/documents/{$p['id']}/decision", ['decision' => 'refuser'])->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/documents/{$p['id']}/decision", ['decision' => 'publier'])->assertOk();
        $doc = $this->withToken($autre)->getJson('/api/documents')->json('documents.0');
        $this->assertSame('Awa Traoré', $doc['contributeur']);
        $this->assertTrue(MemberNotification::where('user_id', $awa->id)->where('title', 'like', 'Document publié%')->exists());
    }

    public function test_categories_gerees(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($admin)->postJson('/api/admin/document-categories', ['nom' => 'Guides'])->assertCreated();
        $this->withToken($admin)->postJson('/api/admin/document-categories', ['nom' => 'Guides'])->assertStatus(422);
        $d = $this->doc();
        $this->withToken($admin)->deleteJson('/api/admin/document-categories/'.$this->cat()->id)->assertStatus(422);
        $this->withToken($admin)->putJson('/api/admin/document-categories/'.$this->cat()->id, ['nom' => 'Guides pratiques'])->assertOk();
        $this->assertSame('Guides pratiques', $d->fresh()->category);
    }
}
