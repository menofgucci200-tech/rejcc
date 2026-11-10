<?php

namespace Tests\Feature;

use App\Support\Content\SiteConfig;
use App\Support\Content\SiteRemote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * CathoImpact mis en avant : section de l'accueil (masquable depuis Pages du
 * site), lien dans la barre de navigation, page /cathoimpact et son partage.
 */
class CathoImpactAccueilTest extends TestCase
{
    private function portrait(int $id, string $nom): array
    {
        return [
            'id' => $id, 'nom' => $nom, 'accroche' => 'Accroche de '.$nom,
            'image_url' => "https://rejcc.site/uploads/portrait-{$id}.jpg",
            'lien' => "https://www.instagram.com/p/{$id}/", 'reseau' => 'instagram', 'reseau_label' => 'Instagram',
        ];
    }

    private function fausseApi(array $portraits = [], array $sections = []): void
    {
        Cache::flush();
        SiteRemote::clear(); // mémo statique de la requête précédente
        Http::swap(new \Illuminate\Http\Client\Factory()); // les faux appels ne s'additionnent pas
        Http::fake([
            '*/site-settings' => Http::response(['settings' => [], 'sections' => $sections]),
            '*/cathoimpact/portraits' => Http::response(['ok' => true, 'portraits' => $portraits]),
            '*' => Http::response(['ok' => false]),
        ]);
    }

    public function test_l_accueil_presente_cathoimpact_avec_les_3_derniers_portraits(): void
    {
        $this->fausseApi(array_map(fn ($i) => $this->portrait($i, "Portrait {$i}"), [1, 2, 3, 4]));

        $r = $this->get('/')->assertOk();
        $r->assertSee('data-test="accueil-cathoimpact"', false);
        $r->assertSee('des parcours qui', false);
        $r->assertSee('Raconter mon parcours');
        $r->assertSee('Découvrir CathoImpact');
        $r->assertSee(route('cathoimpact.questionnaire'), false);
        $r->assertSee('brand/cathoimpact/01.jpg', false);
        // Les 3 plus récents seulement, du plus récent au plus ancien.
        $this->assertSame(3, substr_count($r->getContent(), 'data-test="accueil-cathoimpact-portrait"'));
        $r->assertSeeInOrder(['Portrait 4', 'Portrait 3', 'Portrait 2']);
        $r->assertDontSee('Portrait 1 :', false);
        $r->assertSee('https://www.instagram.com/p/4/', false);
    }

    public function test_sans_portrait_la_section_s_affiche_sans_galerie(): void
    {
        $this->fausseApi();

        $r = $this->get('/')->assertOk();
        $r->assertSee('data-test="accueil-cathoimpact"', false);
        $r->assertDontSee('data-test="accueil-cathoimpact-galerie"', false);
    }

    public function test_la_com_peut_masquer_la_section_et_modifier_l_accroche(): void
    {
        $this->fausseApi([], [['page' => 'home', 'section' => 'cathoimpact', 'visible' => false, 'content' => null]]);
        $this->get('/')->assertOk()->assertDontSee('data-test="accueil-cathoimpact"', false);

        $this->fausseApi([], [['page' => 'home', 'section' => 'cathoimpact', 'visible' => true, 'content' => ['subtitle' => 'Accroche choisie par la Com']]]);
        $this->get('/')->assertOk()->assertSee('Accroche choisie par la Com');
    }

    public function test_la_barre_de_navigation_mene_a_cathoimpact_et_contact_reste_accessible(): void
    {
        $this->fausseApi();

        $principale = array_column(SiteConfig::navPrincipale(), 'href');
        $this->assertContains('/cathoimpact', $principale);
        $this->assertLessThanOrEqual(6, count($principale));
        // Contact passe dans « Aussi sur le site » (menu mobile) et reste dans le pied de page.
        $this->assertContains('/contact', array_column(SiteConfig::navSecondaire(), 'href'));
        $this->assertContains('/contact', array_column(SiteConfig::nav(), 'href'));
    }

    public function test_la_page_cathoimpact_propose_le_questionnaire_tout_de_suite_et_se_partage_bien(): void
    {
        $this->fausseApi();

        $r = $this->get('/cathoimpact')->assertOk();
        // Bouton sous la description, avant le carrousel.
        $r->assertSeeInOrder(['data-test="cathoimpact-repondre"', 'data-test="cathoimpact-slide"'], false);
        $r->assertSee('Comment ça marche ?');
        $r->assertSee('<meta property="og:image" content="'.asset('brand/cathoimpact-partage.jpg').'">', false);
        $r->assertSee('<meta property="og:image:width" content="1200">', false);
        $r->assertSee('<meta property="og:title" content="CathoImpact : des parcours qui inspirent · REJCC">', false);
        $this->assertFileExists(public_path('brand/cathoimpact-partage.jpg'));
    }
}
