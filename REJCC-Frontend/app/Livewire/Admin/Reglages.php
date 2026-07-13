<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Content\SiteConfig;
use App\Support\Content\SiteRemote;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Réglages du site vitrine : identité, coordonnées, réseaux sociaux et
 * bandeau d'annonce. Chaque carte s'enregistre indépendamment ; la vitrine
 * est mise à jour immédiatement (purge du cache SiteRemote).
 */
#[Layout('layouts.admin-light')]
class Reglages extends Component
{
    // Identité
    public string $slogan = '';

    public string $about = '';

    public string $positioning = '';

    public string $mission = '';

    public string $vision = '';

    // Coordonnées
    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    // Réseaux sociaux
    public string $facebook = '';

    public string $instagram = '';

    public string $linkedin = '';

    public string $youtube = '';

    public string $tiktok = '';

    public string $whatsapp = '';

    // Bandeau d'annonce
    public bool $bannerEnabled = false;

    public string $bannerText = '';

    public string $bannerLink = '';

    public string $bannerLabel = '';

    public ?string $savedCard = null;

    public function mount(): void
    {
        $site = SiteConfig::get();

        $this->slogan = $site['slogan'];
        $this->about = $site['about'];
        $this->positioning = $site['positioning'];
        $this->mission = $site['mission'];
        $this->vision = $site['vision'];

        $this->email = $site['contact']['email'];
        $this->phone = $site['contact']['phone'];
        $this->address = $site['contact']['address'];
        $this->city = $site['contact']['city'];

        $this->facebook = (string) SiteRemote::setting('social.facebook', '');
        $this->instagram = (string) SiteRemote::setting('social.instagram', '');
        $this->linkedin = (string) SiteRemote::setting('social.linkedin', '');
        $this->youtube = (string) SiteRemote::setting('social.youtube', '');
        $this->tiktok = (string) SiteRemote::setting('social.tiktok', '');
        $this->whatsapp = (string) SiteRemote::setting('social.whatsapp', '');

        $this->bannerEnabled = (bool) SiteRemote::setting('banner.enabled', false);
        $this->bannerText = (string) SiteRemote::setting('banner.text', '');
        $this->bannerLink = (string) SiteRemote::setting('banner.link', '');
        $this->bannerLabel = (string) SiteRemote::setting('banner.label', '');
    }

    public function saveIdentite(): void
    {
        $this->validate([
            'slogan' => 'required|string|max:120',
            'about' => 'required|string|max:600',
            'positioning' => 'required|string|max:600',
            'mission' => 'required|string|max:600',
            'vision' => 'required|string|max:600',
        ]);

        $this->push('identite', [
            'identity.slogan' => $this->slogan,
            'identity.about' => $this->about,
            'identity.positioning' => $this->positioning,
            'identity.mission' => $this->mission,
            'identity.vision' => $this->vision,
        ]);
    }

    public function saveCoordonnees(): void
    {
        $this->validate([
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:200',
            'city' => 'required|string|max:80',
        ]);

        $this->push('coordonnees', [
            'contact.email' => $this->email,
            'contact.phone' => $this->phone,
            'contact.address' => $this->address,
            'contact.city' => $this->city,
        ]);
    }

    public function saveReseaux(): void
    {
        $this->validate([
            'facebook' => 'nullable|url|max:300',
            'instagram' => 'nullable|url|max:300',
            'linkedin' => 'nullable|url|max:300',
            'youtube' => 'nullable|url|max:300',
            'tiktok' => 'nullable|url|max:300',
            'whatsapp' => 'nullable|url|max:300',
        ], [
            '*.url' => 'Collez l\'adresse complète (https://…).',
        ]);

        $this->push('reseaux', [
            'social.facebook' => $this->facebook,
            'social.instagram' => $this->instagram,
            'social.linkedin' => $this->linkedin,
            'social.youtube' => $this->youtube,
            'social.tiktok' => $this->tiktok,
            'social.whatsapp' => $this->whatsapp,
        ]);
    }

    public function saveBannereAnnonce(): void
    {
        $this->validate([
            'bannerText' => $this->bannerEnabled ? 'required|string|max:180' : 'nullable|string|max:180',
            'bannerLink' => 'nullable|string|max:300',
            'bannerLabel' => 'nullable|string|max:60',
        ], [
            'bannerText.required' => 'Saisissez le message à afficher (ou désactivez le bandeau).',
        ]);

        $this->push('annonce', [
            'banner.enabled' => $this->bannerEnabled,
            'banner.text' => $this->bannerText,
            'banner.link' => $this->bannerLink,
            'banner.label' => $this->bannerLabel ?: 'En savoir plus',
        ]);
    }

    private function push(string $card, array $settings): void
    {
        $result = Api::put('/admin/site-settings', ['settings' => $settings], Api::token());

        if ($result['ok'] ?? false) {
            SiteRemote::clear();
            $this->savedCard = $card;
        } else {
            $this->addError('slogan', $result['message'] ?? 'Une erreur est survenue.');
        }
    }

    public function render()
    {
        return view('livewire.admin.reglages');
    }
}
