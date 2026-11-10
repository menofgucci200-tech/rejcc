<?php

namespace Tests\Feature\Api;

use App\Mail\RappelPublications;
use App\Mail\RecapPublicationsNonPubliees;
use App\Models\ApiToken;
use App\Models\CathoImpactCandidature;
use App\Models\CommunicationPublication;
use App\Models\CommunicationTour;
use App\Models\User;
use App\Support\CalendrierEditorial;
use App\Support\CalendrierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Calendrier éditorial de la Communication : publications, plan de diffusion,
 * tours de rôle, import du fichier Excel (CSV), « Marquer publié », rappels
 * par e-mail et bilan mensuel.
 */
class CalendrierEditorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 11, 10, 10, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function compteCom(array $extra = []): User
    {
        return User::factory()->create(['role' => 'admin', 'bureau_departement' => 'communication', 'is_active' => true,
            'permissions' => ['actualites', 'evenements', 'contenu', 'salons', 'communication'], ...$extra]);
    }

    private function com(?User $u = null)
    {
        return $this->withToken($this->tokenFor($u ?? $this->compteCom()));
    }

    /** Passage de la commande planifiée ; les e-mails partent après la « réponse » (terminate). */
    private function rappels(): void
    {
        $this->artisan('communication:rappels')->assertSuccessful();
        $this->app->terminate();
        // Les envois déjà faits ne doivent pas repartir au passage suivant.
        (fn () => $this->terminatingCallbacks = [])->call($this->app);
    }

    private function publication(array $extra = []): CommunicationPublication
    {
        return CommunicationPublication::create(array_merge(['date' => '2026-11-10', 'titre' => 'Annonce du gala', 'type' => 'evenement', 'canaux' => ['facebook'], 'statut' => 'prevu'], $extra));
    }

    // ── Accès et CRUD ───────────────────────────────────────────────────

    public function test_sans_la_rubrique_communication_tout_est_refuse(): void
    {
        $juridique = User::factory()->create(['role' => 'admin', 'is_active' => true, 'bureau_departement' => 'juridique', 'permissions' => ['contenu', 'documents']]);
        $t = $this->withToken($this->tokenFor($juridique));
        $t->getJson('/api/admin/communication/calendrier')->assertForbidden();
        $t->postJson('/api/admin/communication/publications', ['date' => '2026-11-12', 'titre' => 'Test'])->assertForbidden();
        $t->getJson('/api/admin/communication/bilan')->assertForbidden();
        $t->postJson('/api/admin/communication/tours/proposer', [])->assertForbidden();
        $this->withoutToken()->getJson('/api/admin/communication/calendrier')->assertUnauthorized();
    }

    public function test_creer_une_publication_pre_coche_les_canaux_du_plan_de_diffusion(): void
    {
        $r = $this->com()->postJson('/api/admin/communication/publications', [
            'date' => '2026-11-12', 'heure' => '18:30', 'titre' => 'Formation : bâtir son business plan', 'type' => 'formation', 'format' => 'Visuel',
        ])->assertCreated();
        $r->assertJsonPath('publication.canaux', ['whatsapp', 'site', 'email', 'annonces'])
            ->assertJsonPath('publication.statut', 'prevu')->assertJsonPath('publication.type_label', 'Formation');

        // Canaux choisis à la main : respectés.
        $this->com()->postJson('/api/admin/communication/publications', ['date' => '2026-11-13', 'titre' => 'Portrait', 'type' => 'portrait', 'canaux' => ['linkedin']])
            ->assertCreated()->assertJsonPath('publication.canaux', ['linkedin']);

        $this->assertSame(['youtube', 'tiktok', 'linkedin', 'facebook', 'instagram', 'site'], CalendrierEditorial::canauxParDefaut('portrait'));
        $this->assertCount(9, CalendrierEditorial::canauxParDefaut('evenement'));
        $this->assertSame(['email', 'annonces'], CalendrierEditorial::canauxParDefaut('compte_rendu'));
    }

    public function test_validation_des_publications(): void
    {
        $t = $this->com();
        $t->postJson('/api/admin/communication/publications', [])->assertUnprocessable()->assertJsonValidationErrors(['date', 'titre']);
        $t->postJson('/api/admin/communication/publications', ['date' => '2026-11-12', 'titre' => 'Ok', 'heure' => '25:00', 'type' => 'inconnu', 'canaux' => ['myspace'], 'liens' => ['pas-un-lien']])
            ->assertUnprocessable()->assertJsonValidationErrors(['heure', 'type', 'canaux.0', 'liens.0']);
        $autre = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique']);
        $t->postJson('/api/admin/communication/publications', ['date' => '2026-11-12', 'titre' => 'Ok', 'responsable_id' => $autre->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['responsable_id']);
    }

    public function test_modifier_supprimer_et_demande_id(): void
    {
        $t = $this->com();
        $id = $t->postJson('/api/admin/communication/publications', ['date' => '2026-11-12', 'titre' => 'Demande du Juridique', 'demande_id' => 42])
            ->assertCreated()->assertJsonPath('publication.demande_id', 42)->json('publication.id');
        $t->putJson("/api/admin/communication/publications/{$id}", ['titre' => 'Nouveau titre', 'statut' => 'pret', 'responsable' => 'Invité extérieur'])
            ->assertOk()->assertJsonPath('publication.titre', 'Nouveau titre')->assertJsonPath('publication.statut', 'pret')
            ->assertJsonPath('publication.responsable_effectif.nom', 'Invité extérieur');
        $t->getJson("/api/admin/communication/publications/{$id}")->assertOk()->assertJsonPath('publication.demande_id', 42);
        $t->deleteJson("/api/admin/communication/publications/{$id}")->assertOk();
        $this->assertDatabaseCount('communication_publications', 0);
    }

    public function test_marquer_publie_en_un_clic_avec_le_lien(): void
    {
        $moi = $this->compteCom();
        $p = $this->publication();
        $this->com($moi)->postJson("/api/admin/communication/publications/{$p->id}/publie", ['lien' => 'https://www.facebook.com/rejcc/posts/1'])
            ->assertOk()->assertJsonPath('publication.statut', 'publie')->assertJsonPath('publication.liens', ['https://www.facebook.com/rejcc/posts/1']);
        $p->refresh();
        $this->assertSame($moi->id, $p->publie_par);
        $this->assertNotNull($p->publie_at);
        // Sans lien aussi, et un lien invalide est refusé.
        $p2 = $this->publication();
        $this->com()->postJson("/api/admin/communication/publications/{$p2->id}/publie")->assertOk();
        $this->com()->postJson("/api/admin/communication/publications/{$p2->id}/publie", ['lien' => 'facebook'])->assertUnprocessable();
    }

    public function test_le_calendrier_du_mois_filtre_et_montre_les_retards(): void
    {
        $awa = $this->compteCom(['prenom' => 'Awa', 'nom' => 'Koné']);
        $this->publication(['date' => '2026-11-05', 'titre' => 'Oubliée', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-06', 'titre' => 'Annulée', 'statut' => 'annule']);
        $this->publication(['date' => '2026-11-07', 'titre' => 'Publiée', 'statut' => 'publie']);
        $this->publication(['date' => '2026-11-20', 'titre' => 'À venir', 'canaux' => ['tiktok']]);
        $this->publication(['date' => '2026-12-01', 'titre' => 'Mois suivant']);

        $r = $this->com()->getJson('/api/admin/communication/calendrier?mois=2026-11')->assertOk();
        $this->assertCount(4, $r->json('publications'));
        $this->assertSame(['Oubliée'], collect($r->json('retards'))->pluck('titre')->all());
        $this->assertTrue(collect($r->json('publications'))->firstWhere('titre', 'Oubliée')['en_retard']);
        $this->assertSame('2026-11-10', $r->json('aujourdhui'));
        $r->assertJsonPath('plan.formation', ['whatsapp', 'site', 'email', 'annonces']);

        $this->com()->getJson('/api/admin/communication/calendrier?mois=2026-11&canal=tiktok')->assertJsonCount(1, 'publications');
        $this->com()->getJson('/api/admin/communication/calendrier?mois=2026-11&statut=retard')->assertJsonCount(1, 'publications');
        $this->com()->getJson("/api/admin/communication/calendrier?mois=2026-11&responsable={$awa->id}")->assertJsonCount(1, 'publications');
    }

    // ── Tours de rôle ───────────────────────────────────────────────────

    public function test_les_blocs_de_novembre_2026_suivent_le_modele_de_la_com(): void
    {
        // Le 1er novembre 2026 est un dimanche ; le 30, un lundi.
        $this->assertSame([
            ['du' => '2026-11-01', 'au' => '2026-11-04'], // dimanche → mercredi
            ['du' => '2026-11-05', 'au' => '2026-11-07'], // jeudi → samedi
            ['du' => '2026-11-08', 'au' => '2026-11-11'],
            ['du' => '2026-11-12', 'au' => '2026-11-14'],
            ['du' => '2026-11-15', 'au' => '2026-11-18'],
            ['du' => '2026-11-19', 'au' => '2026-11-21'],
            ['du' => '2026-11-22', 'au' => '2026-11-25'],
            ['du' => '2026-11-26', 'au' => '2026-11-28'],
            ['du' => '2026-11-29', 'au' => '2026-11-30'],
        ], CalendrierEditorial::blocsDuMois(2026, 11));

        // Février 2027 commence un lundi : lundi → mercredi en premier.
        $this->assertSame(['du' => '2027-02-01', 'au' => '2027-02-03'], CalendrierEditorial::blocsDuMois(2027, 2)[0]);
        // Mars 2026 finit un mardi après un bloc dim. 29 → mar. 31 ; aucun bloc d'un seul jour.
        foreach ([[2026, 1], [2026, 5], [2026, 8], [2027, 2]] as [$a, $m]) {
            $blocs = CalendrierEditorial::blocsDuMois($a, $m);
            $this->assertSame(sprintf('%04d-%02d-01', $a, $m), $blocs[0]['du']);
            $this->assertSame(Carbon::create($a, $m)->endOfMonth()->toDateString(), end($blocs)['au']);
            foreach ($blocs as $b) {
                $this->assertNotSame($b['du'], $b['au'], "Bloc d'un seul jour en {$a}-{$m}");
            }
        }
    }

    public function test_generer_puis_enregistrer_les_tours_du_mois(): void
    {
        $awa = $this->compteCom(['prenom' => 'Awa', 'nom' => 'Koné']);
        $yao = $this->compteCom(['prenom' => 'Yao', 'nom' => 'Kouassi']);
        $t = $this->com($awa);

        $r = $t->postJson('/api/admin/communication/tours/proposer', ['mois' => '2026-11', 'membres' => [['id' => $awa->id], ['id' => $yao->id], ['nom' => 'Marie (bénévole)']]])->assertOk();
        $tours = $r->json('tours');
        $this->assertCount(9, $tours);
        $this->assertSame([$awa->id, $yao->id, null, $awa->id], array_column(array_slice($tours, 0, 4), 'responsable_id'));
        $this->assertSame('Marie (bénévole)', $tours[2]['nom']);

        // Ajustement avant l'enregistrement : le dernier bloc revient à Yao.
        $tours[8]['responsable_id'] = $yao->id;
        $tours[8]['responsable'] = null;
        $t->postJson('/api/admin/communication/tours/lot', ['mois' => '2026-11', 'tours' => $tours])->assertOk()->assertJsonCount(9, 'tours');
        $this->assertSame(9, CommunicationTour::count());
        // Ré-enregistrer remplace les tours du mois, sans doublon.
        $t->postJson('/api/admin/communication/tours/lot', ['mois' => '2026-11', 'tours' => array_slice($tours, 0, 2)])->assertOk();
        $this->assertSame(2, CommunicationTour::count());

        $t->postJson('/api/admin/communication/tours/proposer', ['mois' => '2026-11', 'membres' => []])->assertUnprocessable();
        $t->postJson('/api/admin/communication/tours/lot', ['mois' => '2026-11', 'tours' => [['du' => '2026-12-02', 'au' => '2026-12-03', 'responsable_id' => $awa->id]]])->assertUnprocessable();
    }

    public function test_le_tour_en_cours_et_le_responsable_par_defaut(): void
    {
        $awa = $this->compteCom(['prenom' => 'Awa', 'nom' => 'Koné']);
        $t = $this->com($awa);
        $t->postJson('/api/admin/communication/tours', ['du' => '2026-11-08', 'au' => '2026-11-11', 'responsable_id' => $awa->id])->assertCreated();
        $t->postJson('/api/admin/communication/tours', ['du' => '2026-11-12', 'au' => '2026-11-14'])->assertUnprocessable();

        $t->getJson('/api/admin/communication/calendrier?mois=2026-11')->assertJsonPath('tour_en_cours.responsable', 'Awa Koné');
        // Sans responsable : celui du tour.
        $t->postJson('/api/admin/communication/publications', ['date' => '2026-11-09', 'titre' => 'Sans responsable'])
            ->assertCreated()->assertJsonPath('publication.responsable_id', $awa->id);
        // Publication créée avant le tour (import…) : le tour s'applique à l'affichage.
        $p = $this->publication(['date' => '2026-11-11']);
        $this->assertSame(['id' => $awa->id, 'nom' => 'Awa Koné', 'du_tour' => true], CalendrierEditorial::responsable($p->fresh()));
    }

    // ── Import ──────────────────────────────────────────────────────────

    public function test_import_csv_point_virgule_avec_bom_et_accents(): void
    {
        $awa = $this->compteCom(['prenom' => 'Awa', 'nom' => 'Koné']);
        $csv = "\xEF\xBB\xBFDate;Sujet;Style de vidéo;Canal;Responsable\r\n"
            ."02/11/2026;Fête de la Toussaint : témoignage;Vidéo courte;Facebook + Instagram;awa kone\r\n"
            ."03/11/2026;Offre d'emploi : comptable;Visuel;WhatsApp, E-mail;Jean-Éric Bédié\r\n"
            ."31/11/2026;Date impossible;Texte;Facebook;Awa\r\n"
            .";Sans date;Texte;Myspace;\r\n"
            ."\r\n";
        $t = $this->com($awa);
        $r = $t->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode($csv), 'nom' => 'calendrier.csv', 'apercu' => true])->assertOk();
        $r->assertJsonPath('valides', 2)->assertJsonPath('erreurs', 2);
        $lignes = $r->json('lignes');
        $this->assertSame('Fête de la Toussaint : témoignage', $lignes[0]['titre']);
        $this->assertSame(['facebook', 'instagram'], $lignes[0]['canaux']);
        $this->assertSame($awa->id, $lignes[0]['responsable_id']);
        $this->assertSame('Jean-Éric Bédié', $lignes[1]['responsable']);
        $this->assertSame(4, $lignes[2]['ligne']);
        $this->assertStringContainsString('31/11/2026', $lignes[2]['erreurs'][0]);
        $this->assertSame(['date manquante', 'canal « Myspace » inconnu'], $lignes[3]['erreurs']);
        $this->assertSame(0, CommunicationPublication::count(), "L'aperçu n'enregistre rien.");

        $t->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode($csv), 'nom' => 'calendrier.csv'])
            ->assertOk()->assertJsonPath('importees', 2)->assertJsonCount(2, 'erreurs');
        $this->assertSame(['Fête de la Toussaint : témoignage', 'Offre d\'emploi : comptable'], CommunicationPublication::orderBy('date')->pluck('titre')->all());
        $this->assertSame('Vidéo courte', CommunicationPublication::first()->format);
    }

    public function test_import_csv_virgule_guillemets_et_windows_1252(): void
    {
        $csv = mb_convert_encoding("date,sujet,style,canal,responsable,heure\n2026-11-04,\"Rappel, formation de jeudi\",Story,Tous,,18h30\n", 'Windows-1252', 'UTF-8');
        $this->com()->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode($csv), 'nom' => 'export.csv'])
            ->assertOk()->assertJsonPath('importees', 1);
        $p = CommunicationPublication::firstOrFail();
        $this->assertSame('Rappel, formation de jeudi', $p->titre);
        $this->assertSame('18:30', $p->heure);
        $this->assertCount(9, $p->canaux);

        // En-tête non reconnu ou format refusé.
        $this->com()->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode("a;b\n1;2\n"), 'nom' => 'x.csv'])->assertUnprocessable();
        $this->com()->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode('x'), 'nom' => 'x.pdf'])->assertUnprocessable();
    }

    public function test_import_d_un_classeur_xlsx(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Novembre" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="worksheet" Target="worksheets/feuille.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Date</t></si><si><t>Sujet</t></si><si><t>Canal</t></si><si><r><t>Messe </t></r><r><t>de l\'Avent</t></r></si><si><t>YouTube</t></si></sst>');
        $zip->addFromString('xl/worksheets/feuille.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .'<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row>'
            .'<row r="2"><c r="A2"><v>46359</v></c><c r="B2" t="s"><v>3</v></c><c r="C2" t="s"><v>4</v></c></row>'
            .'</sheetData></worksheet>');
        $zip->close();
        $contenu = file_get_contents($tmp);
        unlink($tmp);

        $this->com()->postJson('/api/admin/communication/publications/import', ['fichier' => base64_encode($contenu), 'nom' => 'Calendrier.xlsx'])
            ->assertOk()->assertJsonPath('importees', 1);
        $p = CommunicationPublication::firstOrFail();
        $this->assertSame("Messe de l'Avent", $p->titre);
        $this->assertSame('2026-12-03', $p->date->toDateString()); // numéro de série Excel 46359
        $this->assertSame(['youtube'], $p->canaux);
    }

    public function test_modele_export_et_duplication(): void
    {
        $this->assertStringStartsWith("\xEF\xBB\xBFdate;heure;sujet;style;canal;responsable", CalendrierImport::modele());
        // Le modèle se réimporte tel quel.
        $analyse = CalendrierImport::analyser(CalendrierImport::lignesBrutes(CalendrierImport::modele(), 'modele.csv'), collect());
        $this->assertCount(3, $analyse['lignes']);
        $this->assertSame([], array_merge(...array_column($analyse['lignes'], 'erreurs')));

        $this->publication(['date' => '2026-10-31', 'titre' => 'Halloween ? Non : Toussaint']);
        $this->publication(['date' => '2026-10-15', 'titre' => 'Mi-octobre', 'statut' => 'publie', 'liens' => ['https://x.test/1']]);
        $this->publication(['date' => '2026-10-16', 'titre' => 'Annulée', 'statut' => 'annule']);
        $this->com()->postJson('/api/admin/communication/calendrier/dupliquer', ['mois' => '2026-11'])->assertOk()->assertJsonPath('copiees', 1);
        $copie = CommunicationPublication::where('date', '2026-11-15')->firstOrFail();
        $this->assertSame(['prevu', null], [$copie->statut, $copie->liens]);

        $r = $this->com()->getJson('/api/admin/communication/calendrier/export?mois=2026-10')->assertOk()->assertJsonPath('nom', 'calendrier-editorial-2026-10.csv');
        $this->assertStringContainsString('15/10/2026;;Mi-octobre', $r->json('csv'));
        // L'export se réimporte (libellés des canaux compris).
        $this->publication(['date' => '2026-10-20', 'titre' => 'Tous canaux', 'canaux' => array_keys(CalendrierEditorial::CANAUX)]);
        $export = $this->com()->getJson('/api/admin/communication/calendrier/export?mois=2026-10')->json('csv');
        $analyse = CalendrierImport::analyser(CalendrierImport::lignesBrutes($export, 'export.csv'), collect());
        $this->assertSame([], array_merge(...array_column($analyse['lignes'], 'erreurs')));
        $this->assertCount(9, collect($analyse['lignes'])->firstWhere('titre', 'Tous canaux')['canaux']);
    }

    // ── Rappels ─────────────────────────────────────────────────────────

    public function test_rappel_de_la_veille_a_18h_au_responsable_sans_doublon(): void
    {
        Mail::fake();
        $awa = $this->compteCom(['prenom' => 'Awa', 'email' => 'awa@rejcc.site']);
        $yao = $this->compteCom(['prenom' => 'Yao', 'email' => 'yao@rejcc.site']);
        $this->publication(['date' => '2026-11-11', 'titre' => 'Demain', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-11', 'titre' => 'Demain aussi', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-11', 'titre' => 'Déjà publiée', 'responsable_id' => $yao->id, 'statut' => 'publie']);
        $this->publication(['date' => '2026-11-12', 'titre' => 'Après-demain', 'responsable_id' => $yao->id]);

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 17, 50));
        $this->rappels();
        Mail::assertNothingSent();

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 18, 0));
        $this->rappels();
        Mail::assertSent(RappelPublications::class, 1);
        Mail::assertSent(RappelPublications::class, fn ($m) => $m->hasTo('awa@rejcc.site') && $m->moment === 'veille' && $m->publications->count() === 2);

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 18, 10));
        $this->rappels();
        Mail::assertSent(RappelPublications::class, 1); // pas de doublon
    }

    public function test_un_compte_de_test_ne_touche_qu_a_ses_propres_publications_et_tours(): void
    {
        $testeur = $this->compteCom(['bureau_departement' => null, 'departement' => 'Communication']);
        $vraie = $this->publication(['titre' => 'Publication de la Com']);
        $tourReel = CommunicationTour::create(['du' => '2026-11-09', 'au' => '2026-11-11', 'responsable' => 'Awa']);

        $this->com($testeur)->putJson("/api/admin/communication/publications/{$vraie->id}", ['titre' => 'Piratée'])->assertForbidden();
        $this->com($testeur)->deleteJson("/api/admin/communication/publications/{$vraie->id}")->assertForbidden();
        $this->com($testeur)->postJson("/api/admin/communication/publications/{$vraie->id}/publie")->assertForbidden();
        $this->com($testeur)->deleteJson("/api/admin/communication/tours/{$tourReel->id}")->assertForbidden();
        $this->assertSame('Publication de la Com', $vraie->fresh()->titre);

        // Ses propres publications restent modifiables ; remplacer les tours du mois épargne ceux de la Com.
        $id = $this->com($testeur)->postJson('/api/admin/communication/publications', ['date' => '2026-11-20', 'titre' => '[TEST] Essai'])->assertCreated()->json('publication.id');
        $this->com($testeur)->putJson("/api/admin/communication/publications/{$id}", ['titre' => '[TEST] Essai 2'])->assertOk();
        $this->com($testeur)->postJson('/api/admin/communication/tours/lot', ['mois' => '2026-11', 'tours' => [['du' => '2026-11-20', 'au' => '2026-11-21', 'responsable' => 'Testeur']]])->assertOk();
        $this->assertNotNull($tourReel->fresh());
    }

    public function test_les_publications_des_comptes_de_test_ne_declenchent_aucun_rappel(): void
    {
        Mail::fake();
        $awa = $this->compteCom(['prenom' => 'Awa', 'email' => 'awa@rejcc.site']);
        $testeur = $this->compteCom(['bureau_departement' => null, 'departement' => 'Communication']);
        $this->publication(['titre' => '[TEST] Essai', 'responsable_id' => $awa->id, 'cree_par' => $testeur->id]);
        $this->publication(['date' => '2026-11-09', 'titre' => '[TEST] Hier', 'responsable_id' => $awa->id, 'cree_par' => $testeur->id]);

        $this->rappels();
        Mail::assertNothingSent();
    }

    public function test_l_export_neutralise_les_formules_et_l_import_les_retablit(): void
    {
        $this->publication(['titre' => '=HYPERLINK("https://exemple.com")', 'notes' => '+225 07 00 00 00']);
        $csv = $this->com()->getJson('/api/admin/communication/calendrier/export?mois=2026-11')->assertOk()->json('csv');

        $this->assertStringContainsString('"\'=HYPERLINK(""https://exemple.com"")"', $csv);
        $this->assertStringContainsString(';+225 07 00 00 00;', $csv);
        $analyse = CalendrierImport::analyser(CalendrierImport::lignesBrutes($csv, 'export.csv'), CalendrierEditorial::equipe());
        $this->assertSame('=HYPERLINK("https://exemple.com")', $analyse['lignes'][0]['titre']);
    }

    public function test_rappel_du_jour_a_8h_et_responsable_du_tour(): void
    {
        Mail::fake();
        $awa = $this->compteCom(['prenom' => 'Awa', 'email' => 'awa@rejcc.site']);
        CommunicationTour::create(['du' => '2026-11-08', 'au' => '2026-11-11', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-10', 'titre' => 'Du jour, sans responsable']);
        $this->publication(['date' => '2026-11-10', 'titre' => 'Nom libre', 'responsable' => 'Invité']);

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 7, 55));
        $this->rappels();
        Mail::assertNothingSent();

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 8, 0));
        $this->rappels();
        Mail::assertSent(RappelPublications::class, fn ($m) => $m->hasTo('awa@rejcc.site') && $m->moment === 'jour'
            && $m->publications->pluck('titre')->all() === ['Du jour, sans responsable']);
        Carbon::setTestNow(Carbon::create(2026, 11, 10, 12, 0));
        $this->rappels();
        Mail::assertSent(RappelPublications::class, 1);
    }

    public function test_recap_du_matin_des_publications_non_publiees(): void
    {
        Mail::fake();
        $this->compteCom(['email' => 'com1@rejcc.site']);
        $this->compteCom(['email' => 'com2@rejcc.site']);
        $this->compteCom(['email' => 'inactif@rejcc.site', 'is_active' => false]);
        $this->publication(['date' => '2026-11-09', 'titre' => 'Oubliée hier', 'statut' => 'pret']);
        $this->publication(['date' => '2026-11-09', 'titre' => 'Publiée hier', 'statut' => 'publie']);
        $this->publication(['date' => '2026-11-09', 'titre' => 'Annulée hier', 'statut' => 'annule']);

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 9, 0));
        $this->rappels();
        Mail::assertSent(RecapPublicationsNonPubliees::class, 2);
        Mail::assertSent(RecapPublicationsNonPubliees::class, fn ($m) => $m->hasTo('com1@rejcc.site') && $m->publications->pluck('titre')->all() === ['Oubliée hier']);
        Mail::assertNotSent(RecapPublicationsNonPubliees::class, fn ($m) => $m->hasTo('inactif@rejcc.site'));

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 9, 10));
        $this->rappels();
        Mail::assertSent(RecapPublicationsNonPubliees::class, 2);
    }

    public function test_rien_n_est_envoye_quand_tout_est_publie(): void
    {
        Mail::fake();
        $awa = $this->compteCom(['email' => 'awa@rejcc.site']);
        $this->publication(['date' => '2026-11-09', 'statut' => 'publie', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-10', 'statut' => 'publie', 'responsable_id' => $awa->id]);
        $this->publication(['date' => '2026-11-11', 'statut' => 'annule', 'responsable_id' => $awa->id]);

        Carbon::setTestNow(Carbon::create(2026, 11, 10, 20, 0));
        $this->rappels();
        Mail::assertNothingSent();
    }

    public function test_les_retards_apparaissent_dans_a_traiter(): void
    {
        $this->publication(['date' => '2026-11-08']);
        $r = $this->com()->getJson('/api/admin/a-traiter')->assertOk();
        $this->assertSame(1, collect($r->json('elements'))->firstWhere('cle', 'publications_retard')['nombre']);
    }

    // ── Espace du département et bilan ──────────────────────────────────

    public function test_le_bloc_du_jour_de_l_espace_communication(): void
    {
        $this->publication(['date' => '2026-11-10', 'titre' => 'Aujourd\'hui']);
        $this->publication(['date' => '2026-11-11', 'titre' => 'Demain']);
        $this->publication(['date' => '2026-11-03', 'titre' => 'En retard']);
        $this->com()->getJson('/api/admin/communication/calendrier/aujourdhui')->assertOk()
            ->assertJsonPath('aujourdhui.0.titre', 'Aujourd\'hui')->assertJsonPath('demain.0.titre', 'Demain')
            ->assertJsonPath('retards.0.titre', 'En retard')->assertJsonPath('chiffres.retards', 1);
    }

    public function test_le_bilan_du_mois(): void
    {
        $awa = $this->compteCom(['prenom' => 'Awa', 'nom' => 'Koné']);
        // Awa : une à temps, une en retard de publication, une oubliée.
        $this->publication(['date' => '2026-11-02', 'responsable_id' => $awa->id, 'statut' => 'publie', 'publie_at' => '2026-11-02 19:00', 'canaux' => ['facebook', 'tiktok']]);
        $this->publication(['date' => '2026-11-03', 'responsable_id' => $awa->id, 'statut' => 'publie', 'publie_at' => '2026-11-05 09:00', 'canaux' => ['facebook']]);
        $this->publication(['date' => '2026-11-04', 'responsable_id' => $awa->id, 'type' => 'formation', 'canaux' => ['site']]);
        $this->publication(['date' => '2026-11-04', 'responsable' => 'Marc', 'statut' => 'annule']);
        $this->publication(['date' => '2026-11-25', 'responsable' => 'Marc']);
        CathoImpactCandidature::forceCreate(['profil' => 'entrepreneur', 'nom_complet' => 'A', 'email' => 'a@x.ci', 'whatsapp' => '0700000000', 'entreprise' => 'E', 'fonction' => 'F', 'secteur' => 'S', 'ville' => 'V',
            'parcours' => 'p', 'inspiration' => 'i', 'consentement_texte' => 'c', 'consentement_at' => now(), 'statut' => 'publie', 'statut_at' => now()]);
        User::factory()->create(['role' => 'member', 'created_at' => '2026-11-03']);
        User::factory()->create(['role' => 'member', 'created_at' => '2026-10-03']);

        $r = $this->com()->getJson('/api/admin/communication/bilan?mois=2026-11')->assertOk()
            ->assertJsonPath('libelle', 'Novembre 2026')
            ->assertJsonPath('publications.prevues', 4)->assertJsonPath('publications.publiees', 2)
            ->assertJsonPath('publications.a_temps', 1)->assertJsonPath('publications.non_publiees', 1)
            ->assertJsonPath('publications.a_venir', 1)->assertJsonPath('publications.annulees', 1)
            ->assertJsonPath('publications.taux_regularite', 33)
            ->assertJsonPath('cathoimpact.recues', 1)->assertJsonPath('cathoimpact.publiees', 1)
            ->assertJsonPath('reseau.nouveaux_membres', 1);
        $awaLigne = collect($r->json('publications.par_responsable'))->firstWhere('nom', 'Awa Koné');
        $this->assertSame([3, 2, 1, 1, 33], [$awaLigne['prevues'], $awaLigne['publiees'], $awaLigne['a_temps'], $awaLigne['non_publiees'], $awaLigne['taux_regularite']]);
        $this->assertSame(2, collect($r->json('publications.par_canal'))->firstWhere('canal', 'facebook')['publiees']);
        $this->assertSame(1, collect($r->json('publications.par_type'))->firstWhere('type', 'formation')['non_publiees']);

        $this->com()->getJson('/api/admin/communication/bilan?mois=2026-13')->assertUnprocessable();
    }
}
