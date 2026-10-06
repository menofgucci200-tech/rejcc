<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Certificats;
use App\Support\SignatureCertificat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    /** Membre qui termine une formation certifiante : certificat délivré. */
    private function certifie(?User $u = null): array
    {
        $u ??= User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $f = Formation::create(['title' => 'Gestion financière', 'category' => 'Finance', 'is_certifying' => true, 'modules_count' => 1,
            'duration' => '12 heures', 'competences' => ['Budget prévisionnel', 'Trésorerie']]);
        $f->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $e = FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $u->id, 'progress' => 100, 'examen_score' => 86, 'completed_at' => now()]);

        return [$u, Certificate::where('type', 'formation')->where('source_id', $e->id)->firstOrFail(), $e];
    }

    public function test_delivrance_unique_pdf_et_espace_membre(): void
    {
        [$u, $c, $e] = $this->certifie();
        $this->assertSame('Awa Traoré', $c->nom);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{9}$/', $c->code);
        $this->assertSame(['Budget prévisionnel', 'Trésorerie'], $c->details['competences']);
        $this->assertSame(86, $c->details['score']);
        $this->assertTrue(MemberNotification::where('user_id', $u->id)->where('title', 'like', 'Votre certificat est disponible%')->exists());

        // Idempotent : une nouvelle sauvegarde ne délivre pas un second certificat.
        $e->update(['progress' => 100]);
        Certificats::rattraper();
        $this->assertSame(1, Certificate::count());

        $t = $this->tokenFor($u);
        $this->assertCount(1, $this->withToken($t)->getJson('/api/my-certificates')->json('certificates'));
        $r = $this->withToken($t)->get("/api/my-certificates/{$c->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $c->refresh();
        $this->assertSame(hash('sha256', $r->getContent()), $c->empreinte);
        // Le PDF officiel est conservé tel quel : même fichier à chaque téléchargement.
        $this->assertSame($r->getContent(), $this->withToken($t)->get("/api/my-certificates/{$c->id}/pdf")->getContent());
        // Personne d'autre n'y accède.
        $this->withToken($this->tokenFor(User::factory()->create()))->get("/api/my-certificates/{$c->id}/pdf")->assertNotFound();
    }

    public function test_verification_publique_signature_et_fichier(): void
    {
        [, $c] = $this->certifie();
        $sig = SignatureCertificat::signer($c);

        $v = $this->getJson('/api/certificats/verifier/'.strtolower($c->codeLisible()).'?s='.$sig)->assertOk();
        $this->assertSame(['valide', true, 'Awa Traoré'], [$v->json('resultat'), $v->json('signature_qr'), $v->json('certificat.nom')]);
        $this->assertArrayNotHasKey('email', $v->json('certificat'));
        // Une signature recopiée d'un autre certificat ne correspond pas.
        [, $autre] = $this->certifie(User::factory()->create(['prenom' => 'Koffi', 'nom' => 'Yao']));
        $this->assertFalse($this->getJson('/api/certificats/verifier/'.$autre->code.'?s='.$sig)->json('signature_qr'));
        $this->getJson('/api/certificats/verifier/AAAAAAAAA')->assertNotFound()->assertJsonPath('resultat', 'inconnu');

        // Fichier déposé : intact s'il s'agit exactement du PDF délivré.
        $pdf = Storage::disk('local')->get(Certificats::pdf($c));
        $this->postJson('/api/certificats/verifier-fichier', ['empreinte' => hash('sha256', $pdf)])->assertOk()->assertJsonPath('resultat', 'intact');
        $this->postJson('/api/certificats/verifier-fichier', ['empreinte' => hash('sha256', $pdf.' '), 'code' => $c->code])
            ->assertNotFound()->assertJsonPath('resultat', 'modifie');
        $this->postJson('/api/certificats/verifier-fichier', ['empreinte' => hash('sha256', 'x')])->assertNotFound()->assertJsonPath('resultat', 'inconnu');

        $this->assertSame(3, $c->fresh()->verifications);
        $this->assertSame(3, CertificateVerification::whereIn('resultat', ['inconnu', 'modifie'])->count());
        $this->get('/api/certificats/verifier/'.$c->code.'/pdf')->assertOk();
    }

    public function test_revocation_et_reemission(): void
    {
        [$u, $c] = $this->certifie();
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $t = $this->tokenFor($u);

        // Demande de correction du membre → compteur admin.
        $this->withToken($t)->postJson("/api/my-certificates/{$c->id}/correction", ['message' => 'Mon nom est Awa Traoré-Koné'])->assertOk();
        $this->assertSame(1, $this->withToken($admin)->getJson('/api/admin/certificates')->json('stats.corrections'));

        $n = $this->withToken($admin)->postJson("/api/admin/certificates/{$c->id}/reemettre", ['nom' => 'Awa Traoré-Koné', 'motif' => 'Nom complet'])
            ->assertOk()->json('certificate');
        $this->assertSame([$c->reference.'-R1', 'Awa Traoré-Koné', 'valide'], [$n['reference'], $n['nom'], $n['statut']]);
        $c->refresh();
        $this->assertSame('revoque', $c->statut);
        $ancien = $this->getJson('/api/certificats/verifier/'.$c->code)->assertOk();
        $this->assertSame(['revoque', $n['code']], [$ancien->json('resultat'), $ancien->json('certificat.remplace_par')]);
        // Le membre ne voit plus que la version corrigée.
        $this->assertSame([$n['code']], array_column($this->withToken($t)->getJson('/api/my-certificates')->json('certificates'), 'code'));

        $nouveau = Certificate::where('reference', $n['reference'])->first();
        $this->withToken($admin)->postJson("/api/admin/certificates/{$nouveau->id}/revoquer", ['motif' => ''])->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/certificates/{$nouveau->id}/revoquer", ['motif' => 'Examen invalidé après contrôle'])->assertOk();
        $this->assertSame('revoque', $this->getJson('/api/certificats/verifier/'.$nouveau->code)->json('resultat'));
        $this->assertTrue(MemberNotification::where('user_id', $u->id)->where('title', 'like', 'Certificat révoqué%')->exists());
        $this->get('/api/certificats/verifier/'.$nouveau->code.'/pdf')->assertNotFound();
    }

    public function test_attestations_evenement_et_parcours(): void
    {
        $membre = User::factory()->create(['prenom' => 'Paul', 'nom' => 'Ahoua']);
        $ev = Event::create(['title' => 'Séminaire financement', 'slug' => 'seminaire', 'category' => 'Séminaire', 'statut' => 'publie',
            'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(6), 'location' => 'Cocody', 'attestation' => true]);
        $present = EventRegistration::create(['event_id' => $ev->id, 'user_id' => $membre->id, 'present_at' => now()->subDays(2)]);
        EventRegistration::create(['event_id' => $ev->id, 'prenom' => 'Esther', 'nom' => 'Kouamé', 'email' => 'esther@example.com', 'present_at' => now()->subDays(2), 'billet' => 'B1']);
        EventRegistration::create(['event_id' => $ev->id, 'user_id' => User::factory()->create()->id]); // absent
        $sans = Event::create(['title' => 'Afterwork', 'slug' => 'afterwork', 'category' => 'Réseautage', 'statut' => 'publie', 'starts_at' => now()->subDays(3)]);
        EventRegistration::create(['event_id' => $sans->id, 'user_id' => $membre->id, 'present_at' => now()->subDays(3)]);

        $n = Certificats::rattraper();
        $this->assertSame(2, $n['evenement']);
        $att = Certificate::where('type', 'evenement')->where('source_id', $present->id)->first();
        $this->assertSame(['Attestation de participation', 'Séminaire financement', 'Cocody'], [$att->intitule, $att->titre, $att->details['lieu_evenement']]);
        $this->assertSame('esther@example.com', Certificate::whereNull('user_id')->value('email'));
        $this->assertSame(0, Certificats::rattraper()['evenement']);

        // Parcours terminé → attestation de parcours.
        $p = \App\Models\Path::create(['title' => 'Jeune entrepreneur', 'slug' => 'jeune', 'is_published' => true]);
        $f = Formation::create(['title' => 'Business plan', 'category' => 'Création', 'is_certifying' => false, 'modules_count' => 1, 'is_published' => true]);
        $f->modules()->create(['titre' => 'M1', 'ordre' => 1]);
        $p->formations()->attach($f->id, ['ordre' => 1]);
        FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);
        $parcours = Certificate::where('type', 'parcours')->where('user_id', $membre->id)->first();
        $this->assertNotNull($parcours);
        $this->assertSame(['Business plan'], $parcours->details['formations']);
    }

    public function test_reglages_signataires_et_bio(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $png = 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->withToken($admin)->putJson('/api/admin/certificats/reglages', ['lieu' => 'Abidjan', 'signataires' => [
            ['nom' => 'Jean Kouadio', 'fonction' => 'Président du REJCC', 'signature_image' => $png],
            ['nom' => '', 'fonction' => 'Responsable des formations'],
        ]])->assertOk();
        $r = Certificats::reglages();
        $this->assertSame('Président du REJCC', $r['signataires'][0]['fonction']);
        Storage::disk('local')->assertExists($r['signataires'][0]['signature']);
        $this->withToken($admin)->putJson('/api/admin/certificats/reglages', ['lieu' => 'Abidjan', 'signataires' => [
            ['fonction' => 'A', 'signature_image' => 'data:image/png;base64,'.base64_encode('pas une image')], ['fonction' => 'Responsable'],
        ]])->assertStatus(422);

        // Le certificat garde les signataires du jour de sa délivrance ; il n'apparaît sur la bio publique qu'au choix du membre.
        \App\Support\SubscriptionMode::set(false);
        [$u, $c] = $this->certifie(User::factory()->abonne()->create(['prenom' => 'Awa', 'nom' => 'Traoré']));
        $this->assertSame('Jean Kouadio', $c->signataires[0]['nom']);
        $this->assertSame([], $this->getJson('/api/member-card/'.$u->id)->json('card.certificats'));
        $this->withToken($this->tokenFor($u))->putJson("/api/my-certificates/{$c->id}/bio", ['visible_bio' => true])->assertOk();
        $this->assertSame($c->reference, $this->getJson('/api/member-card/'.$u->id)->json('card.certificats.0.reference'));
    }

    public function test_signature_electronique_du_pdf_si_configuree(): void
    {
        // Certificat numérique de test (auto-signé) : en production, celui acheté auprès d'une autorité reconnue.
        $cle = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'REJCC TEST'], $cle);
        openssl_x509_export(openssl_csr_sign($csr, null, $cle, 30), $crt);
        openssl_pkey_export($cle, $pem);
        $dir = sys_get_temp_dir();
        file_put_contents("$dir/rejcc-test.crt", $crt);
        file_put_contents("$dir/rejcc-test.key", $pem);
        config(['services.certificats.pdf_certificat' => "$dir/rejcc-test.crt", 'services.certificats.pdf_cle' => "$dir/rejcc-test.key"]);

        [, $c] = $this->certifie();
        $pdf = Storage::disk('local')->get(Certificats::pdf($c));
        $this->assertTrue($c->fresh()->signe_electroniquement);
        $this->assertStringContainsString('/ByteRange', $pdf);
        $this->assertStringContainsString('adbe.pkcs7.detached', $pdf);
        $this->assertTrue($this->getJson('/api/certificats/verifier/'.$c->code)->json('certificat.signe_electroniquement'));
    }
}
