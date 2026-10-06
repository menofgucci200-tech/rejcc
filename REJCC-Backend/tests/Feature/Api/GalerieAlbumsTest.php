<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GalerieAlbumsTest extends TestCase
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

    public function test_l_admin_cree_un_album_et_y_range_des_photos(): void
    {
        $token = $this->adminToken();

        $album = $this->withToken($token)->postJson('/api/admin/site-content/albums', [
            'titre' => 'Assemblée générale 2026',
            'date_evenement' => '2026-09-20',
            'lieu' => 'Paroisse Saint Joseph, Riviera Bonoumin',
            'publie' => true,
        ])->assertOk()->json('item');
        $this->assertSame('assemblee-generale-2026', $album['slug']);

        // Un second album du même nom reçoit une adresse distincte.
        $autre = $this->withToken($token)->postJson('/api/admin/site-content/albums', ['titre' => 'Assemblée générale 2026'])->json('item');
        $this->assertSame('assemblee-generale-2026-2', $autre['slug']);

        foreach (['a', 'b', 'c'] as $n) {
            $this->withToken($token)->postJson('/api/admin/site-content/gallery', [
                'album_id' => $album['id'],
                'url' => "https://exemple.ci/{$n}.jpg",
                'caption' => "Photo {$n}",
            ])->assertOk();
        }

        // Liste admin : nombre de photos et couverture par défaut (1re photo).
        $liste = $this->withToken($token)->getJson('/api/admin/site-content/albums')->assertOk()->json('items');
        $ag = collect($liste)->firstWhere('id', $album['id']);
        $this->assertSame(3, $ag['photos_count']);
        $this->assertSame('https://exemple.ci/a.jpg', $ag['couverture_url']);

        // Public : seuls les albums publiés et non vides sont listés.
        $this->getJson('/api/albums')->assertOk()
            ->assertJsonCount(1, 'albums')
            ->assertJsonPath('albums.0.slug', 'assemblee-generale-2026')
            ->assertJsonPath('albums.0.photos', 3);

        $this->getJson('/api/albums/assemblee-generale-2026')->assertOk()
            ->assertJsonPath('album.lieu', 'Paroisse Saint Joseph, Riviera Bonoumin')
            ->assertJsonCount(3, 'photos');
    }

    public function test_un_album_non_publie_reste_prive_et_sa_suppression_garde_les_photos(): void
    {
        $album = GalleryAlbum::create(['titre' => 'Brouillon', 'slug' => 'brouillon', 'publie' => false]);
        $photo = GalleryPhoto::create(['album_id' => $album->id, 'url' => 'https://exemple.ci/x.jpg']);

        $this->getJson('/api/albums')->assertOk()->assertJsonCount(0, 'albums');
        $this->getJson('/api/albums/brouillon')->assertNotFound();

        $this->withToken($this->adminToken())->deleteJson("/api/admin/site-content/albums/{$album->id}")->assertOk();
        $this->assertNull($photo->fresh()->album_id);
    }

    public function test_une_photo_ne_peut_pas_viser_un_album_inexistant(): void
    {
        $this->withToken($this->adminToken())->postJson('/api/admin/site-content/gallery', [
            'album_id' => 999,
            'url' => 'https://exemple.ci/x.jpg',
        ])->assertStatus(422);
    }
}
