<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\MemberNotification;
use App\Models\PersonalDocument;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonalDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_coffre_fort_prive_du_membre(): void
    {
        SubscriptionMode::set(true);
        $koffi = User::factory()->create(); // non abonné : le coffre-fort reste accessible
        $t = $this->tokenFor($koffi);

        $this->withToken($t)->postJson('/api/mes-documents', ['type' => 'passeport', 'fichier' => 'personnels/999/passeport.pdf', 'fichier_nom' => 'passeport.pdf'])->assertStatus(422);
        $this->withToken($t)->postJson('/api/mes-documents', ['type' => 'autre', 'fichier' => "personnels/{$koffi->id}/x.pdf", 'fichier_nom' => 'x.pdf'])->assertStatus(422);
        $d = $this->withToken($t)->postJson('/api/mes-documents', [
            'type' => 'passeport', 'fichier' => "personnels/{$koffi->id}/abc.pdf", 'fichier_nom' => 'passeport.pdf', 'mime' => 'application/pdf', 'octets' => 120000,
            'delivre_le' => now()->subYears(4)->toDateString(), 'expire_le' => now()->addDays(20)->toDateString(),
        ])->assertCreated()->json('document');
        $this->assertSame(['Passeport', 'bientot', false, 'pdf'], [$d['libelle'], $d['etat'], $d['partage'], $d['apercu']]);

        // Personne d'autre ne voit ni n'ouvre la pièce.
        $autre = $this->tokenFor(User::factory()->create());
        $this->assertSame([], $this->withToken($autre)->getJson('/api/mes-documents')->json('documents'));
        $this->withToken($autre)->getJson("/api/mes-documents/{$d['id']}/acces")->assertNotFound();
        $this->withToken($autre)->putJson("/api/mes-documents/{$d['id']}", ['type' => 'cv'])->assertNotFound();
        $this->withToken($t)->getJson("/api/mes-documents/{$d['id']}/acces")->assertOk()->assertJsonPath('document.fichier', "personnels/{$koffi->id}/abc.pdf");

        // L'équipe n'y a accès que si le membre partage la pièce, et c'est tracé.
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($admin)->getJson("/api/admin/documents-personnels/{$d['id']}/acces")->assertNotFound();
        $this->assertSame([], $this->withToken($admin)->getJson("/api/admin/members/{$koffi->id}")->json('documents_partages'));
        $this->withToken($t)->putJson("/api/mes-documents/{$d['id']}", ['type' => 'passeport', 'partage' => true])->assertOk()->assertJsonPath('document.partage', true);
        $this->assertCount(1, $this->withToken($admin)->getJson("/api/admin/members/{$koffi->id}")->json('documents_partages'));
        $this->withToken($admin)->getJson("/api/admin/documents-personnels/{$d['id']}/acces")->assertOk();
        $this->assertTrue(AuditLog::where('action', 'Consultation')->where('target', 'like', "Document personnel #{$d['id']}%")->exists());
        $this->assertNotNull($this->withToken($t)->getJson('/api/mes-documents')->json('documents.0.consulte_equipe_at'));

        // Remplacement du fichier : l'ancien chemin est rendu pour suppression.
        $r = $this->withToken($t)->putJson("/api/mes-documents/{$d['id']}", ['type' => 'passeport', 'fichier' => "personnels/{$koffi->id}/def.pdf", 'fichier_nom' => 'passeport-2026.pdf'])->assertOk();
        $this->assertSame("personnels/{$koffi->id}/abc.pdf", $r->json('ancien_fichier'));

        // Rappel d'expiration une seule fois.
        $this->assertSame(1, PersonalDocument::rappelsExpiration());
        $this->assertSame(0, PersonalDocument::rappelsExpiration());
        $this->assertTrue(MemberNotification::where('user_id', $koffi->id)->where('title', 'like', 'Votre document expire bientôt%')->exists());

        $this->withToken($t)->deleteJson("/api/mes-documents/{$d['id']}")->assertOk()->assertJsonPath('fichier', "personnels/{$koffi->id}/def.pdf");
        $this->assertSame(0, PersonalDocument::count());
    }
}
