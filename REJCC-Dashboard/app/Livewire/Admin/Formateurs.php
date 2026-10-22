<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Formateurs des sessions live (département Formation) : un membre du
 * réseau, ou un intervenant extérieur qui reçoit un lien personnel par
 * session, sans compte.
 */
#[Layout('layouts.admin-light')]
class Formateurs extends Component
{
    public bool $formulaire = false;

    public ?int $editionId = null;

    /** membre | exterieur */
    public string $type = 'membre';

    public string $recherche = '';

    public ?int $userId = null;

    public string $membreChoisi = '';

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $specialite = '';

    public bool $actif = true;

    public ?string $erreur = null;

    public ?string $message = null;

    public function nouveau(): void
    {
        $this->reset(['editionId', 'recherche', 'userId', 'membreChoisi', 'prenom', 'nom', 'email', 'telephone', 'specialite', 'erreur', 'message']);
        $this->type = 'membre';
        $this->actif = true;
        $this->formulaire = true;
    }

    public function modifier(int $id): void
    {
        $f = collect(Api::get('/admin/formateurs', [], Api::token())['formateurs'] ?? [])->firstWhere('id', $id);
        if (! $f) {
            return;
        }
        $this->editionId = $id;
        $this->type = $f['exterieur'] ? 'exterieur' : 'membre';
        $this->userId = $f['user_id'];
        $this->membreChoisi = $f['exterieur'] ? '' : $f['nom_complet'];
        $this->prenom = $f['prenom'];
        $this->nom = $f['nom'];
        $this->email = (string) $f['email'];
        $this->telephone = (string) $f['telephone'];
        $this->specialite = (string) $f['specialite'];
        $this->actif = (bool) $f['actif'];
        $this->erreur = null;
        $this->formulaire = true;
    }

    public function choisirMembre(int $id, string $nom): void
    {
        $this->userId = $id;
        $this->membreChoisi = $nom;
        $this->recherche = '';
    }

    public function enregistrer(): void
    {
        $data = [
            'user_id' => $this->type === 'membre' ? $this->userId : null,
            'prenom' => $this->prenom ?: null,
            'nom' => $this->nom ?: null,
            'email' => $this->email ?: null,
            'telephone' => $this->telephone ?: null,
            'specialite' => $this->specialite ?: null,
            'actif' => $this->actif,
        ];
        if ($this->type === 'membre' && ! $this->userId) {
            $this->erreur = 'Choisissez le membre qui donnera les formations.';

            return;
        }
        if ($this->type === 'exterieur' && ! $this->email) {
            $this->erreur = 'Indiquez l\'e-mail de l\'intervenant : il y recevra son lien d\'animateur pour chaque session.';

            return;
        }

        $r = $this->editionId
            ? Api::put("/admin/formateurs/{$this->editionId}", $data, Api::token())
            : Api::post('/admin/formateurs', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Enregistrement impossible.';

            return;
        }
        $this->formulaire = false;
        $this->message = $this->editionId ? 'Formateur mis à jour.' : 'Formateur ajouté : choisissez-le en programmant une session live.';
    }

    public function render()
    {
        $resultats = $this->type === 'membre' && mb_strlen(trim($this->recherche)) >= 2
            ? Api::get('/admin/formateurs/membres', ['q' => trim($this->recherche)], Api::token())['membres'] ?? []
            : [];

        return view('livewire.admin.formateurs', [
            'formateurs' => Api::get('/admin/formateurs', [], Api::token())['formateurs'] ?? [],
            'resultats' => $resultats,
        ]);
    }
}
