<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Content\DailyWord;
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


    // Bandeau d'annonce
    public bool $bannerEnabled = false;

    public string $bannerText = '';

    public string $bannerLink = '';

    public string $bannerLabel = '';

    // Paiement (abonnement annuel via CinetPay)
    public string $cinetpayApiKey = '';

    public string $cinetpaySiteId = '';

    // Parole & prière du jour (espace membre)
    public array $paroles = [];

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

        $this->bannerEnabled = (bool) SiteRemote::setting('banner.enabled', false);
        $this->bannerText = (string) SiteRemote::setting('banner.text', '');
        $this->bannerLink = (string) SiteRemote::setting('banner.link', '');
        $this->bannerLabel = (string) SiteRemote::setting('banner.label', '');

        $this->paroles = array_map(
            fn (array $p) => ['verset' => (string) ($p['verset'] ?? ''), 'reference' => (string) ($p['reference'] ?? ''), 'intention' => (string) ($p['intention'] ?? '')],
            DailyWord::all(),
        );

        // Réglages sensibles : jamais exposés par l'API publique, on les
        // récupère via l'endpoint admin dédié.
        $admin = Api::get('/admin/site-settings', [], Api::token())['settings'] ?? [];
        $this->cinetpayApiKey = (string) ($admin['payment.cinetpay_api_key'] ?? '');
        $this->cinetpaySiteId = (string) ($admin['payment.cinetpay_site_id'] ?? '');
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
        ], [
            '*.url' => 'Collez l\'adresse complète (https://…).',
        ]);

        $this->push('reseaux', [
            'social.facebook' => $this->facebook,
            'social.instagram' => $this->instagram,
            'social.linkedin' => $this->linkedin,
            'social.youtube' => $this->youtube,
            'social.tiktok' => $this->tiktok,
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

    public function ajouterParole(): void
    {
        $this->paroles[] = ['verset' => '', 'reference' => '', 'intention' => ''];
    }

    public function retirerParole(int $i): void
    {
        unset($this->paroles[$i]);
        $this->paroles = array_values($this->paroles);
    }

    public function saveParoles(): void
    {
        $this->validate([
            'paroles' => 'required|array|min:1|max:60',
            'paroles.*.verset' => 'required|string|max:400',
            'paroles.*.reference' => 'required|string|max:60',
            'paroles.*.intention' => 'nullable|string|max:300',
        ], [
            'paroles.required' => 'Gardez au moins un verset.',
            'paroles.min' => 'Gardez au moins un verset.',
            'paroles.*.verset.required' => 'Chaque ligne doit contenir un verset.',
            'paroles.*.reference.required' => 'Indiquez la référence de chaque verset (ex : Proverbes 16:3).',
        ]);

        $this->push('paroles', [DailyWord::SETTING => array_values($this->paroles)], 'paroles');
    }

    public function savePaiement(): void
    {
        $this->validate([
            'cinetpayApiKey' => 'nullable|string|max:200',
            'cinetpaySiteId' => 'nullable|string|max:100',
        ]);

        $this->push('paiement', [
            'payment.cinetpay_api_key' => $this->cinetpayApiKey,
            'payment.cinetpay_site_id' => $this->cinetpaySiteId,
        ], 'cinetpayApiKey');
    }

    private function push(string $card, array $settings, string $errorField = 'slogan'): void
    {
        $result = Api::put('/admin/site-settings', ['settings' => $settings], Api::token());

        if ($result['ok'] ?? false) {
            SiteRemote::clear();
            $this->savedCard = $card;
        } else {
            $this->addError($errorField, $result['message'] ?? 'Une erreur est survenue.');
        }
    }

    public function render()
    {
        return view('livewire.admin.reglages', [
            'paroleDuJour' => DailyWord::indexFor(count($this->paroles)),
        ]);
    }
}
