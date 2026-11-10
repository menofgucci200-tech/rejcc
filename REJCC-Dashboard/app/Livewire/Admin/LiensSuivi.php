<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Liens de suivi de la Communication : un lien court rejcc.site/l/{code} par
 * canal de diffusion (WhatsApp, Facebook…) pour savoir lequel ramène du monde.
 * Les clics sont comptés par jour, sans rien garder de la personne qui clique.
 */
#[Layout('layouts.admin-light')]
class LiensSuivi extends Component
{
    public ?int $editId = null;

    public string $libelle = '';

    public string $cible = '';

    public string $canal = 'whatsapp';

    public string $code = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(): void
    {
        $this->code = self::codePropose();
    }

    /** Code court lisible (sans 0/o, 1/l/i, faciles à confondre). */
    public static function codePropose(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    public function proposerCode(): void
    {
        $this->code = self::codePropose();
    }

    public function enregistrer(): void
    {
        $this->message = $this->erreur = null;
        $donnees = ['libelle' => $this->libelle, 'cible' => $this->cible, 'canal' => $this->canal, 'code' => $this->code];
        $r = $this->editId
            ? Api::put("/admin/liens-suivi/{$this->editId}", $donnees, Api::token())
            : Api::post('/admin/liens-suivi', $donnees, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        $lien = Site::url('l/'.$r['lien']['code']);
        $this->message = $this->editId ? 'Lien modifié.' : "Lien créé : {$lien}";
        $this->annuler();
    }

    public function modifier(int $id): void
    {
        $lien = collect(Api::get('/admin/liens-suivi', [], Api::token())['liens'] ?? [])->firstWhere('id', $id);
        if (! $lien) {
            return;
        }
        $this->editId = $id;
        $this->libelle = $lien['libelle'];
        $this->cible = $lien['cible'];
        $this->canal = $lien['canal'];
        $this->code = $lien['code'];
        $this->message = $this->erreur = null;
    }

    public function annuler(): void
    {
        $this->editId = null;
        $this->libelle = $this->cible = '';
        $this->canal = 'whatsapp';
        $this->code = self::codePropose();
        $this->erreur = null;
    }

    public function basculer(int $id, bool $actif): void
    {
        $r = Api::put("/admin/liens-suivi/{$id}", ['actif' => $actif], Api::token());
        $this->dispatch('rj-toast', type: ($r['ok'] ?? false) ? 'success' : 'error', message: ($r['ok'] ?? false)
            ? ($actif ? 'Lien réactivé.' : 'Lien désactivé : il renvoie désormais vers l\'accueil du site.')
            : ($r['message'] ?? 'Modification impossible.'));
    }

    public function supprimer(int $id): void
    {
        $r = Api::delete("/admin/liens-suivi/{$id}", Api::token());
        if ($this->editId === $id) {
            $this->annuler();
        }
        $this->message = ($r['ok'] ?? false) ? 'Lien supprimé avec ses compteurs de clics.' : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Suppression impossible.');
    }

    public function render()
    {
        $r = Api::get('/admin/liens-suivi', [], Api::token());

        return view('livewire.admin.liens-suivi', [
            'ok' => $r['ok'] ?? false,
            'liens' => $r['liens'] ?? [],
            'parCanal' => $r['par_canal'] ?? [],
            'canaux' => $r['canaux'] ?? [],
            'base' => Site::url('l/'),
        ]);
    }
}
