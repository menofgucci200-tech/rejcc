<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\JobAlert;
use App\Models\MemberNotification;
use App\Models\Opportunity;
use App\Models\OpportunityApplication;
use App\Models\User;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Emploi & Stage : les membres abonnés proposent des offres (emploi, stage,
 * alternance, freelance, mission), validées par l'équipe avant publication ;
 * l'auteur suit ses offres (pourvue, clôturée, prolongée).
 */
class OpportunityController extends Controller
{
    private function categories()
    {
        return Group::orderBy('ordre')->orderBy('id')->get(['id', 'name', 'icone', 'couleur'])
            ->map(fn (Group $g) => ['id' => $g->id, 'nom' => $g->name, 'icone' => $g->icone ?: 'network', 'couleur' => $g->couleur ?: '#031D59'])->values();
    }

    private function referentiels(): array
    {
        return [
            'categories' => $this->categories(),
            'types' => Opportunity::TYPES,
            'contrats' => Opportunity::CONTRATS,
            'teletravail' => Opportunity::TELETRAVAIL,
            'statuts' => Opportunity::STATUTS,
        ];
    }

    /** Données d'une offre ; $complet ajoute la fiche détaillée. */
    protected function payload(Opportunity $o, ?User $moi, bool $complet = false): array
    {
        $estAuteur = $moi && $o->author_id === $moi->id;
        $data = [
            'id' => $o->id,
            'title' => $o->title,
            'description' => $o->description,
            'type' => $o->type,
            'type_label' => Opportunity::TYPES[$o->type] ?? ucfirst((string) $o->type),
            'contrat' => $o->contrat,
            'contrat_label' => Opportunity::CONTRATS[$o->contrat] ?? null,
            'statut' => $o->estExpiree() ? 'expiree' : $o->statut,
            'statut_label' => $o->estExpiree() ? 'Expirée' : (Opportunity::STATUTS[$o->statut] ?? $o->statut),
            'entreprise' => $o->entreprise,
            'site_url' => $o->site_url,
            'lieu' => $o->lieu,
            'teletravail' => $o->teletravail,
            'teletravail_label' => Opportunity::TELETRAVAIL[$o->teletravail] ?? null,
            'remuneration' => $o->remuneration,
            'debut' => $o->debut?->toDateString(),
            'duree' => $o->duree,
            'deadline' => $o->deadline?->toDateString(),
            'expire_le' => $o->expire_le?->toDateString(),
            'groupe' => $o->groupe ? ['id' => $o->groupe->id, 'nom' => $o->groupe->name, 'couleur' => $o->groupe->couleur ?: '#031D59', 'icone' => $o->groupe->icone ?: 'network'] : null,
            'competences' => $o->competences ?? [],
            'author' => $o->author ? trim($o->author->prenom.' '.$o->author->nom) : null,
            'auteur' => $o->author ? $o->author->only(['id', 'prenom', 'nom', 'photo', 'role', 'titre']) : null,
            'mine' => $estAuteur,
            'media_url' => $o->media_url,
            'media_name' => $o->media_name,
            'publie_at' => $o->publie_at?->toIso8601String(),
            'created_at' => $o->created_at?->toIso8601String(),
        ];
        if ($complet) {
            $data += ['missions' => $o->missions, 'profil' => $o->profil, 'vues' => (int) $o->vues];
        }
        if ($moi && ! $estAuteur) {
            $c = $o->relationLoaded('candidatures') ? $o->candidatures->firstWhere('user_id', $moi->id) : $o->candidatures()->where('user_id', $moi->id)->first();
            $data['ma_candidature'] = $c ? ['statut' => $c->statut, 'statut_label' => OpportunityApplication::STATUTS[$c->statut] ?? $c->statut, 'date' => $c->created_at->toIso8601String()] : null;
        }
        if ($moi && ! $estAuteur && $moi->role !== 'admin') {
            $data['deja_signalee'] = DB::table('opportunity_reports')->where('opportunity_id', $o->id)->where('user_id', $moi->id)->exists();
        }
        if ($moi?->role === 'admin') {
            $data['signalements'] = DB::table('opportunity_reports')->join('users', 'users.id', '=', 'opportunity_reports.user_id')
                ->where('opportunity_id', $o->id)->where('statut', 'nouveau')->orderByDesc('opportunity_reports.created_at')
                ->get(['opportunity_reports.motif', 'opportunity_reports.created_at', 'users.prenom', 'users.nom'])
                ->map(fn ($r) => ['motif' => $r->motif, 'date' => $r->created_at, 'par' => trim($r->prenom.' '.$r->nom)])->all();
            $data['candidatures_par_statut'] = $o->candidatures()->selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut')->all();
        }
        if ($estAuteur || $moi?->role === 'admin') {
            $cands = $o->candidatures()->where('statut', '!=', 'retiree');
            $data['nb_candidatures'] = (clone $cands)->count();
            $data['nb_nouvelles'] = (clone $cands)->whereNull('vue_at')->count();
        }
        if ($estAuteur || $moi?->role === 'admin') {
            $data['motif'] = $o->motif;
            $data['contact'] = $o->contact;
        }

        return $data;
    }

    private function regles(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'type' => ['required', Rule::in(array_keys(Opportunity::TYPES))],
            'contrat' => ['nullable', Rule::in(array_keys(Opportunity::CONTRATS))],
            'entreprise' => 'required|string|min:2|max:160',
            'group_id' => 'required|integer|exists:groups,id',
            'site_url' => 'nullable|url|max:500',
            'lieu' => 'required|string|min:2|max:160',
            'teletravail' => ['nullable', Rule::in(array_keys(Opportunity::TELETRAVAIL))],
            'remuneration' => 'nullable|string|max:120',
            'debut' => 'nullable|date',
            'duree' => 'nullable|string|max:60',
            'description' => 'required|string|min:20|max:3000',
            'missions' => 'nullable|string|max:3000',
            'profil' => 'nullable|string|max:2000',
            'competences' => 'nullable|array|max:12',
            'competences.*' => 'nullable|string|max:40',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date|after_or_equal:today',
            'media_url' => 'nullable|url|max:500',
            'media_name' => 'nullable|string|max:200',
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => "Donnez un intitulé à l'offre.",
            'title.min' => "L'intitulé est trop court.",
            'entreprise.required' => "Indiquez l'entreprise ou la structure qui recrute.",
            'group_id.required' => "Choisissez le secteur de l'offre.",
            'group_id.exists' => "Choisissez le secteur de l'offre.",
            'lieu.required' => 'Indiquez la ville du poste.',
            'description.required' => 'Présentez le poste ou le stage.',
            'description.min' => "Présentez l'offre en quelques phrases (20 caractères minimum).",
            'deadline.after_or_equal' => 'La date limite de candidature doit être aujourd\'hui ou plus tard.',
            'site_url.url' => 'Le lien du site doit être une adresse complète (https://…).',
        ];
    }

    private function valider(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $v = Validator::make($request->all(), $this->regles(), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $d['contrat'] = $d['type'] === 'emploi' ? ($d['contrat'] ?? 'cdi') : null;
        $d['teletravail'] ??= 'sur_site';
        $d['competences'] = array_values(array_unique(array_filter(array_map(fn ($c) => trim($c), $d['competences'] ?? []))));

        return $d;
    }

    private function notifier(Opportunity $o, string $titre, string $texte): void
    {
        if ($o->author_id) {
            MemberNotification::create([
                'user_id' => $o->author_id, 'type' => 'info', 'title' => $titre, 'body' => $texte,
                'link' => "/espace-membre/emplois?offre={$o->id}",
            ]);
        }
    }

    private function avecRelations()
    {
        return Opportunity::with(['author:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone']);
    }

    // ------------------------------------------------------------------
    // Site public : offres en ligne, sans contact ni auteur (postuler = membres)
    // ------------------------------------------------------------------

    private function payloadPublic(Opportunity $o, bool $complet = false): array
    {
        $p = $this->payload($o, null, $complet);
        unset($p['author'], $p['auteur'], $p['mine'], $p['media_url'], $p['media_name']);

        return $p;
    }

    /** GET /public-opportunities */
    public function publicIndex()
    {
        return response()->json(['ok' => true, 'opportunities' => Opportunity::with('groupe:id,name,couleur,icone')->enLigne()
            ->orderByDesc('publie_at')->get()->map(fn ($o) => $this->payloadPublic($o))->values()]);
    }

    /** GET /public-opportunities/{id} */
    public function publicShow(int $id)
    {
        $o = Opportunity::with('groupe:id,name,couleur,icone')->enLigne()->find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => "Cette offre n'est plus disponible."], 404);
        }

        return response()->json(['ok' => true, 'opportunity' => $this->payloadPublic($o, true)]);
    }

    // ------------------------------------------------------------------
    // Espace membre
    // ------------------------------------------------------------------

    /**
     * GET /opportunities?q=&type=&groupe=&ville=&teletravail=&tri=&favoris=1 —
     * offres en ligne filtrées + mes offres (tous statuts) + mes alertes.
     */
    public function index(Request $request)
    {
        $moi = $request->user();
        $query = $this->avecRelations()->select('opportunities.*')
            ->leftJoin('groups', 'groups.id', '=', 'opportunities.group_id')
            ->enLigne();
        RechercheMots::appliquer($query, (string) $request->query('q', ''), [
            'opportunities.title', 'opportunities.description', 'opportunities.entreprise', 'opportunities.lieu',
            'opportunities.missions', 'opportunities.profil', 'groups.name',
        ], ['opportunities.competences']);
        if (isset(Opportunity::TYPES[$type = (string) $request->query('type')])) {
            $query->where('opportunities.type', $type);
        }
        if ($groupe = (int) $request->query('groupe')) {
            $query->where('opportunities.group_id', $groupe);
        }
        if ($ville = trim((string) $request->query('ville', ''))) {
            $query->where('opportunities.lieu', 'like', "%{$ville}%");
        }
        if (isset(Opportunity::TELETRAVAIL[$tt = (string) $request->query('teletravail')])) {
            $query->where('opportunities.teletravail', $tt);
        }
        $favoris = DB::table('opportunity_favoris')->where('user_id', $moi->id)->pluck('opportunity_id')->all();
        if ($request->boolean('favoris')) {
            $query->whereIn('opportunities.id', $favoris ?: [0]);
        }
        match ($request->query('tri')) {
            'limite' => $query->orderByRaw('opportunities.deadline is null')->orderBy('opportunities.deadline'),
            'vues' => $query->orderByDesc('opportunities.vues'),
            default => $query->orderByDesc('opportunities.publie_at'),
        };
        $offres = $query->get();
        $mes = $this->avecRelations()->where('author_id', $moi->id)->orderByDesc('created_at')->get();
        $villes = Opportunity::enLigne()->whereNotNull('lieu')->pluck('lieu')
            ->map(fn ($l) => trim(explode(',', $l)[0]))->filter()->unique()->sort()->values();

        return response()->json([
            'ok' => true,
            'opportunities' => $offres->map(fn ($o) => $this->payload($o, $moi) + ['favori' => in_array($o->id, $favoris, true)])->values(),
            'mes_offres' => $mes->map(fn ($o) => $this->payload($o, $moi))->values(),
            'peut_publier' => $moi->hasActiveSubscription(),
            'nb_favoris' => count($favoris),
            'villes' => $villes,
            'alertes' => JobAlert::with('groupe:id,name')->where('user_id', $moi->id)->latest()->get()
                ->map(fn (JobAlert $a) => ['id' => $a->id, 'libelle' => $a->libelle()])->values(),
        ] + $this->referentiels());
    }

    /** POST /opportunities/{id}/favori — sauvegarder / retirer une offre. */
    public function favori(Request $request, int $id)
    {
        $moi = $request->user();
        if (! Opportunity::enLigne()->whereKey($id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $existe = DB::table('opportunity_favoris')->where('user_id', $moi->id)->where('opportunity_id', $id);
        if ($existe->exists()) {
            $existe->delete();

            return response()->json(['ok' => true, 'favori' => false]);
        }
        DB::table('opportunity_favoris')->insert(['user_id' => $moi->id, 'opportunity_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true, 'favori' => true]);
    }

    /** POST /opportunities/{id}/signaler — un membre signale une offre suspecte. */
    public function signaler(Request $request, int $id)
    {
        $moi = $request->user();
        $o = Opportunity::enLigne()->find($id);
        if (! $o || $o->author_id === $moi->id) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $motif = mb_substr(trim((string) $request->input('motif')), 0, 500);
        if (mb_strlen($motif) < 5) {
            return response()->json(['ok' => false, 'message' => 'Expliquez en quelques mots pourquoi vous signalez cette offre.'], 422);
        }
        DB::table('opportunity_reports')->updateOrInsert(['opportunity_id' => $o->id, 'user_id' => $moi->id],
            ['motif' => $motif, 'statut' => 'nouveau', 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** POST /job-alerts — « M'alerter » pour une recherche (5 alertes au plus). */
    public function creerAlerte(Request $request)
    {
        $moi = $request->user();
        $v = Validator::make($request->all(), [
            'type' => ['nullable', Rule::in(array_keys(Opportunity::TYPES))],
            'group_id' => 'nullable|integer|exists:groups,id',
            'ville' => 'nullable|string|max:80',
            'q' => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = array_map(fn ($x) => is_string($x) ? (trim($x) ?: null) : $x, $v->validated());
        if (JobAlert::where('user_id', $moi->id)->count() >= 5) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà 5 alertes : supprimez-en une pour en créer une nouvelle.'], 422);
        }
        $existe = JobAlert::where('user_id', $moi->id)->where('type', $d['type'] ?? null)->where('group_id', $d['group_id'] ?? null)
            ->where('ville', $d['ville'] ?? null)->where('q', $d['q'] ?? null)->exists();
        if ($existe) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà une alerte pour cette recherche.'], 422);
        }
        $a = JobAlert::create($d + ['user_id' => $moi->id]);

        return response()->json(['ok' => true, 'alerte' => ['id' => $a->id, 'libelle' => $a->load('groupe')->libelle()]], 201);
    }

    /** DELETE /job-alerts/{id} */
    public function supprimerAlerte(Request $request, int $id)
    {
        JobAlert::where('user_id', $request->user()->id)->whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }

    /** GET /opportunities/{id} — fiche (offre en ligne, ou la mienne). */
    public function show(Request $request, int $id)
    {
        $moi = $request->user();
        $o = $this->avecRelations()->find($id);
        $visible = $o && ($o->author_id === $moi->id || $moi->role === 'admin' || ($o->statut === 'publiee' && ! $o->estExpiree()));
        if (! $visible) {
            return response()->json(['ok' => false, 'message' => "Cette offre n'est plus disponible (pourvue, clôturée ou expirée)."], 404);
        }
        if ($o->author_id !== $moi->id) {
            $o->increment('vues');
        }

        $favori = DB::table('opportunity_favoris')->where('user_id', $moi->id)->where('opportunity_id', $o->id)->exists();

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o, $moi, true) + ['favori' => $favori]] + $this->referentiels());
    }

    /** POST /opportunities — un membre abonné propose une offre (validée par l'équipe). */
    public function store(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $moi = $request->user();
        if (Opportunity::where('author_id', $moi->id)->where('statut', 'en_attente')->count() >= 5) {
            return response()->json(['ok' => false, 'message' => "Vous avez déjà 5 offres en attente de validation : patientez avant d'en proposer d'autres."], 422);
        }
        $o = Opportunity::create($d + ['author_id' => $moi->id, 'statut' => 'en_attente']);

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o->load('groupe'), $moi, true)], 201);
    }

    /** PUT /opportunities/{id} — l'auteur modifie ; à corriger ou refusée : renvoyée en validation. */
    public function update(Request $request, int $id)
    {
        $moi = $request->user();
        $o = Opportunity::where('author_id', $moi->id)->find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $resoumise = in_array($o->statut, ['a_corriger', 'refusee'], true);
        if ($resoumise) {
            $d['statut'] = 'en_attente';
        }
        $o->fill($d);
        if ($o->statut === 'publiee' && $o->isDirty('deadline')) {
            $o->calculerExpiration();
        }
        $o->save();

        return response()->json(['ok' => true, 'resoumise' => $resoumise, 'opportunity' => $this->payload($o->fresh(['groupe', 'author']), $moi, true)]);
    }

    /** POST /opportunities/{id}/statut — l'auteur marque l'offre pourvue, la clôture ou la rouvre. */
    public function changerStatut(Request $request, int $id)
    {
        $o = Opportunity::where('author_id', $request->user()->id)->find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $statut = (string) $request->input('statut');
        if (in_array($statut, ['pourvue', 'cloturee'], true) && $o->statut === 'publiee') {
            $o->update(['statut' => $statut]);
        } elseif ($statut === 'publiee' && in_array($o->statut, ['pourvue', 'cloturee'], true)) {
            // Réouverture d'une offre déjà validée : nouvelle période de publication.
            $o->statut = 'publiee';
            $o->deadline = null;
            $o->calculerExpiration();
            $o->save();
        } else {
            return response()->json(['ok' => false, 'message' => 'Changement impossible pour cette offre.'], 422);
        }

        return response()->json(['ok' => true, 'statut' => $o->statut]);
    }

    /** POST /opportunities/{id}/prolonger — 30 jours de plus pour une offre en ligne (ou expirée). */
    public function prolonger(Request $request, int $id)
    {
        $o = Opportunity::where('author_id', $request->user()->id)->where('statut', 'publiee')->find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => "Seule une offre en ligne peut être prolongée."], 422);
        }
        $depart = $o->expire_le && $o->expire_le->gte(today()) ? $o->expire_le : today();
        $o->update(['expire_le' => $depart->copy()->addDays(30), 'deadline' => $o->deadline ? $depart->copy()->addDays(30) : null, 'rappel_at' => null]);

        return response()->json(['ok' => true, 'expire_le' => $o->expire_le->toDateString()]);
    }

    /** DELETE /opportunities/{id} — l'auteur supprime son offre. */
    public function destroy(Request $request, int $id)
    {
        Opportunity::where('author_id', $request->user()->id)->whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Candidatures
    // ------------------------------------------------------------------

    private function nom(User $u): string
    {
        return trim($u->prenom.' '.$u->nom);
    }

    /** POST /opportunities/{id}/postuler — un membre postule (message + CV). */
    public function postuler(Request $request, int $id)
    {
        $moi = $request->user();
        $o = Opportunity::enLigne()->find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => "Cette offre n'accepte plus de candidatures."], 422);
        }
        if ($o->author_id === $moi->id) {
            return response()->json(['ok' => false, 'message' => "Vous ne pouvez pas postuler à votre propre offre."], 422);
        }
        if ($o->deadline && $o->deadline->lt(today())) {
            return response()->json(['ok' => false, 'message' => 'La date limite de candidature est dépassée.'], 422);
        }
        $existante = OpportunityApplication::where('opportunity_id', $o->id)->where('user_id', $moi->id)->first();
        if ($existante && $existante->statut !== 'retiree') {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà postulé à cette offre.'], 422);
        }
        $v = Validator::make($request->all(), [
            'message' => 'required|string|min:30|max:3000',
            'cv_url' => 'nullable|url|max:500',
            'cv_name' => 'nullable|string|max:200',
        ], [
            'message.required' => 'Présentez-vous au recruteur en quelques lignes.',
            'message.min' => 'Votre message est trop court : expliquez en quelques lignes pourquoi ce poste vous intéresse (30 caractères minimum).',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $c = OpportunityApplication::updateOrCreate(['opportunity_id' => $o->id, 'user_id' => $moi->id],
            $v->validated() + ['statut' => 'recue', 'vue_at' => null, 'note' => null]);

        if ($o->author_id) {
            MemberNotification::create([
                'user_id' => $o->author_id, 'type' => 'info',
                'title' => "Nouvelle candidature : {$o->title}",
                'body' => $this->nom($moi).' a postulé à votre offre. Consultez sa candidature et son profil sur la plateforme.',
                'link' => "/espace-membre/emplois?offre={$o->id}&candidatures=1",
            ]);
        }

        return response()->json(['ok' => true, 'candidature' => ['statut' => $c->statut]], 201);
    }

    /** DELETE /opportunities/{id}/candidature — le candidat retire sa candidature. */
    public function retirerCandidature(Request $request, int $id)
    {
        $c = OpportunityApplication::where('opportunity_id', $id)->where('user_id', $request->user()->id)->first();
        if (! $c || ! in_array($c->statut, ['recue', 'preselection'], true)) {
            return response()->json(['ok' => false, 'message' => 'Cette candidature ne peut plus être retirée.'], 422);
        }
        $c->update(['statut' => 'retiree']);

        return response()->json(['ok' => true]);
    }

    /** GET /opportunities/{id}/candidatures — candidatures reçues (auteur de l'offre ou admin). */
    public function candidatures(Request $request, int $id)
    {
        $moi = $request->user();
        $o = Opportunity::find($id);
        if (! $o || ($o->author_id !== $moi->id && $moi->role !== 'admin')) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $liste = OpportunityApplication::with('user:id,prenom,nom,photo,role,titre,ville,email,telephone,created_at')
            ->where('opportunity_id', $o->id)->where('statut', '!=', 'retiree')
            ->orderByRaw("CASE statut WHEN 'recue' THEN 0 WHEN 'preselection' THEN 1 WHEN 'retenue' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')->get();

        // Le recruteur a vu les candidatures : elles ne sont plus « nouvelles ».
        if ($o->author_id === $moi->id) {
            OpportunityApplication::where('opportunity_id', $o->id)->whereNull('vue_at')->update(['vue_at' => now()]);
        }

        return response()->json(['ok' => true, 'candidatures' => $liste->filter(fn ($c) => $c->user)->map(fn (OpportunityApplication $c) => [
            'id' => $c->id,
            'statut' => $c->statut,
            'statut_label' => OpportunityApplication::STATUTS[$c->statut] ?? $c->statut,
            'message' => $c->message,
            'cv_url' => $c->cv_url,
            'cv_name' => $c->cv_name,
            'note' => $c->note,
            'nouvelle' => $c->vue_at === null,
            'date' => $c->created_at->toIso8601String(),
            // En postulant, le candidat partage ses coordonnées avec le recruteur.
            'candidat' => $c->user->only(['id', 'prenom', 'nom', 'photo', 'role', 'titre', 'ville', 'email', 'telephone']) + ['code' => $c->user->cardCode()],
        ])->values(), 'statuts' => OpportunityApplication::STATUTS]);
    }

    /** POST /opportunities/{id}/candidatures/{c}/statut — le recruteur fait avancer une candidature. */
    public function statutCandidature(Request $request, int $id, int $cid)
    {
        $moi = $request->user();
        $o = Opportunity::where('author_id', $moi->id)->find($id);
        $c = $o ? OpportunityApplication::where('opportunity_id', $o->id)->find($cid) : null;
        if (! $c || $c->statut === 'retiree') {
            return response()->json(['ok' => false, 'message' => 'Candidature introuvable.'], 404);
        }
        $v = Validator::make($request->all(), [
            'statut' => 'nullable|in:recue,preselection,retenue,non_retenue',
            'note' => 'nullable|string|max:1000',
            'message' => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        if (array_key_exists('note', $d)) {
            $c->note = trim((string) $d['note']) ?: null;
        }
        $avant = $c->statut;
        if (! empty($d['statut'])) {
            $c->statut = $d['statut'];
        }
        $c->save();

        if ($c->statut !== $avant && $c->statut !== 'recue') {
            $mot = trim((string) ($d['message'] ?? ''));
            [$titre, $texte] = match ($c->statut) {
                'preselection' => ["Candidature présélectionnée : {$o->title}", 'Bonne nouvelle : votre candidature a retenu l\'attention du recruteur, qui reviendra vers vous.'],
                'retenue' => ["Candidature retenue : {$o->title}", 'Félicitations ! Votre candidature est retenue. Le recruteur va vous contacter pour la suite.'],
                default => ["Candidature non retenue : {$o->title}", "Votre candidature n'a pas été retenue cette fois-ci. Merci pour votre intérêt, et bon courage pour vos recherches !"],
            };
            MemberNotification::create([
                'user_id' => $c->user_id, 'type' => 'info', 'title' => $titre,
                'body' => $texte.($mot !== '' ? " Message du recruteur : {$mot}" : ''),
                'link' => "/espace-membre/emplois?onglet=candidatures&offre={$o->id}",
            ]);
        }

        return response()->json(['ok' => true, 'statut' => $c->statut]);
    }

    /** GET /mes-candidatures — suivi des candidatures du membre. */
    public function mesCandidatures(Request $request)
    {
        $liste = OpportunityApplication::with(['opportunity' => fn ($q) => $q->with('groupe:id,name,couleur,icone')])
            ->where('user_id', $request->user()->id)->orderByDesc('created_at')->get()
            ->filter(fn ($c) => $c->opportunity);

        return response()->json(['ok' => true, 'candidatures' => $liste->map(fn (OpportunityApplication $c) => [
            'id' => $c->id,
            'statut' => $c->statut,
            'statut_label' => OpportunityApplication::STATUTS[$c->statut] ?? $c->statut,
            'date' => $c->created_at->toIso8601String(),
            'vue' => $c->vue_at !== null,
            'offre' => $this->payload($c->opportunity, $request->user()),
        ])->values()]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex(Request $request)
    {
        $moi = $request->user();
        $statut = (string) $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));

        $query = $this->avecRelations()
            ->when($statut === 'signalee', fn ($w) => $w->whereIn('id', DB::table('opportunity_reports')->where('statut', 'nouveau')->select('opportunity_id')))
            ->when($statut === 'expiree', fn ($w) => $w->where('statut', 'publiee')->whereDate('expire_le', '<', today()))
            ->when($statut === 'publiee', fn ($w) => $w->enLigne())
            ->when($statut !== '' && ! in_array($statut, ['expiree', 'publiee', 'signalee'], true), fn ($w) => $w->where('statut', $statut))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('entreprise', 'like', "%{$q}%")
                ->orWhere('lieu', 'like', "%{$q}%")->orWhereHas('author', fn ($u) => $u->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%"))))
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 WHEN 'a_corriger' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        $compteurs = Opportunity::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut')->all();
        $expirees = Opportunity::where('statut', 'publiee')->whereDate('expire_le', '<', today())->count();
        $compteurs['publiee'] = ($compteurs['publiee'] ?? 0) - $expirees;
        $compteurs['expiree'] = $expirees;
        $signalees = DB::table('opportunity_reports')->where('statut', 'nouveau')->distinct()->count('opportunity_id');

        return response()->json([
            'ok' => true,
            'opportunities' => $query->get()->map(fn ($o) => $this->payload($o, $moi, true) + [
                'auteur_email' => $o->author?->email, 'auteur_telephone' => $o->author?->telephone,
            ])->values(),
            'compteurs' => $compteurs,
            'signalees' => $signalees,
            'stats' => [
                'en_ligne' => Opportunity::enLigne()->count(),
                'candidatures' => OpportunityApplication::where('statut', '!=', 'retiree')->count(),
                'retenues' => OpportunityApplication::where('statut', 'retenue')->count(),
                'pourvues' => Opportunity::where('statut', 'pourvue')->count(),
                'alertes' => JobAlert::count(),
            ],
        ] + $this->referentiels());
    }

    /** POST /admin/opportunities — l'équipe publie directement une offre. */
    public function adminStore(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $o = new Opportunity($d + ['author_id' => $request->user()->id, 'statut' => 'publiee', 'publie_at' => now(), 'decide_at' => now()]);
        $o->calculerExpiration();
        $o->save();
        $alertes = JobAlert::prevenir($o);

        return response()->json(['ok' => true, 'alertes' => $alertes, 'opportunity' => $this->payload($o->load('groupe'), $request->user(), true)], 201);
    }

    /** PUT /admin/opportunities/{id} — correction par l'équipe (l'auteur est prévenu). */
    public function adminUpdate(Request $request, int $id)
    {
        $o = Opportunity::find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $o->fill($d);
        if ($o->statut === 'publiee' && $o->isDirty('deadline')) {
            $o->calculerExpiration();
        }
        $o->save();
        if ($o->author_id !== $request->user()->id) {
            $note = trim((string) $request->input('note'));
            $this->notifier($o, "Offre corrigée par l'équipe : {$o->title}", "L'équipe REJCC a apporté des corrections à votre offre.".($note !== '' ? " Note : {$note}" : ''));
        }

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o->fresh(['groupe', 'author']), $request->user(), true)]);
    }

    /** POST /admin/opportunities/{id}/decision — publier, demander une correction ou refuser (motif transmis). */
    public function decision(Request $request, int $id)
    {
        $o = Opportunity::find($id);
        if (! $o) {
            return response()->json(['ok' => false, 'message' => 'Offre introuvable.'], 404);
        }
        $v = Validator::make($request->all(), [
            'decision' => 'required|in:publier,corriger,refuser,retirer',
            'motif' => 'nullable|string|max:1000|required_unless:decision,publier',
        ], ['motif.required_unless' => "Indiquez à l'auteur ce qu'il doit corriger, ou la raison de la décision."]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $decision = $v->validated()['decision'];
        $motif = trim((string) $request->input('motif')) ?: null;

        $premierePublication = $decision === 'publier' && $o->publie_at === null;
        if ($decision === 'publier') {
            $o->statut = 'publiee';
            $o->motif = null;
            $o->publie_at = now();
            $o->calculerExpiration();
        } else {
            $o->statut = ['corriger' => 'a_corriger', 'refuser' => 'refusee', 'retirer' => 'refusee'][$decision];
            $o->motif = $motif;
        }
        $o->decide_at = now();
        $o->save();

        [$titre, $texte] = match ($decision) {
            'publier' => ["Offre publiée : {$o->title}", "Votre offre est en ligne jusqu'au ".$o->expire_le->locale('fr')->isoFormat('D MMMM YYYY').' : les membres peuvent y postuler.'],
            'corriger' => ["Offre à corriger : {$o->title}", "L'équipe a besoin d'une correction avant de publier votre offre : {$motif} Modifiez-la pour la renvoyer en validation."],
            'refuser' => ["Offre non publiée : {$o->title}", "Votre offre n'a pas été publiée. Motif : {$motif}"],
            'retirer' => ["Offre retirée : {$o->title}", "Votre offre a été retirée par l'équipe. Motif : {$motif}"],
        };
        $this->notifier($o, $titre, $texte);
        if ($decision === 'retirer') {
            DB::table('opportunity_reports')->where('opportunity_id', $o->id)->update(['statut' => 'classe', 'updated_at' => now()]);
        }
        $alertes = $premierePublication ? JobAlert::prevenir($o) : 0;

        return response()->json(['ok' => true, 'alertes' => $alertes, 'opportunity' => $this->payload($o->fresh(['groupe', 'author']), $request->user(), true)]);
    }

    /** POST /admin/opportunities/{id}/signalements — classer les signalements sans suite. */
    public function classerSignalements(int $id)
    {
        DB::table('opportunity_reports')->where('opportunity_id', $id)->update(['statut' => 'classe', 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function adminDestroy(int $id)
    {
        Opportunity::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
