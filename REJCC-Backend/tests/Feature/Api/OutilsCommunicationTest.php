<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\CommunicationFichier;
use App\Models\CommunicationTexte;
use App\Models\ContactMedia;
use App\Models\User;
use App\Support\OutilsCommunication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Boîte à outils du département Communication : documents de marque,
 * contacts médias et textes prêts à copier (rubrique « communication »).
 */
class OutilsCommunicationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function com(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => ['actualites', 'communication'], ...$extra]);
    }

    private function testeurCom(): User
    {
        return $this->com(['departement' => 'Communication', 'bureau_departement' => null]);
    }

    private function fichier(array $extra = []): array
    {
        return ['titre' => 'Charte graphique 2026', 'categorie' => 'charte', 'fichier' => 'marque/2026/11/charte-ab12cd.pdf', 'fichier_nom' => 'Charte REJCC.pdf', 'mime' => 'application/pdf', 'octets' => 2_400_000, ...$extra];
    }

    // ── Permissions ──────────────────────────────────────────────────────

    public function test_toutes_les_routes_exigent_la_rubrique_communication(): void
    {
        $autre = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['actualites', 'documents']]));
        $membre = $this->tokenFor(User::factory()->create(['role' => 'member']));

        foreach ([
            ['get', '/api/admin/communication/fichiers'], ['post', '/api/admin/communication/fichiers'],
            ['put', '/api/admin/communication/fichiers/1'], ['delete', '/api/admin/communication/fichiers/1'],
            ['get', '/api/admin/communication/fichiers/1/acces'],
            ['get', '/api/admin/communication/contacts-medias'], ['post', '/api/admin/communication/contacts-medias'],
            ['put', '/api/admin/communication/contacts-medias/1'], ['delete', '/api/admin/communication/contacts-medias/1'],
            ['get', '/api/admin/communication/textes'], ['post', '/api/admin/communication/textes'],
            ['post', '/api/admin/communication/textes/restaurer'],
            ['put', '/api/admin/communication/textes/1'], ['delete', '/api/admin/communication/textes/1'],
        ] as [$methode, $url]) {
            $this->withToken($autre)->json($methode, $url)->assertStatus(403);
            $this->withToken($membre)->json($methode, $url)->assertStatus(403);
        }
        $this->withToken($autre)->getJson('/api/admin/export/contacts-medias')->assertStatus(403);
    }

    public function test_le_departement_communication_recoit_la_rubrique_et_l_admin_general_y_accede(): void
    {
        $this->assertContains('communication', \App\Support\Departements::sections('communication'));
        $general = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));
        $this->withToken($general)->getJson('/api/admin/communication/textes')->assertOk();
    }

    // ── Documents de marque ──────────────────────────────────────────────

    public function test_crud_des_documents_de_marque(): void
    {
        $token = $this->tokenFor($this->com());

        $r = $this->withToken($token)->postJson('/api/admin/communication/fichiers', $this->fichier())->assertCreated();
        $id = $r->json('fichier.id');
        $this->assertSame('Charte graphique', $r->json('fichier.categorie_label'));
        $this->assertSame('PDF', $r->json('fichier.extension'));
        $this->assertSame('2,3 Mo', $r->json('fichier.taille'));

        $this->withToken($token)->postJson('/api/admin/communication/fichiers', $this->fichier(['titre' => 'Logo principal', 'categorie' => 'logos', 'fichier' => 'marque/2026/11/logo.svg', 'fichier_nom' => 'logo.svg']))->assertCreated();

        $liste = $this->withToken($token)->getJson('/api/admin/communication/fichiers')->assertOk();
        $this->assertCount(2, $liste->json('fichiers'));
        $this->assertSame(1, $liste->json('compteurs.logos'));
        $this->assertCount(1, $this->withToken($token)->getJson('/api/admin/communication/fichiers?categorie=logos')->json('fichiers'));
        $this->assertCount(1, $this->withToken($token)->getJson('/api/admin/communication/fichiers?q=charte')->json('fichiers'));

        // Remplacement du fichier : l'ancien chemin est rendu pour suppression.
        $m = $this->withToken($token)->putJson("/api/admin/communication/fichiers/{$id}", $this->fichier(['titre' => 'Charte v2', 'fichier' => 'marque/2026/11/charte-v2.pdf', 'fichier_nom' => 'Charte v2.pdf']))->assertOk();
        $this->assertSame('marque/2026/11/charte-ab12cd.pdf', $m->json('ancien_fichier'));
        // Modification sans nouveau fichier (le dashboard renvoie le même) : rien à supprimer.
        $m = $this->withToken($token)->putJson("/api/admin/communication/fichiers/{$id}", ['titre' => 'Charte v3', 'categorie' => 'charte', 'fichier' => 'marque/2026/11/charte-v2.pdf', 'fichier_nom' => 'Charte v2.pdf'])->assertOk();
        $this->assertNull($m->json('ancien_fichier'));
        $this->assertSame('marque/2026/11/charte-v2.pdf', CommunicationFichier::find($id)->fichier);
        $this->assertSame(2_400_000, CommunicationFichier::find($id)->octets);

        $s = $this->withToken($token)->deleteJson("/api/admin/communication/fichiers/{$id}")->assertOk();
        $this->assertSame('marque/2026/11/charte-v2.pdf', $s->json('fichier'));
        $this->assertNull(CommunicationFichier::find($id));
    }

    public function test_validation_des_documents_de_marque(): void
    {
        $token = $this->tokenFor($this->com());
        $url = '/api/admin/communication/fichiers';

        $this->withToken($token)->postJson($url, $this->fichier(['titre' => '']))->assertStatus(422)->assertJsonPath('message', 'Donnez un titre au document.');
        $this->withToken($token)->postJson($url, $this->fichier(['categorie' => 'divers']))->assertStatus(422)->assertJsonPath('message', 'Catégorie inconnue.');
        $this->withToken($token)->postJson($url, $this->fichier(['fichier' => null]))->assertStatus(422)->assertJsonPath('message', 'Choisissez un fichier à envoyer ou collez un lien.');
        $this->withToken($token)->postJson($url, ['titre' => 'Gabarit', 'categorie' => 'gabarits', 'lien' => 'http://canva.com/x'])->assertStatus(422)->assertJsonPath('message', 'Le lien doit commencer par https://.');
        // Le chemin reste confiné au dossier marque/ du stockage privé.
        foreach (['documents/x.pdf', 'marque/../.env', '/etc/passwd', 'marque/a b.pdf'] as $chemin) {
            $this->withToken($token)->postJson($url, $this->fichier(['fichier' => $chemin]))->assertStatus(422)->assertJsonPath('message', 'Emplacement de fichier invalide.');
        }
        $this->withToken($token)->postJson($url, $this->fichier(['octets' => 40 * 1024 * 1024]))->assertStatus(422)->assertJsonPath('message', 'Le fichier ne doit pas dépasser 30 Mo.');
        $this->assertSame(0, CommunicationFichier::count());
    }

    public function test_un_gabarit_canva_peut_etre_un_simple_lien(): void
    {
        $token = $this->tokenFor($this->com());
        $r = $this->withToken($token)->postJson('/api/admin/communication/fichiers', ['titre' => 'Gabarit post événement', 'categorie' => 'gabarits', 'lien' => 'https://www.canva.com/design/abc/view'])->assertCreated();
        $this->assertFalse($r->json('fichier.a_fichier'));
        $this->assertSame('https://www.canva.com/design/abc/view', $r->json('fichier.lien'));
        $id = $r->json('fichier.id');
        $this->withToken($token)->getJson("/api/admin/communication/fichiers/{$id}/acces")->assertJsonPath('fichier.lien', 'https://www.canva.com/design/abc/view')->assertJsonPath('fichier.chemin', null);

        // Un fichier remplace le lien ; repasser à un lien rend l'ancien fichier à supprimer.
        $this->withToken($token)->putJson("/api/admin/communication/fichiers/{$id}", $this->fichier(['categorie' => 'gabarits']))->assertOk()->assertJsonPath('fichier.lien', null);
        $m = $this->withToken($token)->putJson("/api/admin/communication/fichiers/{$id}", ['titre' => 'G', 'categorie' => 'gabarits', 'lien' => 'https://www.canva.com/design/def/view'])->assertOk();
        $this->assertSame('marque/2026/11/charte-ab12cd.pdf', $m->json('ancien_fichier'));
        $this->assertNull(CommunicationFichier::find($id)->fichier_nom);
    }

    public function test_acces_au_fichier_et_compteur_de_telechargements(): void
    {
        $token = $this->tokenFor($this->com());
        $id = $this->withToken($token)->postJson('/api/admin/communication/fichiers', $this->fichier())->json('fichier.id');

        $this->withToken($token)->getJson("/api/admin/communication/fichiers/{$id}/acces")->assertOk()
            ->assertJsonPath('fichier.chemin', 'marque/2026/11/charte-ab12cd.pdf')
            ->assertJsonPath('fichier.nom', 'Charte REJCC.pdf')
            ->assertJsonPath('fichier.mime', 'application/pdf');
        $this->assertSame(0, CommunicationFichier::find($id)->telechargements);
        $this->withToken($token)->getJson("/api/admin/communication/fichiers/{$id}/acces?action=telechargement")->assertOk();
        $this->assertSame(1, CommunicationFichier::find($id)->telechargements);
        $this->withToken($token)->getJson('/api/admin/communication/fichiers/999/acces')->assertNotFound();

        $autre = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['documents']]));
        $this->withToken($autre)->getJson("/api/admin/communication/fichiers/{$id}/acces")->assertStatus(403);
    }

    public function test_un_compte_de_test_ne_modifie_que_ses_propres_documents(): void
    {
        $vrai = CommunicationFichier::create($this->fichier() + ['auteur_id' => $this->com()->id]);
        $testeur = $this->tokenFor($this->testeurCom());

        $liste = $this->withToken($testeur)->getJson('/api/admin/communication/fichiers')->assertOk();
        $this->assertFalse($liste->json('fichiers.0.modifiable'));
        $this->withToken($testeur)->putJson("/api/admin/communication/fichiers/{$vrai->id}", $this->fichier(['titre' => 'Piraté']))->assertStatus(403);
        $this->withToken($testeur)->deleteJson("/api/admin/communication/fichiers/{$vrai->id}")->assertStatus(403);

        $sien = $this->withToken($testeur)->postJson('/api/admin/communication/fichiers', $this->fichier(['fichier' => 'marque/test.pdf']))->assertCreated()->json('fichier.id');
        $this->withToken($testeur)->deleteJson("/api/admin/communication/fichiers/{$sien}")->assertOk();
    }

    // ── Contacts médias ──────────────────────────────────────────────────

    public function test_crud_recherche_filtre_et_export_des_contacts_medias(): void
    {
        $token = $this->tokenFor($this->com());
        $url = '/api/admin/communication/contacts-medias';

        $id = $this->withToken($token)->postJson($url, [
            'nom' => 'Aya Kouassi', 'media' => 'Radio Notre-Dame', 'type' => 'radio', 'fonction' => 'Journaliste',
            'email' => 'aya@exemple.ci', 'telephone' => '+225 07 00 00 00 00', 'ville' => 'Abidjan', 'notes' => 'Émission du samedi', 'dernier_contact' => '2026-11-02',
        ])->assertCreated()->assertJsonPath('contact.type_label', 'Radio')->assertJsonPath('contact.dernier_contact', '2026-11-02')->json('contact.id');
        $this->withToken($token)->postJson($url, ['nom' => 'Koffi Yao', 'media' => 'Fraternité Matin', 'type' => 'presse', 'ville' => 'Bouaké'])->assertCreated();

        $this->assertCount(2, $this->withToken($token)->getJson($url)->json('contacts'));
        $this->assertSame(['Aya Kouassi'], collect($this->withToken($token)->getJson($url.'?type=radio')->json('contacts'))->pluck('nom')->all());
        $this->assertSame(['Koffi Yao'], collect($this->withToken($token)->getJson($url.'?q=bouak')->json('contacts'))->pluck('nom')->all());
        $this->assertSame(1, $this->withToken($token)->getJson($url)->json('compteurs.presse'));

        $this->withToken($token)->putJson("{$url}/{$id}", ['nom' => 'Aya Kouassi', 'type' => 'tv', 'media' => 'RTI', 'notes' => ''])->assertOk()->assertJsonPath('contact.type_label', 'Télévision')->assertJsonPath('contact.notes', null);

        $export = $this->withToken($token)->getJson('/api/admin/export/contacts-medias')->assertOk();
        $this->assertSame('Média ou organisation', $export->json('columns.1'));
        $this->assertCount(2, $export->json('rows'));
        $this->assertCount(1, $this->withToken($token)->getJson('/api/admin/export/contacts-medias?type=presse')->json('rows'));

        $this->withToken($token)->deleteJson("{$url}/{$id}")->assertOk();
        $this->assertSame(1, ContactMedia::count());
    }

    public function test_validation_des_contacts_medias(): void
    {
        $token = $this->tokenFor($this->com());
        $url = '/api/admin/communication/contacts-medias';

        $this->withToken($token)->postJson($url, ['type' => 'radio'])->assertStatus(422)->assertJsonPath('message', 'Indiquez le nom du contact.');
        $this->withToken($token)->postJson($url, ['nom' => 'X', 'type' => 'journal'])->assertStatus(422)->assertJsonPath('message', 'Type de contact inconnu.');
        $this->withToken($token)->postJson($url, ['nom' => 'X', 'type' => 'web', 'email' => 'pas-un-email'])->assertStatus(422)->assertJsonPath('message', "L'adresse e-mail n'est pas valide.");
        $this->withToken($token)->postJson($url, ['nom' => 'X', 'type' => 'web', 'telephone' => 'appeler'])->assertStatus(422);
        $this->withToken($token)->postJson($url, ['nom' => 'X', 'type' => 'web', 'dernier_contact' => now()->addDays(3)->toDateString()])->assertStatus(422)->assertJsonPath('message', 'La date du dernier contact ne peut pas être dans le futur.');
        $this->assertSame(0, ContactMedia::count());
    }

    public function test_un_compte_de_test_ne_voit_pas_les_vrais_contacts_medias(): void
    {
        $vrai = ContactMedia::create(['nom' => 'Journaliste réel', 'type' => 'presse', 'auteur_id' => $this->com()->id]);
        $testeur = $this->tokenFor($this->testeurCom());
        $url = '/api/admin/communication/contacts-medias';

        $this->assertCount(0, $this->withToken($testeur)->getJson($url)->json('contacts'));
        $this->withToken($testeur)->putJson("{$url}/{$vrai->id}", ['nom' => 'X', 'type' => 'web'])->assertNotFound();
        $this->withToken($testeur)->deleteJson("{$url}/{$vrai->id}")->assertNotFound();
        $this->withToken($testeur)->postJson($url, ['nom' => 'Contact de test', 'type' => 'web'])->assertCreated();
        $this->assertSame(['Contact de test'], collect($this->withToken($testeur)->getJson($url)->json('contacts'))->pluck('nom')->all());
        $this->assertSame([['Contact de test', null, 'Média web', null, null, null, null, null, null]], $this->withToken($testeur)->getJson('/api/admin/export/contacts-medias')->json('rows'));
    }

    // ── Textes prêts à copier ────────────────────────────────────────────

    public function test_les_textes_par_defaut_sont_installes_et_respectent_la_marque(): void
    {
        $defauts = OutilsCommunication::textesParDefaut();
        $this->assertSame(count($defauts), CommunicationTexte::whereNotNull('cle')->count());
        foreach (['nouvel-evenement', 'rappel-veille', 'compte-rendu', 'portrait-cathoimpact', 'formation', 'offre-emploi', 'bureau', 'partenaire', 'fete-liturgique', 'campagne-adhesion'] as $type) {
            $this->assertArrayHasKey('message-'.$type, $defauts);
        }
        $tout = implode("\n", array_column($defauts, 2));
        $this->assertStringContainsString('Réseau Entrepreneurial des Jeunes Chrétiens Catholiques', $tout);
        $this->assertStringNotContainsString('Jeunes Catholiques', $tout);
        $this->assertStringContainsString("Ensemble pour l'excellence", $tout);
        $this->assertStringNotContainsStringIgnoringCase(' tu ', $tout);
        // Les bios respectent leur limite de caractères.
        foreach ($defauts as $cle => [, , $contenu, $limite]) {
            if ($limite) {
                $this->assertLessThanOrEqual($limite, mb_strlen($contenu), $cle);
            }
        }

        // Idempotent : rien n'est ajouté deux fois, un texte modifié n'est pas écrasé.
        CommunicationTexte::where('cle', 'signature-email')->update(['contenu' => 'Ma signature']);
        $this->assertSame(0, OutilsCommunication::installerTextesParDefaut());
        $this->assertSame('Ma signature', CommunicationTexte::where('cle', 'signature-email')->value('contenu'));
    }

    public function test_crud_et_restauration_des_textes(): void
    {
        $token = $this->tokenFor($this->com());
        $url = '/api/admin/communication/textes';

        $r = $this->withToken($token)->getJson($url)->assertOk();
        $this->assertSame(0, $r->json('defauts_manquants'));
        $this->assertSame(10, $r->json('compteurs.messages'));
        $this->assertTrue($r->json('textes.0.par_defaut'));

        $id = $this->withToken($token)->postJson($url, ['categorie' => 'messages', 'titre' => 'Journée portes ouvertes', 'contenu' => 'Rejoignez-nous le [date].', 'limite' => 280])
            ->assertCreated()->assertJsonPath('texte.par_defaut', false)->assertJsonPath('texte.limite', 280)->json('texte.id');
        $this->assertCount(1, $this->withToken($token)->getJson($url.'?q=portes ouvertes')->json('textes'));
        $this->withToken($token)->putJson("{$url}/{$id}", ['categorie' => 'messages', 'titre' => 'Portes ouvertes', 'contenu' => 'Nouveau texte'])->assertOk()->assertJsonPath('texte.limite', null);
        $this->withToken($token)->deleteJson("{$url}/{$id}")->assertOk();

        $this->withToken($token)->postJson($url, ['categorie' => 'messages', 'titre' => 'Vide', 'contenu' => ''])->assertStatus(422)->assertJsonPath('message', 'Le texte est vide.');
        $this->withToken($token)->postJson($url, ['categorie' => 'blog', 'titre' => 'X', 'contenu' => 'Y'])->assertStatus(422)->assertJsonPath('message', 'Catégorie inconnue.');

        // Un texte par défaut supprimé se restaure.
        $defaut = CommunicationTexte::where('cle', 'bio-tiktok')->value('id');
        $this->withToken($token)->deleteJson("{$url}/{$defaut}")->assertOk();
        $this->assertSame(1, $this->withToken($token)->getJson($url)->json('defauts_manquants'));
        $this->withToken($token)->postJson($url.'/restaurer')->assertOk()->assertJsonPath('ajoutes', 1);
        $this->assertTrue(CommunicationTexte::where('cle', 'bio-tiktok')->exists());
    }

    public function test_un_compte_de_test_ne_modifie_pas_les_textes_de_la_com(): void
    {
        $testeur = $this->tokenFor($this->testeurCom());
        $defaut = CommunicationTexte::where('cle', 'signature-email')->value('id');
        $url = '/api/admin/communication/textes';

        $this->withToken($testeur)->putJson("{$url}/{$defaut}", ['categorie' => 'signature', 'titre' => 'X', 'contenu' => 'Y'])->assertStatus(403);
        $this->withToken($testeur)->deleteJson("{$url}/{$defaut}")->assertStatus(403);
        $this->withToken($testeur)->postJson($url.'/restaurer')->assertStatus(403);
        $id = $this->withToken($testeur)->postJson($url, ['categorie' => 'messages', 'titre' => 'Essai', 'contenu' => 'Texte'])->assertCreated()->json('texte.id');
        $this->withToken($testeur)->deleteJson("{$url}/{$id}")->assertOk();
    }

    public function test_le_nettoyage_des_comptes_de_test_efface_leurs_outils_de_communication(): void
    {
        $testeur = $this->testeurCom();
        $vrai = $this->com();
        CommunicationFichier::create($this->fichier() + ['auteur_id' => $testeur->id]);
        CommunicationFichier::create($this->fichier(['titre' => 'Vraie charte']) + ['auteur_id' => $vrai->id]);
        ContactMedia::create(['nom' => 'Contact testeur', 'type' => 'web', 'auteur_id' => $testeur->id]);
        ContactMedia::create(['nom' => 'Vrai contact', 'type' => 'web', 'auteur_id' => $vrai->id]);
        CommunicationTexte::create(['categorie' => 'messages', 'titre' => 'Essai', 'contenu' => 'x', 'auteur_id' => $testeur->id]);
        $textes = CommunicationTexte::count();

        $this->artisan('test:nettoyer', ['--force' => true])->assertSuccessful();

        $this->assertSame(['Vraie charte'], CommunicationFichier::pluck('titre')->all());
        $this->assertSame(['Vrai contact'], ContactMedia::pluck('nom')->all());
        $this->assertSame($textes - 1, CommunicationTexte::count());
    }
}
