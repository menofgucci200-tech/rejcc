<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Coffre;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * « Mes documents personnels » : pièces d'identité, passeport, extrait de
 * naissance, diplômes… rangés en privé (chiffrés), avec date d'expiration
 * et rappel, et partage volontaire avec l'équipe REJCC.
 */
class CoffreFort extends Component
{
    use WithFileUploads;

    /** Pièces proposées en raccourci quand elles manquent. */
    public const ESSENTIELS = ['cni', 'passeport', 'extrait', 'casier', 'diplome', 'cv'];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $type = '';

    public string $titre = '';

    public string $delivreLe = '';

    public string $expireLe = '';

    public bool $partage = false;

    public $fichier = null;

    public ?string $fichierActuel = null;

    public ?int $voirId = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public function ouvrirAjout(string $type = ''): void
    {
        $this->reset(['editingId', 'titre', 'delivreLe', 'expireLe', 'partage', 'fichier', 'fichierActuel', 'message', 'erreur']);
        $this->type = $type;
        $this->resetValidation();
        $this->showForm = true;
        $this->voirId = null;
    }

    public function modifier(int $id): void
    {
        $d = $this->documents()->firstWhere('id', $id);
        if (! $d) {
            return;
        }
        $this->ouvrirAjout($d['type']);
        $this->editingId = $id;
        $this->titre = (string) $d['titre'];
        $this->delivreLe = (string) $d['delivre_le'];
        $this->expireLe = (string) $d['expire_le'];
        $this->partage = (bool) $d['partage'];
        $this->fichierActuel = $d['fichier_nom'].($d['taille'] ? ' · '.$d['taille'] : '');
    }

    public function fermer(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->fichier = null;
    }

    public function voir(?int $id): void
    {
        $this->voirId = $id;
    }

    protected function regles(): array
    {
        return [
            'type' => 'required',
            'titre' => $this->type === 'autre' ? 'required|string|max:150' : 'nullable|string|max:150',
            'fichier' => ($this->editingId ? 'nullable' : 'required').'|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            'delivreLe' => 'nullable|date|before_or_equal:today',
            'expireLe' => 'nullable|date'.($this->delivreLe ? '|after_or_equal:delivreLe' : ''),
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required' => 'Choisissez le type de document.',
            'titre.required' => 'Donnez un nom à ce document.',
            'fichier.required' => 'Joignez le fichier (photo ou scan).',
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
            'fichier.mimes' => 'Formats acceptés : PDF, photo (JPG, PNG, WEBP) ou Word.',
            'delivreLe.before_or_equal' => 'La date de délivrance ne peut pas être dans le futur.',
            'expireLe.after_or_equal' => "La date d'expiration doit suivre la date de délivrance.",
        ];
    }

    public function updatedFichier(): void
    {
        $this->validateOnly('fichier', $this->regles(), $this->messages());
    }

    public function enregistrer(): void
    {
        $this->validate($this->regles(), $this->messages());
        $moi = Api::user();

        $data = [
            'type' => $this->type, 'titre' => trim($this->titre) ?: null,
            'delivre_le' => $this->delivreLe ?: null, 'expire_le' => $this->expireLe ?: null, 'partage' => $this->partage,
        ];
        $chemin = null;
        if ($this->fichier) {
            $chemin = Coffre::ranger($this->fichier, (int) $moi->id);
            $data += ['fichier' => $chemin, 'fichier_nom' => $this->fichier->getClientOriginalName(), 'mime' => $this->fichier->getMimeType(), 'octets' => $this->fichier->getSize()];
        }

        $r = $this->editingId
            ? Api::put("/mes-documents/{$this->editingId}", $data, Api::token())
            : Api::post('/mes-documents', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            Coffre::supprimer($chemin);
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué, réessayez.";

            return;
        }
        Coffre::supprimer($r['ancien_fichier'] ?? null);
        $this->message = $this->editingId ? 'Document mis à jour.' : 'Document ajouté à votre espace privé.';
        $this->erreur = null;
        $this->fermer();
    }

    public function supprimer(int $id): void
    {
        $r = Api::delete("/mes-documents/{$id}", Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        Coffre::supprimer($r['fichier'] ?? null);
        $this->voirId = null;
        $this->message = 'Document supprimé définitivement.';
    }

    protected function documents(): Collection
    {
        return Collection::make(Api::get('/mes-documents', [], Api::token())['documents'] ?? []);
    }

    public function render()
    {
        $data = Api::get('/mes-documents', [], Api::token());
        $docs = Collection::make($data['documents'] ?? []);
        $types = $data['types'] ?? [];

        return view('livewire.member.coffre-fort', [
            'docs' => $docs,
            'types' => $types,
            'max' => (int) ($data['max'] ?? 40),
            'manquants' => collect(self::ESSENTIELS)->reject(fn ($t) => $docs->contains('type', $t))->mapWithKeys(fn ($t) => [$t => $types[$t] ?? $t]),
            'alertes' => $docs->whereIn('etat', ['expire', 'bientot']),
            'voir' => $this->voirId ? $docs->firstWhere('id', $this->voirId) : null,
        ]);
    }
}
