<?php

namespace Tests\Feature;

use App\Support\Audience;
use Illuminate\Http\Client\Request as RequeteApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mesure d'audience anonyme (fichier du jour) et liens de suivi /l/{code}.
 */
class AudienceTest extends TestCase
{
    private const NAVIGATEUR = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private const TELEPHONE = 'Mozilla/5.0 (Linux; Android 14; SM-A146B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // Pages d'essai qui ne dépendent pas de l'API.
        Route::middleware('web')->group(function () {
            Route::get('/essai-mesure/{id?}', fn () => response('<html><body>Essai</body></html>'));
            Route::get('/espace-membre/essai-mesure', fn () => response('<html><body>Membre</body></html>'));
            Route::get('/essai-json', fn () => response()->json(['ok' => true]));
        });
    }

    private function jour(): array
    {
        $f = Storage::disk('local')->path('statistiques/visites/'.now()->toDateString().'.json');

        return is_file($f) ? json_decode(file_get_contents($f), true) : [];
    }

    private function visite(string $url, array $entetes = [], string $ip = '10.0.0.1', string $navigateur = self::NAVIGATEUR)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withHeaders(['User-Agent' => $navigateur] + $entetes)->get($url);
    }

    public function test_compte_les_pages_vues_les_visiteurs_les_sources_et_les_appareils(): void
    {
        $this->visite('/essai-mesure', ['Referer' => 'https://www.facebook.com/'])->assertOk();
        $this->visite('/essai-mesure/42', ['Referer' => 'https://rejcc.site/essai-mesure'])->assertOk();
        $this->visite('/essai-mesure?utm_source=WhatsApp', [], '10.0.0.2', self::TELEPHONE)->assertOk();

        $d = $this->jour();
        $this->assertSame(3, $d['pages_vues']);
        $this->assertSame(2, $d['visiteurs']);
        $this->assertSame(['/essai-mesure' => 2, '/essai-mesure/{id}' => 1], $d['pages']);
        $this->assertSame(['facebook.com' => 1, 'whatsapp' => 1], $d['sources']);
        $this->assertSame(['telephone' => 1, 'ordinateur' => 1], $d['appareils']);
        // Ni adresse IP ni navigateur dans le fichier, seulement des empreintes courtes.
        $brut = file_get_contents(Storage::disk('local')->path('statistiques/visites/'.now()->toDateString().'.json'));
        $this->assertStringNotContainsString('10.0.0.1', $brut);
        $this->assertStringNotContainsString('Chrome', $brut);
        $this->assertCount(2, $d['empreintes']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', array_key_first($d['empreintes']));
        // Aucun cookie posé par la mesure (seul le cookie de session du site existe).
        $r = $this->visite('/essai-mesure');
        foreach ($r->headers->getCookies() as $c) {
            $this->assertNotSame('', $c->getName());
            $this->assertStringNotContainsStringIgnoringCase('mesure', $c->getName());
        }
    }

    public function test_robots_prechargements_livewire_et_espace_membre_ignores(): void
    {
        $this->visite('/essai-mesure', [], '10.0.0.3', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->visite('/essai-mesure', [], '10.0.0.3', 'WhatsApp/2.23.20.0 A');
        $this->visite('/essai-mesure', [], '10.0.0.3', '');
        $this->visite('/essai-mesure', ['Sec-Purpose' => 'prefetch']);
        $this->visite('/essai-mesure', ['X-Livewire-Navigate' => '1']);
        $this->visite('/espace-membre/essai-mesure');
        $this->visite('/essai-json');
        $this->visite('/page-qui-n-existe-pas')->assertNotFound();

        $this->assertSame([], $this->jour());
    }

    public function test_navigation_signalee_par_le_navigateur(): void
    {
        $this->withHeaders(['User-Agent' => self::NAVIGATEUR])->post('/mesure', ['chemin' => '/essai-mesure/7'])->assertNoContent();
        // Adresse inconnue ou espace membre : rien.
        $this->withHeaders(['User-Agent' => self::NAVIGATEUR])->post('/mesure', ['chemin' => '/inconnue/totalement'])->assertNoContent();
        $this->withHeaders(['User-Agent' => self::NAVIGATEUR])->post('/mesure', ['chemin' => '/espace-membre/essai-mesure'])->assertNoContent();

        $d = $this->jour();
        $this->assertSame(1, $d['pages_vues']);
        $this->assertSame(['/essai-mesure/{id}' => 1], $d['pages']);
        $this->assertSame([], $d['sources']);
    }

    public function test_normalisation_des_chemins(): void
    {
        $this->assertSame('/', Audience::chemin('/'));
        $this->assertSame('/emplois/{id}', Audience::chemin('/emplois/42?x=1'));
        $this->assertSame('/carte/{code}', Audience::chemin('/carte/AB12CD'));
        $this->assertSame('/evenements/gala-2026-abidjan', Audience::chemin('/Evenements/gala-2026-abidjan/'));
        $this->assertNull(Audience::chemin('/billet/XYZ123'));
        $this->assertNull(Audience::chemin('/reinitialiser-mot-de-passe'));
    }

    public function test_menage_efface_les_empreintes_passees_et_les_vieux_jours(): void
    {
        $dossier = Audience::dossier();
        $hier = now()->subDay()->toDateString();
        $ancien = now()->subDays(Audience::JOURS_CONSERVES + 5)->toDateString();
        file_put_contents("$dossier/$hier.json", json_encode(['jour' => $hier, 'pages_vues' => 4, 'visiteurs' => 2, 'empreintes' => ['abcd' => 1]]));
        file_put_contents("$dossier/$ancien.json", json_encode(['jour' => $ancien, 'pages_vues' => 1]));

        // Première visite du jour : le ménage se fait de lui-même.
        $this->visite('/essai-mesure');

        $this->assertFileDoesNotExist("$dossier/$ancien.json");
        $d = json_decode(file_get_contents("$dossier/$hier.json"), true);
        $this->assertArrayNotHasKey('empreintes', $d);
        $this->assertSame(4, $d['pages_vues']);
    }

    public function test_lien_de_suivi_redirige_vers_la_cible_enregistree(): void
    {
        Http::fake([
            'api.test/api/liens/gala-wa*' => Http::response(['ok' => true, 'cible' => 'https://rejcc.site/evenements/gala', 'canal' => 'whatsapp']),
            'api.test/api/liens/externe*' => Http::response(['ok' => true, 'cible' => 'https://www.youtube.com/watch?v=abc', 'canal' => 'youtube']),
            'api.test/api/liens/*' => Http::response(['ok' => false], 404),
        ]);

        $this->visite('/l/gala-wa')->assertStatus(302)->assertRedirect('https://rejcc.site/evenements/gala?utm_source=whatsapp');
        $this->visite('/l/externe')->assertStatus(302)->assertRedirect('https://www.youtube.com/watch?v=abc');
        $this->visite('/l/inconnu')->assertStatus(302)->assertRedirect('/');
        // Le robot d'aperçu de WhatsApp suit le lien sans compter de clic.
        $this->visite('/l/gala-wa', [], '10.0.0.9', 'WhatsApp/2.23.20.0 A')->assertStatus(302);

        Http::assertSent(fn (RequeteApi $r) => str_contains($r->url(), '/liens/gala-wa') && ! str_contains($r->url(), 'compter=0'));
        Http::assertSent(fn (RequeteApi $r) => str_contains($r->url(), '/liens/gala-wa?compter=0'));
        // Une adresse passée dans la requête n'est jamais suivie.
        $this->visite('/l/inconnu?url=https://evil.example')->assertRedirect('/');
    }
}
