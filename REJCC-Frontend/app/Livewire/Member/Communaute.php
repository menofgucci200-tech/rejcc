<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\NavCompteurs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Communauté REJCC : salons de discussion de tous les membres (Annonces et
 * salons repris des communautés WhatsApp). Le fil ouvert est rafraîchi toutes
 * les 4 s (nouveaux messages, modifications, réactions) ; la liste des salons
 * toutes les 20 s.
 */
#[Layout('layouts.member-light')]
class Communaute extends Component
{
    use WithFileUploads;

    /** Nombre maximal de messages gardés à l'écran (les plus anciens se rechargent). */
    private const MAX_AFFICHES = 160;

    public array $salons = [];

    public bool $peutFichiers = false;

    public array $emojis = [];

    #[Url(as: 'salon', history: true)]
    public ?string $slug = null;

    public ?array $salon = null;

    public array $moi = [];

    public array $messages = [];

    public int $dernierId = 0;

    public ?string $horodatage = null;

    public bool $plusAnciens = false;

    /** Le salon demandé ne peut pas être ouvert (raison affichée). */
    public ?string $erreur = null;

    public ?string $erreurCode = null;

    public ?string $info = null;

    public string $texte = '';

    /** Membres mentionnés dans le message en cours : [id => « Prénom Nom »]. */
    public array $mentions = [];

    public ?array $reponseA = null;

    public ?int $editionId = null;

    public string $editionTexte = '';

    public ?int $signalementId = null;

    public string $motif = '';

    public $photo = null;

    public $document = null;

    public $vocal = null;

    public string $legende = '';

    public int $tick = 0;

    public function mount(): void
    {
        $this->chargerListe();
        if ($this->slug) {
            $this->ouvrir($this->slug);
        }
    }

    // ── Liste des salons ─────────────────────────────────────────────────

    public function chargerListe(): void
    {
        $r = Api::get('/communaute', [], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->info = $r['message'] ?? 'La Communauté est momentanément indisponible.';

            return;
        }
        $this->salons = $r['salons'] ?? [];
        $this->peutFichiers = (bool) ($r['peut_fichiers'] ?? false);
        $this->emojis = $r['reactions'] ?? [];
        $this->signalerCompteur();
    }

    private function signalerCompteur(): void
    {
        $n = (int) collect($this->salons)->sum('non_lus');
        NavCompteurs::fixer('communaute', $n);
        $this->dispatch('compteur-communaute', n: $n);
    }

    // ── Salon ouvert ─────────────────────────────────────────────────────

    public function ouvrir(string $slug): void
    {
        $this->reinitialiserSaisie();
        $this->slug = $slug;
        $this->salon = null;
        $this->messages = [];
        $this->erreur = null;
        $this->erreurCode = null;

        $r = Api::get('/communaute/salons/'.rawurlencode($slug), [], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Ce salon est indisponible.';
            $this->erreurCode = $r['code'] ?? null;

            return;
        }
        $this->appliquerSalon($r);
        $this->messages = $r['messages'] ?? [];
        $this->plusAnciens = (bool) ($r['plus_anciens'] ?? false);
        $this->dernierId = (int) (collect($this->messages)->max('id') ?? 0);

        foreach ($this->salons as $i => $s) {
            if ($s['slug'] === $slug) {
                $this->salons[$i]['non_lus'] = 0;
            }
        }
        $this->signalerCompteur();
        $this->dispatch('fil-bas');
    }

    public function fermer(): void
    {
        $this->reinitialiserSaisie();
        $this->slug = null;
        $this->salon = null;
        $this->messages = [];
        $this->erreur = null;
        $this->chargerListe();
    }

    private function appliquerSalon(array $r): void
    {
        $this->salon = $r['salon'];
        $this->moi = $r['moi'] ?? [];
        $this->horodatage = $r['horodatage'] ?? now()->toIso8601String();
    }

    /** Rafraîchissement périodique : nouveaux messages, modifications, réactions. */
    public function rafraichir(): void
    {
        $this->tick++;
        if ($this->tick % 5 === 0) {
            $this->chargerListe();
        }
        if (! $this->slug || ! $this->salon) {
            return;
        }
        $r = Api::get('/communaute/salons/'.rawurlencode($this->slug), [
            'after' => $this->dernierId,
            'depuis' => $this->horodatage,
        ], Api::token());
        if (! ($r['ok'] ?? false)) {
            if (in_array((int) ($r['status'] ?? 0), [402, 403, 404], true) || isset($r['code'])) {
                $this->erreur = $r['message'] ?? 'Ce salon n\'est plus accessible.';
                $this->salon = null;
            }

            return;
        }
        $this->appliquerSalon($r);

        $index = collect($this->messages)->pluck('id')->flip();
        foreach ($r['modifies'] ?? [] as $m) {
            if ($index->has($m['id'])) {
                $this->messages[$index[$m['id']]] = $m;
            }
        }
        $nouveaux = collect($r['messages'] ?? [])->reject(fn ($m) => $index->has($m['id']));
        if ($nouveaux->isNotEmpty()) {
            $this->messages = array_values(array_merge($this->messages, $nouveaux->all()));
            $this->dernierId = (int) collect($this->messages)->max('id');
            $this->limiter();
            $this->dispatch('fil-nouveaux');
        }
    }

    public function chargerPlusAnciens(): void
    {
        if (! $this->slug || ! $this->messages) {
            return;
        }
        $r = Api::get('/communaute/salons/'.rawurlencode($this->slug), ['before' => $this->messages[0]['id']], Api::token());
        if ($r['ok'] ?? false) {
            $this->messages = array_values(array_merge($r['messages'] ?? [], $this->messages));
            $this->plusAnciens = (bool) ($r['plus_anciens'] ?? false);
        }
    }

    private function limiter(): void
    {
        if (count($this->messages) > self::MAX_AFFICHES) {
            $this->messages = array_values(array_slice($this->messages, -self::MAX_AFFICHES));
            $this->plusAnciens = true;
        }
    }

    // ── Écrire ───────────────────────────────────────────────────────────

    /** Suggestions pour une mention @… (appelé depuis le champ de saisie). */
    public function chercherMembres(string $q): array
    {
        $r = Api::get('/communaute/membres', ['q' => $q], Api::token());

        return collect($r['membres'] ?? [])->map(fn ($m) => [
            'id' => $m['id'],
            'nom' => trim($m['prenom'].' '.$m['nom']),
            'initiales' => mb_strtoupper(mb_substr($m['prenom'] ?? '', 0, 1).mb_substr($m['nom'] ?? '', 0, 1)),
            'photo' => $m['photo'] ?? null,
        ])->all();
    }

    public function ajouterMention(int $id, string $nom): void
    {
        $this->mentions[$id] = $nom;
    }

    public function repondre(int $id): void
    {
        $m = collect($this->messages)->firstWhere('id', $id);
        if ($m && $m['type'] !== 'supprime') {
            $this->reponseA = [
                'id' => $m['id'],
                'auteur' => trim(($m['auteur']['prenom'] ?? '').' '.($m['auteur']['nom'] ?? '')),
                'extrait' => match ($m['type']) {
                    'image' => '📷 Photo',
                    'fichier' => '📄 '.($m['fichier']['nom'] ?? 'Document'),
                    'vocal' => '🎤 Note vocale',
                    default => Str::limit((string) $m['body'], 90),
                },
            ];
            $this->dispatch('saisie-focus');
        }
    }

    public function annulerReponse(): void
    {
        $this->reponseA = null;
    }

    public function envoyer(): void
    {
        $texte = trim($this->texte);
        if ($texte === '' || ! $this->salon) {
            return;
        }
        $mentionTous = (bool) ($this->moi['peut_mention_tous'] ?? false) && preg_match('/(?<![\p{L}\p{N}])@tous\b/u', $texte);
        $mentions = collect($this->mentions)->filter(fn ($nom) => str_contains($texte, '@'.$nom))->keys()->values()->all();

        if ($this->publier(['type' => 'texte', 'body' => $texte, 'mentions' => $mentions, 'mention_tous' => $mentionTous])) {
            $this->texte = '';
            $this->mentions = [];
            $this->reponseA = null;
        }
    }

    /** Envoie la photo ou le document choisi (avec une légende facultative). */
    public function envoyerPiece(): void
    {
        if (! $this->salon) {
            return;
        }
        if ($this->photo) {
            $this->validate(['photo' => 'image|mimes:jpg,jpeg,png,webp,gif|max:10240'], [
                'photo.image' => 'Ce fichier n\'est pas une photo.',
                'photo.mimes' => 'Formats acceptés : JPG, PNG, WebP ou GIF.',
                'photo.max' => 'Cette photo dépasse 10 Mo.',
            ]);
            $fichier = $this->photo;
            $type = 'image';
        } elseif ($this->document) {
            $this->validate(['document' => 'file|mimes:pdf|max:10240'], [
                'document.mimes' => 'Seuls les documents PDF sont acceptés.',
                'document.max' => 'Ce document dépasse 10 Mo.',
            ]);
            $fichier = $this->document;
            $type = 'fichier';
        } else {
            return;
        }

        $chemin = $this->stocker($fichier);
        $ok = $this->publier([
            'type' => $type,
            'body' => trim($this->legende) ?: null,
            'fichier' => $chemin,
            'fichier_nom' => $fichier->getClientOriginalName() ?: ($type === 'image' ? 'photo.jpg' : 'document.pdf'),
            'fichier_mime' => $fichier->getMimeType(),
            'fichier_taille' => $fichier->getSize(),
        ]);
        if ($ok) {
            $this->annulerPiece();
        } else {
            Storage::disk('local')->delete($chemin);
        }
    }

    public function annulerPiece(): void
    {
        $this->photo = null;
        $this->document = null;
        $this->legende = '';
        $this->resetValidation();
    }

    /** Note vocale enregistrée dans le navigateur, envoyée dès la fin de l'enregistrement. */
    public function envoyerVocal(int $duree): void
    {
        if (! $this->vocal || ! $this->salon) {
            return;
        }
        $this->validate(['vocal' => 'file|max:6144'], ['vocal.max' => 'Cette note vocale est trop longue.']);
        $mime = $this->vocal->getMimeType();
        if (! str_starts_with((string) $mime, 'audio/') && $mime !== 'video/webm') {
            $this->info = 'Format de note vocale non reconnu.';
            $this->vocal = null;

            return;
        }
        $chemin = $this->stocker($this->vocal);
        $ok = $this->publier([
            'type' => 'vocal',
            'fichier' => $chemin,
            'fichier_nom' => 'note-vocale.'.(str_contains((string) $mime, 'mp4') ? 'm4a' : 'webm'),
            'fichier_mime' => $mime === 'video/webm' ? 'audio/webm' : $mime,
            'fichier_taille' => $this->vocal->getSize(),
            'duree' => max(1, min(180, $duree)),
        ]);
        if (! $ok) {
            Storage::disk('local')->delete($chemin);
        }
        $this->vocal = null;
    }

    private function stocker($fichier): string
    {
        $ext = strtolower($fichier->guessExtension() ?: $fichier->getClientOriginalExtension() ?: 'bin');

        return $fichier->storeAs('communaute/'.now()->format('Y/m'), Str::uuid().'.'.$ext, 'local');
    }

    private function publier(array $donnees): bool
    {
        $this->info = null;
        $r = Api::post('/communaute/salons/'.rawurlencode($this->slug).'/messages', $donnees + [
            'reponse_a_id' => $this->reponseA['id'] ?? null,
        ], Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->info = $r['message'] ?? 'Le message n\'a pas pu être envoyé. Réessayez.';
            if (($r['code'] ?? null) === 'mode_lent') {
                $this->moi['attente'] = (int) ($r['attente'] ?? 0);
            }

            return false;
        }
        $this->messages[] = $r['message'];
        $this->dernierId = max($this->dernierId, (int) $r['message']['id']);
        $this->reponseA = null;
        if ((int) ($this->salon['mode_lent_secondes'] ?? 0) > 0 && ! ($this->moi['moderateur'] ?? false)) {
            $this->moi['attente'] = (int) $this->salon['mode_lent_secondes'];
        }
        $this->limiter();
        $this->dispatch('fil-bas');

        return true;
    }

    // ── Actions sur un message ───────────────────────────────────────────

    public function reagir(int $id, string $emoji): void
    {
        $r = Api::post("/communaute/messages/{$id}/reactions", ['emoji' => $emoji], Api::token());
        if ($r['ok'] ?? false) {
            foreach ($this->messages as $i => $m) {
                if ($m['id'] === $id) {
                    $this->messages[$i]['reactions'] = $r['reactions'];
                }
            }
        } else {
            $this->info = $r['message'] ?? 'Réaction impossible pour le moment.';
        }
    }

    public function commencerEdition(int $id): void
    {
        $m = collect($this->messages)->firstWhere('id', $id);
        if ($m && ($m['peut_modifier'] ?? false)) {
            $this->editionId = $id;
            $this->editionTexte = (string) $m['body'];
        }
    }

    public function annulerEdition(): void
    {
        $this->editionId = null;
        $this->editionTexte = '';
    }

    public function enregistrerEdition(): void
    {
        if (! $this->editionId) {
            return;
        }
        $r = Api::put("/communaute/messages/{$this->editionId}", ['body' => $this->editionTexte], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->info = $r['message'] ?? 'La modification n\'a pas pu être enregistrée.';

            return;
        }
        foreach ($this->messages as $i => $m) {
            if ($m['id'] === $this->editionId) {
                $this->messages[$i]['body'] = trim($this->editionTexte);
                $this->messages[$i]['edite'] = true;
            }
        }
        $this->annulerEdition();
    }

    public function supprimer(int $id): void
    {
        $r = Api::delete("/communaute/messages/{$id}", Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->info = $r['message'] ?? 'Suppression impossible.';

            return;
        }
        foreach ($this->messages as $i => $m) {
            if ($m['id'] === $id) {
                $this->messages[$i] = array_merge($m, ['type' => 'supprime', 'body' => null, 'fichier' => null, 'reactions' => [],
                    'supprime_par_equipe' => ! $m['moi'], 'peut_modifier' => false, 'peut_supprimer' => false, 'peut_signaler' => false]);
            }
        }
    }

    public function ouvrirSignalement(int $id): void
    {
        $this->signalementId = $id;
        $this->motif = '';
    }

    public function fermerSignalement(): void
    {
        $this->signalementId = null;
        $this->motif = '';
    }

    public function envoyerSignalement(): void
    {
        if (! $this->signalementId) {
            return;
        }
        $r = Api::post("/communaute/messages/{$this->signalementId}/signaler", ['motif' => $this->motif], Api::token());
        $this->info = $r['message'] ?? (($r['ok'] ?? false) ? 'Message signalé.' : 'Signalement impossible.');
        $this->fermerSignalement();
    }

    // ── Réglages du salon ────────────────────────────────────────────────

    public function accepterRegles(): void
    {
        if ($this->slug && (Api::post('/communaute/salons/'.rawurlencode($this->slug).'/regles', [], Api::token())['ok'] ?? false)) {
            $this->ouvrir($this->slug);
        }
    }

    public function reglerNotifications(string $niveau): void
    {
        $r = Api::put('/communaute/salons/'.rawurlencode($this->slug).'/notifications', ['niveau' => $niveau], Api::token());
        if ($r['ok'] ?? false) {
            $this->moi['notifications'] = $niveau;
            $this->info = match ($niveau) {
                'tout' => 'Vous serez prévenu de chaque nouveau message de ce salon.',
                'mentions' => 'Vous serez prévenu quand on vous mentionne ou vous répond.',
                default => 'Salon en sourdine : plus aucune notification.',
            };
        }
    }

    // ── Modération (référents et administrateurs) ────────────────────────

    public function sanctionner(int $userId, string $action, ?string $duree = null): void
    {
        $r = Api::post('/communaute/salons/'.rawurlencode($this->slug)."/membres/{$userId}/{$action}", ['duree' => $duree], Api::token());
        $this->info = $r['message'] ?? 'Action impossible.';
    }

    public function basculerLectureSeule(): void
    {
        $r = Api::put('/communaute/salons/'.rawurlencode($this->slug).'/reglages', ['lecture_seule' => ! ($this->salon['lecture_seule'] ?? false)], Api::token());
        if ($r['ok'] ?? false) {
            $this->salon['lecture_seule'] = ! ($this->salon['lecture_seule'] ?? false);
            $this->info = $this->salon['lecture_seule'] ? 'Salon en lecture seule : seuls les référents peuvent écrire.' : 'Le salon est de nouveau ouvert à tous.';
        }
    }

    public function modeLent(int $secondes): void
    {
        $r = Api::put('/communaute/salons/'.rawurlencode($this->slug).'/reglages', ['mode_lent_secondes' => $secondes], Api::token());
        if ($r['ok'] ?? false) {
            $this->salon['mode_lent_secondes'] = $secondes;
            $this->info = $secondes ? 'Mode lent activé.' : 'Mode lent désactivé.';
        } else {
            $this->info = $r['message'] ?? 'Réglage impossible.';
        }
    }

    private function reinitialiserSaisie(): void
    {
        $this->texte = '';
        $this->mentions = [];
        $this->reponseA = null;
        $this->annulerEdition();
        $this->fermerSignalement();
        $this->annulerPiece();
        $this->vocal = null;
        $this->info = null;
        $this->tick = 0;
    }

    public function render()
    {
        return view('livewire.member.communaute');
    }
}
