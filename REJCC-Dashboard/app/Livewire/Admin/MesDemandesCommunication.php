<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * « Mes demandes de communication » : tout compte du dashboard demande un
 * visuel ou une annonce à la Communication (au lieu de WhatsApp), puis suit
 * ses demandes (statut, réponse) et peut les annuler tant qu'elles ne sont
 * pas acceptées. Le département est déduit du compte par l'API.
 */
#[Layout('layouts.admin-light')]
class MesDemandesCommunication extends Component
{
    use WithFileUploads;

    /** 3 pièces jointes au plus, images ou PDF de 8 Mo chacune. */
    public const FICHIERS_MAX = 3;

    public const TAILLE_MAX_KO = 8192;

    private const EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

    /** Formulaire ouvert (?nouvelle=1 depuis l'espace d'un département). */
    #[Url(as: 'nouvelle', except: false)]
    public bool $formulaire = false;

    /** Demande ouverte (?fiche=, lien des e-mails). */
    #[Url(as: 'fiche', except: null)]
    public ?int $ouverte = null;

    #[Locked]
    public array $plan = [];

    public string $type = '';

    public string $titre = '';

    public string $contenu = '';

    public string $dateSouhaitee = '';

    public string $dateEvenement = '';

    /** @var list<string> */
    public array $canaux = [];

    public string $contact = '';

    /** Fichiers en cours d'envoi (Livewire). */
    public $envois = [];

    /** @var list<array{chemin: string, nom: string, mime: string, octets: int}> déposés, pas encore transmis */
    #[Locked]
    public array $fichiers = [];

    public ?string $message = null;

    public ?string $avertissement = null;

    public ?string $erreur = null;

    public function mount(): void
    {
        $this->plan = Api::get('/admin/plan-diffusion', [], Api::token())['plan'] ?? ['canaux' => [], 'types' => [], 'delai' => []];
    }

    public function ouvrirFormulaire(): void
    {
        $this->formulaire = ! $this->formulaire;
        $this->reset(['message', 'avertissement', 'erreur']);
    }

    /** Le type choisi pré-coche les canaux du plan de diffusion. */
    public function updatedType(): void
    {
        $t = collect($this->plan['types'] ?? [])->firstWhere('cle', $this->type);
        $this->canaux = $t['canaux'] ?? [];
    }

    public function updatedEnvois(): void
    {
        $this->resetErrorBag('envois');
        $this->validate(['envois.*' => 'file|max:'.self::TAILLE_MAX_KO.'|mimetypes:'.implode(',', array_keys(self::EXTENSIONS))], [
            'envois.*.max' => 'Chaque fichier doit faire 8 Mo au plus.',
            'envois.*.mimetypes' => 'Joignez des images (JPG, PNG, WebP) ou des PDF.',
            'envois.*.uploaded' => "L'envoi a échoué : le fichier est peut-être trop lourd.",
        ]);
        foreach ((array) $this->envois as $f) {
            if (count($this->fichiers) >= self::FICHIERS_MAX) {
                $this->addError('envois', 'Joignez 3 fichiers au plus.');
                break;
            }
            $mime = $f->getMimeType();
            $chemin = $f->storeAs('demandes-communication/'.date('Y/m'), Str::uuid().'.'.self::EXTENSIONS[$mime], 'local');
            $this->fichiers[] = ['chemin' => $chemin, 'nom' => Str::limit($f->getClientOriginalName(), 190, ''), 'mime' => $mime, 'octets' => $f->getSize()];
        }
        $this->envois = [];
    }

    private static function cheminDemande(?string $chemin): bool
    {
        return is_string($chemin) && preg_match('/^demandes-communication\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|png|webp|pdf)$/', $chemin) === 1;
    }

    public function retirerFichier(int $i): void
    {
        if (isset($this->fichiers[$i]) && self::cheminDemande($this->fichiers[$i]['chemin'])) {
            Storage::disk('local')->delete($this->fichiers[$i]['chemin']);
        }
        unset($this->fichiers[$i]);
        $this->fichiers = array_values($this->fichiers);
    }

    /** Fichiers déposés mais jamais transmis : on les retire du stockage. */
    private function abandonnerFichiers(): void
    {
        foreach ($this->fichiers as $f) {
            if (self::cheminDemande($f['chemin'] ?? null)) {
                Storage::disk('local')->delete($f['chemin']);
            }
        }
        $this->fichiers = [];
    }

    public function annulerFormulaire(): void
    {
        $this->abandonnerFichiers();
        $this->reset(['type', 'titre', 'contenu', 'dateSouhaitee', 'dateEvenement', 'canaux', 'contact', 'formulaire']);
        $this->resetValidation();
    }

    public function envoyer(): void
    {
        $this->reset(['message', 'avertissement', 'erreur']);
        $this->validate([
            'type' => 'required',
            'titre' => 'required|string|min:3|max:150',
            'contenu' => 'required|string|min:10|max:5000',
            'dateSouhaitee' => 'required|date|after_or_equal:today',
            'dateEvenement' => 'nullable|date',
            'canaux' => 'required|array|min:1',
            'contact' => 'required|string|min:3|max:150',
        ], [
            'type.required' => 'Choisissez le type d\'information.',
            'titre.required' => 'Donnez un titre à votre demande.',
            'contenu.required' => 'Décrivez le message à faire passer.',
            'contenu.min' => 'Décrivez le message à faire passer (10 caractères au moins).',
            'dateSouhaitee.required' => 'Indiquez la date de publication souhaitée.',
            'dateSouhaitee.after_or_equal' => 'La date souhaitée ne peut pas être passée.',
            'canaux.required' => 'Choisissez au moins un canal.',
            'canaux.min' => 'Choisissez au moins un canal.',
            'contact.required' => 'Indiquez comment vous joindre.',
        ]);

        $r = Api::post('/admin/mes-demandes-communication', [
            'type' => $this->type,
            'titre' => trim($this->titre),
            'message' => trim($this->contenu),
            'date_souhaitee' => $this->dateSouhaitee,
            'date_evenement' => $this->dateEvenement ?: null,
            'canaux' => array_values($this->canaux),
            'contact' => trim($this->contact),
            'fichiers' => $this->fichiers,
        ], Api::token());

        if (! ($r['ok'] ?? false)) {
            $champs = ['message' => 'contenu', 'date_souhaitee' => 'dateSouhaitee', 'date_evenement' => 'dateEvenement'];
            foreach ($r['errors'] ?? [] as $champ => $msgs) {
                $this->addError($champs[$champ] ?? (str_starts_with($champ, 'fichiers') ? 'envois' : (str_starts_with($champ, 'canaux') ? 'canaux' : $champ)), $msgs[0]);
            }
            $this->erreur = $r['message'] ?? 'Envoi impossible.';

            return;
        }

        $this->fichiers = []; // transmis : ils appartiennent maintenant à la demande
        $this->reset(['type', 'titre', 'contenu', 'dateSouhaitee', 'dateEvenement', 'canaux', 'formulaire']);
        $this->message = 'Votre demande est transmise à la Communication. Vous serez prévenu par e-mail à chaque étape.';
        $this->avertissement = $r['avertissement_delai'] ?? null;
        $this->ouverte = $r['demande']['id'] ?? null;
    }

    public function ouvrir(int $id): void
    {
        $this->ouverte = $this->ouverte === $id ? null : $id;
    }

    public function annuler(int $id): void
    {
        $r = Api::post("/admin/mes-demandes-communication/{$id}/annuler", [], Api::token());
        if ($r['ok'] ?? false) {
            $this->message = 'Demande annulée.';
            $this->erreur = null;
        } else {
            $this->erreur = $r['message'] ?? 'Annulation impossible.';
        }
    }

    public function render()
    {
        $r = Api::get('/admin/mes-demandes-communication', [], Api::token());
        if ($this->contact === '' && ! empty($r['contact'])) {
            $this->contact = $r['contact'];
        }
        $fiche = null;
        if ($this->ouverte) {
            $f = Api::get("/admin/mes-demandes-communication/{$this->ouverte}", [], Api::token());
            $fiche = ($f['ok'] ?? false) ? $f['demande'] : null;
        }

        // Délai entre aujourd'hui et la date souhaitée (avertissement sous 7 jours, sans bloquer).
        $jours = null;
        if ($this->dateSouhaitee !== '') {
            try {
                $jours = (int) Carbon::today()->diffInDays(Carbon::parse($this->dateSouhaitee)->startOfDay(), false);
            } catch (\Throwable) {
                $jours = null;
            }
        }

        return view('livewire.admin.mes-demandes-communication', [
            'demandes' => $r['demandes'] ?? [],
            'departementLabel' => $r['departement_label'] ?? 'Administration',
            'fiche' => $fiche,
            'jours' => $jours,
        ]);
    }
}
