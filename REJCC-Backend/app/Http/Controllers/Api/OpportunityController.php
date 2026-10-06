<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;
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
    // Espace membre
    // ------------------------------------------------------------------

    /** GET /opportunities — offres en ligne + mes offres (tous statuts). */
    public function index(Request $request)
    {
        $moi = $request->user();
        $offres = $this->avecRelations()->enLigne()->orderByDesc('publie_at')->get();
        $mes = $this->avecRelations()->where('author_id', $moi->id)->orderByDesc('created_at')->get();

        return response()->json([
            'ok' => true,
            'opportunities' => $offres->map(fn ($o) => $this->payload($o, $moi))->values(),
            'mes_offres' => $mes->map(fn ($o) => $this->payload($o, $moi))->values(),
            'peut_publier' => $moi->hasActiveSubscription(),
        ] + $this->referentiels());
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

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o, $moi, true)] + $this->referentiels());
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
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex(Request $request)
    {
        $moi = $request->user();
        $statut = (string) $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));

        $query = $this->avecRelations()
            ->when($statut === 'expiree', fn ($w) => $w->where('statut', 'publiee')->whereDate('expire_le', '<', today()))
            ->when($statut === 'publiee', fn ($w) => $w->enLigne())
            ->when($statut !== '' && ! in_array($statut, ['expiree', 'publiee'], true), fn ($w) => $w->where('statut', $statut))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('entreprise', 'like', "%{$q}%")
                ->orWhere('lieu', 'like', "%{$q}%")->orWhereHas('author', fn ($u) => $u->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%"))))
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 WHEN 'a_corriger' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        $compteurs = Opportunity::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut')->all();
        $expirees = Opportunity::where('statut', 'publiee')->whereDate('expire_le', '<', today())->count();
        $compteurs['publiee'] = ($compteurs['publiee'] ?? 0) - $expirees;
        $compteurs['expiree'] = $expirees;

        return response()->json([
            'ok' => true,
            'opportunities' => $query->get()->map(fn ($o) => $this->payload($o, $moi, true) + [
                'auteur_email' => $o->author?->email, 'auteur_telephone' => $o->author?->telephone,
            ])->values(),
            'compteurs' => $compteurs,
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

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o->load('groupe'), $request->user(), true)], 201);
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

        return response()->json(['ok' => true, 'opportunity' => $this->payload($o->fresh(['groupe', 'author']), $request->user(), true)]);
    }

    public function adminDestroy(int $id)
    {
        Opportunity::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
