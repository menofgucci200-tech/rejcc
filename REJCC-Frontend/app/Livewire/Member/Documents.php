<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Documents & ressources : bibliothèque du réseau (recherche, catégories,
 * aperçu intégré, téléchargement contrôlé) et propositions des membres
 * abonnés, publiées après validation de l'équipe.
 */
#[Layout('layouts.member-light')]
class Documents extends Component
{
    use WithFileUploads;

    #[Url(except: 'bibliotheque')]
    public string $onglet = 'bibliotheque';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: '')]
    public string $categorie = '';

    /** Documents réservés à un groupe sectoriel (lien depuis la page du groupe). */
    #[Url(except: '')]
    public string $groupe = '';

    #[Url(except: 'recents')]
    public string $tri = 'recents';

    /** Document ouvert dans la visionneuse (lien partageable, notification). */
    #[Url(as: 'document', except: null)]
    public ?int $ouvert = null;

    public ?string $message = null;

    public ?string $erreur = null;

    // Proposition d'un document
    public bool $showProposer = false;

    public string $pTitre = '';

    public string $pDescription = '';

    public string $pCategorie = '';

    /** Fichier temporaire : stocké seulement à l'envoi de la proposition. */
    public $pFichier = null;

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['bibliotheque', 'propositions', 'personnels'], true) ? $o : 'bibliotheque';
        $this->ouvert = null;
        $this->message = $this->erreur = null;
    }

    public function setCategorie(string $id): void
    {
        $this->categorie = $this->categorie === $id ? '' : $id;
    }

    public function retirerGroupe(): void
    {
        $this->groupe = '';
    }

    public function ouvrir(int $id): void
    {
        $this->ouvert = $id;
    }

    public function fermer(): void
    {
        $this->ouvert = null;
    }

    // ── Propositions ─────────────────────────────────────────────────────

    public function ouvrirProposer(): void
    {
        $this->reset(['pTitre', 'pDescription', 'pCategorie', 'pFichier', 'message', 'erreur']);
        $this->resetValidation();
        $this->showProposer = true;
    }

    public function fermerProposer(): void
    {
        $this->showProposer = false;
        $this->pFichier = null;
    }

    public function updatedPFichier(): void
    {
        $this->validateOnly('pFichier', $this->regles(), $this->messages());
    }

    protected function regles(): array
    {
        return [
            'pFichier' => 'required|file|max:20480|mimes:pdf,doc,docx,odt,xls,xlsx,ods,csv,ppt,pptx,odp,txt,jpg,jpeg,png,webp,mp3,m4a,mp4',
            'pTitre' => 'required|string|min:3|max:200',
            'pCategorie' => 'required',
            'pDescription' => 'required|string|min:10|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'pFichier.required' => 'Joignez le fichier à partager.',
            'pFichier.max' => 'Le fichier ne doit pas dépasser 20 Mo.',
            'pFichier.mimes' => 'Format non pris en charge (PDF, Word, Excel, PowerPoint, image, audio, vidéo MP4).',
            'pTitre.required' => 'Donnez un titre au document.',
            'pTitre.min' => 'Le titre est trop court.',
            'pCategorie.required' => 'Choisissez une catégorie.',
            'pDescription.required' => 'Expliquez en quelques mots ce que contient le document et à qui il sert.',
            'pDescription.min' => 'La description est trop courte.',
        ];
    }

    public function proposer(): void
    {
        $this->validate($this->regles(), $this->messages());

        $nom = $this->pFichier->getClientOriginalName();
        $base = Str::slug(pathinfo($nom, PATHINFO_FILENAME)) ?: 'document';
        $chemin = $this->pFichier->storeAs('documents/propositions/'.Api::user()->id, $base.'-'.Str::random(6).'.'.strtolower($this->pFichier->getClientOriginalExtension()), 'local');

        $r = Api::post('/documents', [
            'title' => trim($this->pTitre), 'description' => trim($this->pDescription), 'category_id' => (int) $this->pCategorie,
            'fichier' => $chemin, 'fichier_nom' => $nom, 'mime' => $this->pFichier->getMimeType(), 'octets' => $this->pFichier->getSize(),
        ], Api::token());

        if (! ($r['ok'] ?? false)) {
            Storage::disk('local')->delete($chemin);
            $this->erreur = $r['message'] ?? "L'envoi a échoué, réessayez.";

            return;
        }
        $this->showProposer = false;
        $this->reset(['pTitre', 'pDescription', 'pCategorie', 'pFichier']);
        $this->erreur = null;
        $this->message = "Merci ! Votre document est transmis à l'équipe REJCC : vous serez prévenu dès sa publication.";
        $this->onglet = 'propositions';
    }

    public function retirer(int $id): void
    {
        $r = Api::delete("/documents/{$id}", Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        if (! empty($r['fichier'])) {
            Storage::disk('local')->delete($r['fichier']);
        }
        $this->ouvert = null;
        $this->erreur = null;
        $this->message = 'Proposition retirée.';
    }

    public function render()
    {
        $data = Api::get('/documents', array_filter([
            'q' => trim($this->recherche), 'categorie' => $this->categorie, 'groupe' => $this->groupe,
            'tri' => $this->tri !== 'recents' ? $this->tri : null,
        ]), Api::token());
        $docs = Collection::make($data['documents'] ?? []);
        $propositions = Collection::make($data['mes_propositions'] ?? []);

        // Document ouvert : pris dans la liste, sinon parmi mes propositions, sinon sans filtre (lien direct).
        $doc = $this->ouvert ? ($docs->firstWhere('id', $this->ouvert) ?? $propositions->firstWhere('id', $this->ouvert)) : null;
        if ($this->ouvert && ! $doc) {
            $doc = collect(Api::get('/documents', [], Api::token())['documents'] ?? [])->firstWhere('id', $this->ouvert);
        }

        $groupeNom = null;
        if ($this->groupe !== '') {
            $groupeNom = $docs->first()['groupe']['nom'] ?? null;
        }

        return view('livewire.member.documents', [
            'docs' => $docs,
            'categories' => Collection::make($data['categories'] ?? []),
            'propositions' => $propositions,
            'enAttente' => $propositions->where('statut', 'en_attente')->count(),
            'peutProposer' => (bool) ($data['peut_proposer'] ?? false),
            'doc' => $doc,
            'groupeNom' => $groupeNom,
            'filtresActifs' => trim($this->recherche) !== '' || $this->categorie !== '' || $this->groupe !== '',
        ]);
    }
}
