<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Mes certificats et attestations : aperçu du PDF officiel, téléchargement,
 * lien de vérification à partager, ajout au profil LinkedIn, affichage sur
 * la page publique au choix, signalement d'une erreur.
 */
#[Layout('layouts.member-light')]
class Certificats extends Component
{
    /** Certificat ouvert (lien des notifications : ?certificat=ID). */
    #[Url(as: 'certificat', except: null)]
    public ?int $ouvert = null;

    public bool $correction = false;

    public string $messageCorrection = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public function ouvrir(int $id): void
    {
        $this->ouvert = $id;
        $this->correction = false;
        $this->message = $this->erreur = null;
    }

    public function fermer(): void
    {
        $this->ouvert = null;
    }

    public function basculerBio(int $id, bool $visible): void
    {
        $r = Api::put("/my-certificates/{$id}/bio", ['visible_bio' => $visible], Api::token());
        $this->dispatch('rj-toast', message: ($r['ok'] ?? false)
            ? ($visible ? 'Le certificat apparaît sur votre page publique.' : 'Le certificat n\'apparaît plus sur votre page publique.')
            : 'Une erreur est survenue.', type: ($r['ok'] ?? false) ? 'succes' : 'erreur');
    }

    public function envoyerCorrection(): void
    {
        $r = Api::post("/my-certificates/{$this->ouvert}/correction", ['message' => trim($this->messageCorrection)], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->correction = false;
        $this->messageCorrection = '';
        $this->erreur = null;
        $this->message = "Votre demande est transmise à l'équipe REJCC : un certificat corrigé vous sera délivré si nécessaire.";
    }

    /** Lien « Ajouter au profil » de LinkedIn (section Licences et certifications). */
    public static function lienLinkedin(array $c): string
    {
        $d = Carbon::parse($c['delivre_le']);

        return 'https://www.linkedin.com/profile/add?'.http_build_query([
            'startTask' => 'CERTIFICATION_NAME',
            'name' => $c['intitule'].' — '.$c['titre'],
            'organizationName' => 'REJCC — Réseau Entrepreneurial des Jeunes Chrétiens Catholiques',
            'issueYear' => $d->year,
            'issueMonth' => $d->month,
            'certUrl' => $c['url_verification'],
            'certId' => $c['reference'],
        ]);
    }

    public function render()
    {
        $certs = Collection::make(Api::get('/my-certificates', [], Api::token())['certificates'] ?? []);

        return view('livewire.member.certificats', [
            'certificats' => $certs,
            'parType' => $certs->where('statut', 'valide')->countBy('type'),
            'cert' => $this->ouvert ? $certs->firstWhere('id', $this->ouvert) : null,
        ]);
    }
}
