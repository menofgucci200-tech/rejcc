<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Documents & ressources : bibliothèque du réseau. Les fichiers sont privés
 * (stockés par le site, servis seulement après le contrôle d'accès de cette
 * API) ; l'accès se règle par document ; les membres abonnés peuvent
 * proposer des documents, publiés après validation de l'équipe.
 */
class DocumentController extends Controller
{
    private function categories()
    {
        return DocumentCategory::orderBy('ordre')->orderBy('nom')->get(['id', 'nom', 'icone', 'ordre']);
    }

    private function groupes()
    {
        return Group::orderBy('ordre')->orderBy('id')->get(['id', 'name'])->map(fn ($g) => ['id' => $g->id, 'nom' => $g->name])->values();
    }

    protected function payload(Document $d, ?User $moi): array
    {
        $raison = $moi ? $d->raisonRefus($moi) : null;
        $estAuteur = $moi && $d->auteur_id === $moi->id;
        $data = [
            'id' => $d->id,
            'title' => $d->title,
            'description' => $d->description,
            'category' => $d->categorie?->nom ?? $d->category,
            'categorie' => $d->categorie ? $d->categorie->only(['id', 'nom', 'icone']) : null,
            'type' => $d->typeLabel(),
            'taille' => $d->tailleLisible(),
            'size' => $d->tailleLisible(),
            'acces' => $d->acces,
            'acces_label' => $d->acces === 'groupe' ? 'Groupe « '.($d->groupe?->name ?? '?').' »' : (Document::ACCES[$d->acces] ?? $d->acces),
            'groupe' => $d->groupe ? ['id' => $d->groupe->id, 'nom' => $d->groupe->name] : null,
            'verrouille' => $raison !== null,
            'raison' => $raison,
            'a_fichier' => (bool) $d->fichier,
            'externe' => ! $d->fichier && $d->url && str_starts_with($d->url, 'http'),
            'disponible' => (bool) $d->fichier || ($d->url && str_starts_with($d->url, 'http')),
            'nouveau' => $d->publie_at && $d->publie_at->gt(now()->subDays(14)),
            'contributeur' => $d->auteur && $d->auteur->role !== 'admin' ? trim($d->auteur->prenom.' '.$d->auteur->nom) : null,
            'vues' => (int) $d->vues,
            'telechargements' => (int) $d->telechargements,
            'publie_at' => $d->publie_at?->toIso8601String(),
            'fichier_maj_at' => $d->fichier_maj_at?->toIso8601String(),
            'created_at' => $d->created_at?->toIso8601String(),
            // Ancien champ lu par l'accueil membre : lien vers la plateforme, jamais le fichier brut.
            'url' => null,
        ];
        if ($estAuteur || $moi?->role === 'admin') {
            $data['statut'] = $d->statut;
            $data['motif'] = $d->motif;
            $data['mine'] = $estAuteur;
        }
        if ($moi?->role === 'admin') {
            $data['fichier'] = $d->fichier;
            $data['fichier_nom'] = $d->fichier_nom;
            $data['lien'] = $d->fichier ? null : $d->url;
            $data['auteur'] = $d->auteur ? trim($d->auteur->prenom.' '.$d->auteur->nom) : null;
        }

        return $data;
    }

    private function avecRelations()
    {
        return Document::with(['categorie:id,nom,icone', 'groupe:id,name', 'auteur:id,prenom,nom,role']);
    }

    // ------------------------------------------------------------------
    // Espace membre
    // ------------------------------------------------------------------

    /** GET /documents?q=&categorie=&tri= — documents publiés + mes propositions. */
    public function index(Request $request)
    {
        $moi = $request->user();
        $query = $this->avecRelations()->select('documents.*')
            ->leftJoin('document_categories', 'document_categories.id', '=', 'documents.category_id')
            ->publies();
        RechercheMots::appliquer($query, (string) $request->query('q', ''), [
            'documents.title', 'documents.description', 'documents.fichier_nom', 'document_categories.nom',
        ]);
        if ($cat = (int) $request->query('categorie')) {
            $query->where('documents.category_id', $cat);
        }
        if ($groupe = (int) $request->query('groupe')) {
            $query->where('documents.group_id', $groupe);
        }
        match ($request->query('tri')) {
            'titre' => $query->orderBy('documents.title'),
            'populaires' => $query->orderByDesc('documents.telechargements')->orderByDesc('documents.vues'),
            default => $query->orderByDesc('documents.publie_at')->orderByDesc('documents.id'),
        };
        $docs = $query->get();

        $comptes = Document::publies()->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');

        return response()->json([
            'ok' => true,
            'documents' => $docs->map(fn ($d) => $this->payload($d, $moi))->values(),
            'categories' => $this->categories()->map(fn ($c) => $c->only(['id', 'nom', 'icone']) + ['nombre' => (int) ($comptes[$c->id] ?? 0)])->values(),
            'mes_propositions' => $moi->role === 'admin' ? [] : $this->avecRelations()->where('auteur_id', $moi->id)
                ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 WHEN 'refuse' THEN 1 ELSE 2 END")->latest()->get()->map(fn ($d) => $this->payload($d, $moi))->values(),
            'peut_proposer' => $moi->hasActiveSubscription(),
        ]);
    }

    /**
     * GET /documents/{id}/acces?action=vue|telechargement — contrôle d'accès
     * avant que le site serve le fichier privé (ou redirige vers le lien).
     */
    public function acces(Request $request, int $id)
    {
        $moi = $request->user();
        $d = Document::with('groupe:id,name')->find($id);
        if (! $d || ($d->statut !== 'publie' && $d->auteur_id !== $moi->id && $moi->role !== 'admin')) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        if ($raison = $d->raisonRefus($moi)) {
            return response()->json(['ok' => false, 'code' => 'acces_refuse', 'message' => $raison], 403);
        }
        if ($d->statut === 'publie' && $moi->role !== 'admin') {
            $d->increment($request->query('action') === 'telechargement' ? 'telechargements' : 'vues');
        }

        return response()->json(['ok' => true, 'document' => [
            'id' => $d->id, 'title' => $d->title, 'fichier' => $d->fichier, 'fichier_nom' => $d->fichier_nom,
            'mime' => $d->mime, 'url' => $d->fichier ? null : $d->url,
        ]]);
    }

    private function regles(bool $admin): array
    {
        return [
            'title' => 'required|string|min:3|max:200',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|integer|exists:document_categories,id',
            'fichier' => 'nullable|string|max:500|required_without:url',
            'fichier_nom' => 'nullable|string|max:200',
            'mime' => 'nullable|string|max:120',
            'octets' => 'nullable|integer|min:0',
            'url' => 'nullable|url|max:500|required_without:fichier',
        ] + ($admin ? [
            'acces' => ['nullable', Rule::in(array_keys(Document::ACCES))],
            'group_id' => 'nullable|integer|exists:groups,id|required_if:acces,groupe',
            'notifier' => 'nullable|boolean',
        ] : []);
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre au document.',
            'title.min' => 'Le titre est trop court.',
            'category_id.required' => 'Choisissez une catégorie.',
            'category_id.exists' => 'Choisissez une catégorie.',
            'fichier.required_without' => 'Ajoutez un fichier ou collez un lien.',
            'url.required_without' => 'Ajoutez un fichier ou collez un lien.',
            'url.url' => 'Le lien doit être une adresse complète (https://…).',
            'group_id.required_if' => 'Choisissez le groupe qui aura accès au document.',
        ];
    }

    /** POST /documents — un membre abonné propose un document (validé par l'équipe). */
    public function proposer(Request $request)
    {
        $moi = $request->user();
        $v = Validator::make($request->all(), $this->regles(false), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        // Le fichier doit avoir été déposé dans le dossier du membre (jamais le fichier d'un autre).
        $fichier = (string) $request->input('fichier');
        if ($fichier !== '' && (! str_starts_with($fichier, 'documents/propositions/'.$moi->id.'/') || str_contains($fichier, '..'))) {
            return response()->json(['ok' => false, 'message' => 'Fichier invalide.'], 422);
        }
        if (Document::where('auteur_id', $moi->id)->where('statut', 'en_attente')->count() >= 5) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà 5 documents en attente de validation.'], 422);
        }
        $d = Document::create($v->validated() + ['auteur_id' => $moi->id, 'statut' => 'en_attente', 'acces' => 'tous', 'fichier_maj_at' => now()]);

        return response()->json(['ok' => true, 'document' => $this->payload($d->load('categorie'), $moi)], 201);
    }

    /** DELETE /documents/{id} — le membre retire sa proposition (non publiée). */
    public function retirerProposition(Request $request, int $id)
    {
        $d = Document::where('auteur_id', $request->user()->id)->where('statut', '!=', 'publie')->find($id);
        if (! $d) {
            return response()->json(['ok' => false, 'message' => 'Proposition introuvable.'], 404);
        }
        $fichier = $d->fichier;
        $d->delete();

        return response()->json(['ok' => true, 'fichier' => $fichier]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex(Request $request)
    {
        $moi = $request->user();
        $statut = (string) $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));
        $docs = $this->avecRelations()
            ->when($statut !== '', fn ($w) => $w->where('statut', $statut))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')->get();

        return response()->json([
            'ok' => true,
            'documents' => $docs->map(fn ($d) => $this->payload($d, $moi))->values(),
            'categories' => $this->categories()->map(fn ($c) => $c->only(['id', 'nom', 'icone', 'ordre']) + ['nombre' => $c->documents()->count()])->values(),
            'groupes' => $this->groupes(),
            'acces' => Document::ACCES,
            'compteurs' => Document::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut'),
            'stats' => ['vues' => (int) Document::sum('vues'), 'telechargements' => (int) Document::sum('telechargements')],
        ]);
    }

    private function donneesAdmin(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $v = Validator::make($request->all(), $this->regles(true), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $d['acces'] ??= 'tous';
        if ($d['acces'] !== 'groupe') {
            $d['group_id'] = null;
        }
        if (! empty($d['fichier'])) {
            $d['url'] = null;
        } else {
            $d['fichier'] = $d['fichier_nom'] = $d['mime'] = null;
            $d['octets'] = null;
        }
        $d['category'] = DocumentCategory::find($d['category_id'])?->nom;

        return $d;
    }

    /** Membres concernés par un document (selon son accès). */
    private function destinataires(Document $d)
    {
        $q = User::where('is_active', true)->whereIn('role', ['member', 'mentor']);

        return match ($d->acces) {
            'mentors' => $q->where('role', 'mentor')->pluck('id'),
            'groupe' => $q->whereHas('groups', fn ($g) => $g->whereKey($d->group_id))->pluck('id'),
            'abonnes' => $q->get()->filter(fn (User $u) => $u->hasActiveSubscription())->pluck('id'),
            default => $q->pluck('id'),
        };
    }

    private function annoncer(Document $d): int
    {
        $ids = $this->destinataires($d)->reject(fn ($id) => $id === $d->auteur_id)->values();
        $now = now();
        MemberNotification::insert($ids->map(fn ($id) => [
            'user_id' => $id, 'type' => 'info', 'title' => 'Nouveau document : '.$d->title,
            'body' => ($d->categorie?->nom ? $d->categorie->nom.' · ' : '').($d->description ? \Illuminate\Support\Str::limit($d->description, 120) : 'Consultez-le dans Documents & ressources.'),
            'link' => "/espace-membre/documents?document={$d->id}", 'created_at' => $now, 'updated_at' => $now,
        ])->all());

        return $ids->count();
    }

    public function adminStore(Request $request)
    {
        $d = $this->donneesAdmin($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $notifier = (bool) ($d['notifier'] ?? false);
        unset($d['notifier']);
        $doc = Document::create($d + ['statut' => 'publie', 'auteur_id' => $request->user()->id, 'publie_at' => now(), 'fichier_maj_at' => now()]);
        $notifies = $notifier ? $this->annoncer($doc->load('categorie')) : 0;

        return response()->json(['ok' => true, 'notifies' => $notifies, 'document' => $this->payload($doc->fresh(['categorie', 'groupe', 'auteur']), $request->user())], 201);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $doc = Document::find($id);
        if (! $doc) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        $d = $this->donneesAdmin($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $notifier = (bool) ($d['notifier'] ?? false);
        unset($d['notifier']);
        $ancienFichier = $doc->fichier;
        $nouveauFichier = ($d['fichier'] ?? null) !== $doc->fichier || ($d['url'] ?? null) !== $doc->url;
        $doc->fill($d);
        if ($nouveauFichier) {
            $doc->fichier_maj_at = now();
        }
        $doc->save();
        $notifies = $notifier && $doc->statut === 'publie' ? $this->annoncer($doc->load('categorie')) : 0;

        return response()->json([
            'ok' => true, 'notifies' => $notifies,
            // Ancien fichier remplacé : le site peut le supprimer de son stockage.
            'ancien_fichier' => $ancienFichier && $ancienFichier !== $doc->fichier ? $ancienFichier : null,
            'document' => $this->payload($doc->fresh(['categorie', 'groupe', 'auteur']), $request->user()),
        ]);
    }

    /** POST /admin/documents/{id}/decision — publier ou refuser une proposition de membre. */
    public function decision(Request $request, int $id)
    {
        $doc = Document::with('categorie')->find($id);
        if (! $doc) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        $v = Validator::make($request->all(), [
            'decision' => 'required|in:publier,refuser',
            'motif' => 'nullable|string|max:1000|required_if:decision,refuser',
            'notifier' => 'nullable|boolean',
        ], ['motif.required_if' => 'Indiquez au membre la raison du refus.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $publier = $request->input('decision') === 'publier';
        $doc->update($publier
            ? ['statut' => 'publie', 'motif' => null, 'publie_at' => now()]
            : ['statut' => 'refuse', 'motif' => trim((string) $request->input('motif'))]);
        if ($doc->auteur_id) {
            MemberNotification::create([
                'user_id' => $doc->auteur_id, 'type' => 'info',
                'title' => ($publier ? 'Document publié : ' : 'Document non publié : ').$doc->title,
                'body' => $publier ? 'Merci pour votre contribution ! Votre document est désormais disponible pour les membres du réseau.'
                    : 'Votre proposition n\'a pas été retenue. Motif : '.$doc->motif,
                'link' => $publier ? "/espace-membre/documents?document={$doc->id}" : '/espace-membre/documents?onglet=propositions',
            ]);
        }
        $notifies = $publier && $request->boolean('notifier') ? $this->annoncer($doc) : 0;

        return response()->json(['ok' => true, 'notifies' => $notifies]);
    }

    public function adminDestroy(int $id)
    {
        $doc = Document::find($id);
        $fichier = $doc?->fichier;
        $doc?->delete();

        return response()->json(['ok' => true, 'fichier' => $fichier]);
    }

    // ── Catégories ───────────────────────────────────────────────────

    public function creerCategorie(Request $request)
    {
        $v = Validator::make($request->all(), ['nom' => 'required|string|min:2|max:80|unique:document_categories,nom', 'icone' => 'nullable|string|max:40'],
            ['nom.unique' => 'Cette catégorie existe déjà.', 'nom.required' => 'Donnez un nom à la catégorie.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $c = DocumentCategory::create($v->validated() + ['ordre' => (int) DocumentCategory::max('ordre') + 1]);

        return response()->json(['ok' => true, 'categorie' => $c], 201);
    }

    public function modifierCategorie(Request $request, int $id)
    {
        $c = DocumentCategory::find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Catégorie introuvable.'], 404);
        }
        $v = Validator::make($request->all(), ['nom' => 'required|string|min:2|max:80|unique:document_categories,nom,'.$id, 'icone' => 'nullable|string|max:40'],
            ['nom.unique' => 'Cette catégorie existe déjà.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $c->update($v->validated());
        Document::where('category_id', $c->id)->update(['category' => $c->nom]);

        return response()->json(['ok' => true]);
    }

    public function supprimerCategorie(int $id)
    {
        $c = DocumentCategory::withCount('documents')->find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Catégorie introuvable.'], 404);
        }
        if ($c->documents_count > 0) {
            return response()->json(['ok' => false, 'message' => "Cette catégorie contient {$c->documents_count} document(s) : déplacez-les avant de la supprimer."], 422);
        }
        $c->delete();

        return response()->json(['ok' => true]);
    }
}
