<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationPublication;
use App\Models\DemandeCommunication;
use App\Support\CalendrierEditorial;
use App\Support\DemandesCommunication;
use App\Support\Departements;
use App\Support\PlanDiffusion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Demandes de communication. Côté demandeur (tout compte d'administration) :
 * dépôt, suivi et annulation de ses propres demandes. Côté Communication
 * (rubrique « communication ») : liste de toutes les demandes, statuts,
 * réponses, planification. Les pièces jointes sont déposées par le
 * dashboard dans le stockage privé partagé ; l'API ne garde que leurs
 * informations et contrôle l'accès.
 */
class DemandeCommunicationController extends Controller
{
    /** GET /admin/plan-diffusion : plan de diffusion (formulaire, page Com, calendrier). */
    public function plan()
    {
        return response()->json(['ok' => true, 'plan' => PlanDiffusion::tableau()]);
    }

    // ── Demandeur ───────────────────────────────────────────────────────

    /** GET /admin/mes-demandes-communication */
    public function mesDemandes(Request $request)
    {
        $me = $request->user();
        $demandes = DemandeCommunication::where('demandeur_id', $me->id)->latest()->get();

        return response()->json([
            'ok' => true,
            'demandes' => $demandes->map(fn ($d) => $this->ligne($d))->values(),
            'departement' => DemandesCommunication::departementDe($me),
            'departement_label' => DemandesCommunication::libelleDepartement(DemandesCommunication::departementDe($me)),
            'contact' => trim(implode(' · ', array_filter([trim("{$me->prenom} {$me->nom}"), $me->telephone, $me->email]))),
        ]);
    }

    /** GET /admin/mes-demandes-communication/{id} */
    public function maDemande(Request $request, int $id)
    {
        $d = DemandeCommunication::where('demandeur_id', $request->user()->id)->findOrFail($id);

        return response()->json(['ok' => true, 'demande' => $this->fiche($d)]);
    }

    /** POST /admin/mes-demandes-communication */
    public function store(Request $request)
    {
        $me = $request->user();
        $d = $request->validate([
            'type' => ['required', Rule::in(array_keys(PlanDiffusion::TYPES))],
            'titre' => 'required|string|min:3|max:150',
            'message' => 'required|string|min:10|max:5000',
            'date_souhaitee' => 'required|date|after_or_equal:today',
            'date_evenement' => 'nullable|date',
            'canaux' => 'required|array|min:1',
            'canaux.*' => ['string', 'distinct', Rule::in(array_keys(PlanDiffusion::CANAUX))],
            'contact' => 'required|string|min:3|max:150',
            'fichiers' => 'nullable|array|max:'.DemandesCommunication::FICHIERS_MAX,
            'fichiers.*.chemin' => ['required', 'string', 'regex:'.DemandesCommunication::CHEMIN_FICHIER],
            'fichiers.*.nom' => 'required|string|max:200',
            'fichiers.*.mime' => ['nullable', 'string', Rule::in(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])],
            'fichiers.*.octets' => 'nullable|integer|min:0|max:'.DemandesCommunication::FICHIER_MAX_OCTETS,
        ], [
            'type.required' => 'Choisissez le type d\'information.',
            'titre.required' => 'Donnez un titre à votre demande.',
            'message.required' => 'Décrivez le message à faire passer.',
            'message.min' => 'Décrivez le message à faire passer (10 caractères au moins).',
            'date_souhaitee.required' => 'Indiquez la date de publication souhaitée.',
            'date_souhaitee.after_or_equal' => 'La date de publication souhaitée ne peut pas être passée.',
            'canaux.required' => 'Choisissez au moins un canal.',
            'canaux.min' => 'Choisissez au moins un canal.',
            'contact.required' => 'Indiquez comment vous joindre.',
            'fichiers.max' => 'Joignez 3 fichiers au plus.',
            'fichiers.*.chemin.regex' => 'Pièce jointe refusée.',
        ]);

        $demande = new DemandeCommunication([
            'demandeur_id' => $me->id,
            'departement' => DemandesCommunication::departementDe($me),
            'type' => $d['type'],
            'titre' => trim($d['titre']),
            'message' => trim($d['message']),
            'date_souhaitee' => $d['date_souhaitee'],
            'date_evenement' => $d['date_evenement'] ?? null,
            'canaux' => array_values(array_intersect(array_keys(PlanDiffusion::CANAUX), $d['canaux'])),
            'fichiers' => collect($d['fichiers'] ?? [])->map(fn ($f) => [
                'chemin' => $f['chemin'],
                'nom' => str_replace(['"', '\\', '/', "\r", "\n"], '', $f['nom']),
                'mime' => $f['mime'] ?? null,
                'octets' => $f['octets'] ?? null,
            ])->values()->all(),
            'contact' => trim($d['contact']),
        ]);
        $demande->changerStatut('nouvelle', $me);
        $demande->save();

        DemandesCommunication::notifierEquipe($demande);

        return response()->json([
            'ok' => true,
            'demande' => $this->fiche($demande),
            'avertissement_delai' => PlanDiffusion::avertissementDelai($demande->date_souhaitee),
        ], 201);
    }

    /** POST /admin/mes-demandes-communication/{id}/annuler : tant que la Communication ne l'a pas acceptée. */
    public function annuler(Request $request, int $id)
    {
        $me = $request->user();
        $d = DemandeCommunication::where('demandeur_id', $me->id)->findOrFail($id);
        if ($d->statut !== 'nouvelle') {
            return response()->json(['ok' => false, 'message' => 'Cette demande a déjà été prise en charge par la Communication : contactez-la pour l\'annuler.'], 422);
        }
        $d->changerStatut('annulee', $me);
        $d->save();

        return response()->json(['ok' => true, 'demande' => $this->fiche($d)]);
    }

    /**
     * GET /admin/demandes-communication/{id}/fichiers/{n} : chemin d'une pièce
     * jointe, pour le demandeur ou la Communication.
     */
    public function fichier(Request $request, int $id, int $n)
    {
        $me = $request->user();
        $d = DemandeCommunication::findOrFail($id);
        if ((int) $d->demandeur_id !== (int) $me->id &&! (DemandesCommunication::traite($me) && DemandesCommunication::visibles($me)->whereKey($d->id)->exists())) {
            return response()->json(['ok' => false, 'message' => 'Cette pièce jointe ne vous est pas accessible.'], 403);
        }
        $f = ($d->fichiers ?? [])[$n] ?? null;
        if (! $f || ! preg_match(DemandesCommunication::CHEMIN_FICHIER, (string) ($f['chemin'] ?? ''))) {
            return response()->json(['ok' => false, 'message' => 'Pièce jointe introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'fichier' => ['chemin' => $f['chemin'], 'nom' => $f['nom'] ?? basename($f['chemin']), 'mime' => $f['mime'] ?? null]]);
    }

    // ── Communication ───────────────────────────────────────────────────

    /** GET /admin/demandes-communication?statut=&departement=&q= — urgentes (date souhaitée la plus proche) en premier. */
    public function index(Request $request)
    {
        $q = DemandesCommunication::visibles($request->user())->with('demandeur:id,prenom,nom,email');
        $statut = (string) $request->query('statut', 'ouvertes');
        if ($statut === 'ouvertes') {
            $q->whereIn('statut', DemandesCommunication::STATUTS_OUVERTS);
        } elseif (isset(DemandesCommunication::STATUTS[$statut])) {
            $q->where('statut', $statut);
        }
        if (($dep = (string) $request->query('departement', '')) !== '') {
            $dep === 'administration' ? $q->whereNull('departement') : $q->where('departement', $dep);
        }
        if (($texte = trim((string) $request->query('q', ''))) !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $texte).'%';
            $q->where(fn ($w) => $w->where('titre', 'like', $like)->orWhere('message', 'like', $like)->orWhere('contact', 'like', $like));
        }
        // Les demandes ouvertes d'abord, puis par date souhaitée (les plus proches en premier).
        $ouvertes = "'".implode("','", DemandesCommunication::STATUTS_OUVERTS)."'";
        $q->orderByRaw("case when statut in ({$ouvertes}) then 0 else 1 end")->orderBy('date_souhaitee')->orderBy('id');
        $page = $q->paginate(min(100, max(10, $request->integer('par_page', 30))));

        return response()->json([
            'ok' => true,
            'demandes' => collect($page->items())->map(fn ($d) => $this->ligne($d)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'compteurs' => DemandesCommunication::visibles($request->user())->selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut')->map(fn ($n) => (int) $n),
            'statuts' => DemandesCommunication::STATUTS,
            'departements' => ['administration' => 'Administration'] + collect(Departements::LISTE)->map(fn ($d) => $d['label'])->all(),
        ]);
    }

    /** GET /admin/demandes-communication/{id} */
    public function show(Request $request, int $id)
    {
        return response()->json(['ok' => true, 'demande' => $this->fiche(DemandesCommunication::visibles($request->user())->findOrFail($id))]);
    }

    /**
     * PUT /admin/demandes-communication/{id}/statut {statut, motif?, commentaire?,
     * date_publication_prevue?, lien_publication?} : le demandeur est prévenu par e-mail.
     */
    public function changerStatut(Request $request, int $id)
    {
        $d = DemandesCommunication::visibles($request->user())->findOrFail($id);
        $possibles = DemandesCommunication::TRANSITIONS[$d->statut] ?? [];
        $v = $request->validate([
            'statut' => ['required', Rule::in(array_keys(DemandesCommunication::STATUTS))],
            'motif' => 'required_if:statut,refusee|nullable|string|min:3|max:2000',
            'commentaire' => 'nullable|string|max:2000',
            'date_publication_prevue' => 'nullable|date',
            'lien_publication' => 'nullable|url:http,https|max:500',
            'publication_id' => 'nullable|integer|min:1',
        ], [
            'motif.required_if' => 'Indiquez le motif du refus : il sera transmis au demandeur.',
            'motif.min' => 'Indiquez le motif du refus : il sera transmis au demandeur.',
            'lien_publication.url' => 'Le lien de la publication doit commencer par https://',
        ]);
        if (! in_array($v['statut'], $possibles, true)) {
            return response()->json(['ok' => false, 'message' => 'Impossible de passer une demande « '.$d->libelleStatut().' » à « '.DemandesCommunication::libelleStatut($v['statut']).' ».'], 422);
        }

        $texte = $v['statut'] === 'refusee' ? trim($v['motif']) : (trim((string) ($v['commentaire'] ?? '')) ?: null);
        $d->changerStatut($v['statut'], $request->user(), $texte);
        if ($v['statut'] === 'refusee') {
            $d->motif_refus = $texte;
        } elseif ($v['statut'] === 'acceptee') {
            $d->motif_refus = null;
        }
        foreach (['date_publication_prevue', 'lien_publication', 'publication_id'] as $champ) {
            if (array_key_exists($champ, $v) && $v[$champ] !== null) {
                $d->{$champ} = $v[$champ];
            }
        }
        $d->save();

        // Publiée : la publication liée du calendrier éditorial l'est aussi (avec son lien).
        if ($v['statut'] === 'publiee' && $d->publication_id && class_exists(CommunicationPublication::class)
            && ($p = CommunicationPublication::find($d->publication_id)) && $p->statut !== 'publie') {
            $p->update([
                'statut' => 'publie', 'publie_at' => now(), 'publie_par' => $request->user()->id,
                'liens' => array_values(array_unique(array_filter([...($p->liens ?? []), $d->lien_publication]))),
            ]);
        }

        DemandesCommunication::notifierDemandeur($d, $v['statut'], $texte);

        return response()->json(['ok' => true, 'demande' => $this->fiche($d)]);
    }

    /** POST /admin/demandes-communication/{id}/commentaires {texte} : réponse au demandeur, par e-mail. */
    public function commenter(Request $request, int $id)
    {
        $d = DemandesCommunication::visibles($request->user())->findOrFail($id);
        $v = $request->validate(['texte' => 'required|string|min:2|max:2000'], ['texte.required' => 'Écrivez votre message au demandeur.']);
        $d->commenter(trim($v['texte']), $request->user());
        $d->save();

        DemandesCommunication::notifierDemandeur($d, 'commentaire', trim($v['texte']));

        return response()->json(['ok' => true, 'demande' => $this->fiche($d)]);
    }

    /**
     * PUT /admin/demandes-communication/{id}/planification {date_publication_prevue?,
     * publication_id?, lien_publication?} : informations de publication, sans
     * changer le statut (le calendrier éditorial y renvoie sa publication).
     */
    public function planification(Request $request, int $id)
    {
        $d = DemandesCommunication::visibles($request->user())->findOrFail($id);
        $v = $request->validate([
            'date_publication_prevue' => 'sometimes|nullable|date',
            'publication_id' => 'sometimes|nullable|integer|min:1',
            'lien_publication' => 'sometimes|nullable|url:http,https|max:500',
        ], ['lien_publication.url' => 'Le lien de la publication doit commencer par https://']);
        $d->fill($v)->save();

        return response()->json(['ok' => true, 'demande' => $this->fiche($d)]);
    }

    /**
     * POST /admin/demandes-communication/{id}/planifier {date, heure?, responsable_id?, commentaire?} :
     * crée la publication correspondante dans le calendrier éditorial (avec son
     * demande_id), passe la demande à « planifiée » et prévient le demandeur.
     */
    public function planifier(Request $request, int $id)
    {
        $d = DemandesCommunication::visibles($request->user())->findOrFail($id);
        // Une demande nouvelle peut être planifiée directement (accueil de la Com) : cela vaut
        // acceptation, inscrite à l'historique, avec un seul e-mail au demandeur (« planifiée »).
        if (! in_array($d->statut, ['nouvelle', 'acceptee', 'planifiee'], true)) {
            return response()->json(['ok' => false, 'message' => 'Cette demande ne peut plus être planifiée.'], 422);
        }
        if (! class_exists(CommunicationPublication::class)) {
            return response()->json(['ok' => false, 'message' => 'Le calendrier éditorial n\'est pas disponible.'], 503);
        }
        $cal = CalendrierEditorial::class;
        $v = $request->validate([
            'date' => 'required|date',
            'heure' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'responsable_id' => ['nullable', 'integer', Rule::in($cal::equipe()->pluck('id')->all())],
            'commentaire' => 'nullable|string|max:2000',
        ], [
            'date.required' => 'Choisissez la date de publication.',
            'heure.regex' => 'L\'heure doit être au format HH:MM.',
            'responsable_id.in' => 'Ce compte ne fait pas partie du département Communication.',
        ]);

        $type = DemandesCommunication::TYPES_CALENDRIER[$d->type] ?? 'autre';
        $type = isset($cal::TYPES[$type]) ? $type : 'autre';
        // Canaux du calendrier : « réseaux sociaux » se détaille en réseaux.
        $canaux = collect($d->canaux ?? [])->flatMap(fn ($c) => $c === 'reseaux' ? $cal::RESEAUX_SOCIAUX : [$c])
            ->filter(fn ($c) => isset($cal::CANAUX[$c]))->unique()->values()->all();
        $donnees = [
            'date' => $v['date'],
            'heure' => $v['heure'] ?? null,
            'titre' => mb_substr($d->titre, 0, 200),
            'type' => $type,
            'canaux' => $canaux,
            'notes' => mb_substr('Demande n° '.$d->id.' du département '.$d->libelleDepartement().' ('.$d->contact.").\n\n".$d->message, 0, 5000),
            'responsable_id' => $v['responsable_id'] ?? null,
            'demande_id' => $d->id,
        ];
        // Sans responsable choisi : celui du tour de rôle du jour.
        if (empty($donnees['responsable_id']) && ($tour = $cal::tourDu($v['date']))) {
            $donnees['responsable_id'] = $tour->responsable_id;
            $donnees['responsable'] = $tour->responsable_id ? null : $tour->responsable;
        }

        $publication = CommunicationPublication::where('demande_id', $d->id)->whereKey($d->publication_id)->first();
        if ($publication) {
            $publication->update(['date' => $donnees['date'], 'heure' => $donnees['heure'], 'responsable_id' => $donnees['responsable_id'], 'rappel_veille_at' => null, 'rappel_jour_at' => null]);
        } else {
            $publication = CommunicationPublication::create($donnees + ['statut' => 'prevu', 'cree_par' => $request->user()->id]);
        }

        $texte = trim((string) ($v['commentaire'] ?? '')) ?: null;
        $d->forceFill(['publication_id' => $publication->id, 'date_publication_prevue' => $v['date']]);
        $etaitPlanifiee = $d->statut === 'planifiee';
        if ($d->statut === 'nouvelle') {
            $d->changerStatut('acceptee', $request->user());
        }
        $d->changerStatut('planifiee', $request->user(), $texte);
        $d->save();
        DemandesCommunication::notifierDemandeur($d, 'planifiee', $texte);

        return response()->json(['ok' => true, 'demande' => $this->fiche($d), 'publication_id' => $publication->id, 'replanifiee' => $etaitPlanifiee]);
    }

    /** GET /admin/demandes-communication/resume : bloc de l'espace Communication et futur cockpit. */
    public function resume(Request $request)
    {
        $visibles = fn () => DemandesCommunication::visibles($request->user());
        $ouvertes = $visibles()->with('demandeur:id,prenom,nom,email')
            ->whereIn('statut', ['nouvelle', 'acceptee'])->orderBy('date_souhaitee')->get();
        $urgentes = $ouvertes->filter(fn ($d) => DemandesCommunication::estUrgente($d));
        $aMontrer = $ouvertes->filter(fn ($d) => $d->statut === 'nouvelle' || DemandesCommunication::estUrgente($d))->take(6);

        return response()->json(['ok' => true,
            'chiffres' => [
                'nouvelles' => $ouvertes->where('statut', 'nouvelle')->count(),
                'urgentes' => $urgentes->count(),
                'en_cours' => $visibles()->whereIn('statut', ['acceptee', 'planifiee'])->count(),
                'publiees_mois' => $visibles()->where('statut', 'publiee')->where('statut_at', '>=', now()->startOfMonth())->count(),
            ],
            'demandes' => $aMontrer->map(fn ($d) => $this->ligne($d))->values(),
        ]);
    }

    // ── Présentation ────────────────────────────────────────────────────

    private function ligne(DemandeCommunication $d): array
    {
        $d->loadMissing('demandeur:id,prenom,nom,email');
        $reponse = $d->derniereReponse();

        return [
            'id' => $d->id,
            'titre' => $d->titre,
            'type' => $d->type,
            'type_label' => $d->libelleType(),
            'departement' => $d->departement,
            'departement_label' => $d->libelleDepartement(),
            'demandeur' => $d->demandeur ? (trim("{$d->demandeur->prenom} {$d->demandeur->nom}") ?: $d->demandeur->email) : null,
            'date_souhaitee' => $d->date_souhaitee?->toDateString(),
            'date_evenement' => $d->date_evenement?->toDateString(),
            'jours_avant' => PlanDiffusion::joursAvant($d->date_souhaitee),
            'niveau_delai' => PlanDiffusion::niveauDelai($d->date_souhaitee),
            'urgente' => DemandesCommunication::estUrgente($d),
            'canaux' => $d->canaux ?? [],
            'canaux_labels' => array_map(PlanDiffusion::libelleCanal(...), $d->canaux ?? []),
            'nb_fichiers' => count($d->fichiers ?? []),
            'statut' => $d->statut,
            'statut_label' => $d->libelleStatut(),
            'statut_at' => $d->statut_at?->toIso8601String(),
            'annulable' => $d->statut === 'nouvelle',
            'derniere_reponse' => $reponse ? ['texte' => $reponse['texte'], 'at' => $reponse['at'] ?? null, 'par' => $reponse['par'] ?? null] : null,
            'date_publication_prevue' => $d->date_publication_prevue?->toDateString(),
            'created_at' => $d->created_at?->toIso8601String(),
        ];
    }

    /** Publication du calendrier éditorial liée à la demande (date, statut), si elle existe encore. */
    private function publication(DemandeCommunication $d): ?array
    {
        if (! $d->publication_id || ! class_exists(CommunicationPublication::class)) {
            return null;
        }
        $p = CommunicationPublication::find($d->publication_id);

        return $p ? [
            'id' => $p->id,
            'date' => $p->date?->toDateString(),
            'heure' => $p->heure,
            'statut' => $p->statut,
            'statut_label' => CalendrierEditorial::STATUTS[$p->statut] ?? $p->statut,
            'liens' => array_values($p->liens ?? []),
        ] : null;
    }

    private function fiche(DemandeCommunication $d): array
    {
        return $this->ligne($d) + [
            'message' => $d->message,
            'contact' => $d->contact,
            'demandeur_email' => $d->demandeur?->email,
            'fichiers' => collect($d->fichiers ?? [])->map(fn ($f, $i) => [
                'index' => $i,
                'nom' => $f['nom'] ?? basename($f['chemin']),
                'mime' => $f['mime'] ?? null,
                'octets' => $f['octets'] ?? null,
                'image' => str_starts_with((string) ($f['mime'] ?? ''), 'image/'),
            ])->values()->all(),
            'canaux_recommandes' => PlanDiffusion::canaux($d->type),
            'motif_refus' => $d->motif_refus,
            'publication_id' => $d->publication_id,
            'publication' => $this->publication($d),
            'lien_publication' => $d->lien_publication,
            'historique' => collect($d->historique ?? [])->map(fn ($h) => $h + [
                'label' => ($h['type'] ?? 'statut') === 'commentaire' ? 'Message de la Communication' : DemandesCommunication::libelleStatut($h['statut'] ?? null),
            ])->values()->all(),
            'transitions' => collect(DemandesCommunication::TRANSITIONS[$d->statut] ?? [])
                ->mapWithKeys(fn ($s) => [$s => DemandesCommunication::libelleStatut($s)])->all(),
        ];
    }
}
