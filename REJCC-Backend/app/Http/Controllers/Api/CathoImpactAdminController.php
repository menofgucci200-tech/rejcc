<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CathoImpactCandidature;
use App\Models\CathoImpactPortrait;
use App\Support\CathoImpact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CathoImpact dans l'espace Communication (rubrique « communication ») :
 * suivi des réponses, de la présélection jusqu'à la publication, et
 * galerie « Ils ont inspiré » du site.
 */
class CathoImpactAdminController extends Controller
{
    /** GET /admin/cathoimpact?statut=&q=&page= */
    public function index(Request $request)
    {
        $q = CathoImpact::visibles($request->user())->with('communityManager:id,prenom,nom,email')->latest();
        if (($statut = $request->query('statut')) && isset(CathoImpact::STATUTS[$statut])) {
            $q->where('statut', $statut);
        }
        if (($texte = trim((string) $request->query('q', ''))) !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $texte).'%';
            $q->where(fn ($w) => $w->where('nom_complet', 'like', $like)->orWhere('email', 'like', $like)
                ->orWhere('entreprise', 'like', $like)->orWhere('secteur', 'like', $like)->orWhere('ville', 'like', $like));
        }
        $page = $q->paginate(min(100, max(10, $request->integer('par_page', 20))));

        return response()->json([
            'ok' => true,
            'candidatures' => collect($page->items())->map(fn ($c) => $this->ligne($c)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'compteurs' => CathoImpact::visibles($request->user())->selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut')->map(fn ($n) => (int) $n),
            'statuts' => CathoImpact::STATUTS,
        ]);
    }

    /** GET /admin/cathoimpact/{id} */
    public function show(Request $request, int $id)
    {
        $c = CathoImpact::visibles($request->user())->with('communityManager:id,prenom,nom,email')->findOrFail($id);

        return response()->json(['ok' => true, 'candidature' => $this->fiche($c)]);
    }

    /** PUT /admin/cathoimpact/{id} : statut et suivi. */
    public function update(Request $request, int $id)
    {
        $c = CathoImpact::visibles($request->user())->findOrFail($id);
        $d = $request->validate([
            'statut' => ['sometimes', Rule::in(array_keys(CathoImpact::STATUTS))],
            'community_manager_id' => ['sometimes', 'nullable', 'integer', Rule::in(CathoImpact::equipe()->pluck('id')->all())],
            'community_manager' => 'sometimes|nullable|string|max:150',
            'lien_questionnaire' => 'sometimes|nullable|url:http,https|max:500',
            'lien_publication' => 'sometimes|nullable|url:http,https|max:500',
            'note_interne' => 'sometimes|nullable|string|max:5000',
        ], [
            'community_manager_id.in' => 'Ce compte ne fait pas partie du département Communication.',
            'lien_questionnaire.url' => 'Le lien du questionnaire doit commencer par https://',
            'lien_publication.url' => 'Le lien de la publication doit commencer par https://',
        ]);

        if (isset($d['statut'])) {
            $c->changerStatut($d['statut'], $request->user());
            unset($d['statut']);
        }
        // Un compte de l'équipe ou un nom libre, pas les deux.
        if (! empty($d['community_manager_id'])) {
            $d['community_manager'] = null;
        } elseif (array_key_exists('community_manager', $d) && trim((string) $d['community_manager']) !== '') {
            $d['community_manager_id'] = null;
        }
        $c->fill($d)->save();

        return response()->json(['ok' => true, 'candidature' => $this->fiche($c->fresh('communityManager'))]);
    }

    /** DELETE /admin/cathoimpact/{id} : renvoie le chemin de la photo, que le dashboard efface. */
    public function destroy(Request $request, int $id)
    {
        $c = CathoImpact::visibles($request->user())->findOrFail($id);
        $photo = $c->photo;
        $c->delete();

        return response()->json(['ok' => true, 'photo' => $photo]);
    }

    /** GET /admin/cathoimpact/{id}/photo : chemin de la photo dans le stockage privé partagé. */
    public function photo(Request $request, int $id)
    {
        $c = CathoImpact::visibles($request->user())->findOrFail($id);
        if (! $c->photo || ! preg_match(CathoImpact::CHEMIN_PHOTO, $c->photo)) {
            return response()->json(['ok' => false, 'message' => 'Aucune photo.'], 404);
        }

        return response()->json(['ok' => true, 'fichier' => ['chemin' => $c->photo, 'nom' => 'cathoimpact-'.$c->id.'.'.pathinfo($c->photo, PATHINFO_EXTENSION)]]);
    }

    /** GET /admin/cathoimpact/equipe : comptes du département Communication (attribution). */
    public function equipe()
    {
        return response()->json(['ok' => true, 'equipe' => CathoImpact::equipe()->map(fn ($u) => [
            'id' => $u->id, 'nom' => trim("{$u->prenom} {$u->nom}") ?: $u->email,
        ])]);
    }

    /** GET /admin/cathoimpact/resume : bloc de l'espace du département Communication. */
    public function resume(Request $request)
    {
        $debutMois = now()->startOfMonth();
        $visibles = fn () => CathoImpact::visibles($request->user());

        return response()->json(['ok' => true,
            'chiffres' => [
                'recus_mois' => $visibles()->where('created_at', '>=', $debutMois)->count(),
                'a_traiter' => $visibles()->where('statut', 'recu')->count(),
                'publies_mois' => $visibles()->where('statut', 'publie')->where('statut_at', '>=', $debutMois)->count(),
            ],
            'dernieres' => $visibles()->latest()->limit(5)->get()->map(fn ($c) => $this->ligne($c)),
        ]);
    }

    // ── Galerie « Ils ont inspiré » ──────────────────────────────────────

    /** GET /admin/cathoimpact/portraits */
    public function portraits(Request $request)
    {
        $visibles = CathoImpact::visibles($request->user())->pluck('id')->flip();

        return response()->json([
            'ok' => true,
            // Les cartes sont publiques ; le lien vers la réponse d'origine n'est donné que si elle est visible.
            'portraits' => CathoImpactPortrait::with('candidature:id,nom_complet')->orderBy('ordre')->orderBy('id')->get()
                ->map(fn ($p) => $this->portrait($p, $visibles->has($p->candidature_id))),
            'reseaux' => CathoImpact::RESEAUX,
        ]);
    }

    /** POST /admin/cathoimpact/portraits */
    public function creerPortrait(Request $request)
    {
        $d = $this->validerPortrait($request);
        $d['ordre'] = (int) CathoImpactPortrait::max('ordre') + 1;
        $p = CathoImpactPortrait::create($d);

        return response()->json(['ok' => true, 'portrait' => $this->portrait($p, $this->reponseVisible($request, $p))], 201);
    }

    /** PUT /admin/cathoimpact/portraits/{id} */
    public function modifierPortrait(Request $request, int $id)
    {
        $p = CathoImpactPortrait::findOrFail($id);
        $p->update($this->validerPortrait($request, partiel: true));

        $p = $p->fresh('candidature');

        return response()->json(['ok' => true, 'portrait' => $this->portrait($p, $this->reponseVisible($request, $p))]);
    }

    /** DELETE /admin/cathoimpact/portraits/{id} */
    public function supprimerPortrait(int $id)
    {
        CathoImpactPortrait::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/cathoimpact/portraits/ordre {ids: [...]} */
    public function ordrePortraits(Request $request)
    {
        $d = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|distinct']);
        DB::transaction(function () use ($d) {
            foreach (array_values($d['ids']) as $i => $id) {
                CathoImpactPortrait::where('id', $id)->update(['ordre' => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function validerPortrait(Request $request, bool $partiel = false): array
    {
        $requis = $partiel ? 'sometimes|required' : 'required';

        return $request->validate([
            'nom' => "{$requis}|string|min:2|max:150",
            'accroche' => 'sometimes|nullable|string|max:200',
            'image_url' => "{$requis}|url:http,https|max:500",
            'lien' => "{$requis}|url:http,https|max:500",
            'reseau' => [$partiel ? 'sometimes' : 'required', Rule::in(array_keys(CathoImpact::RESEAUX))],
            'candidature_id' => ['sometimes', 'nullable', 'integer', fn ($attr, $id, $echec) => $id === null
                || CathoImpact::visibles($request->user())->whereKey($id)->exists() || $echec('Réponse CathoImpact introuvable.')],
            'visible' => 'sometimes|boolean',
        ], [
            'image_url.required' => 'Ajoutez une image.',
            'image_url.url' => 'L\'image doit être une adresse https://',
            'lien.url' => 'Le lien de la publication doit commencer par https://',
        ]);
    }

    /** La réponse d'origine d'une carte est-elle visible par ce compte (compte de test) ? */
    private function reponseVisible(Request $request, CathoImpactPortrait $p): bool
    {
        return $p->candidature_id === null || CathoImpact::visibles($request->user())->whereKey($p->candidature_id)->exists();
    }

    private function ligne(CathoImpactCandidature $c): array
    {
        return [
            'id' => $c->id,
            'profil' => $c->profil,
            'profil_label' => CathoImpact::PROFILS[$c->profil]['label'] ?? $c->profil,
            'nom_complet' => $c->nom_complet,
            'email' => $c->email,
            'entreprise' => $c->entreprise,
            'fonction' => $c->fonction,
            'secteur' => $c->secteur,
            'ville' => $c->ville,
            'a_photo' => (bool) $c->photo,
            'statut' => $c->statut,
            'statut_label' => $c->libelleStatut(),
            'statut_at' => $c->statut_at?->toIso8601String(),
            'community_manager' => $c->nomCommunityManager(),
            'created_at' => $c->created_at?->toIso8601String(),
        ];
    }

    private function fiche(CathoImpactCandidature $c): array
    {
        $profil = CathoImpact::PROFILS[$c->profil] ?? CathoImpact::PROFILS['entrepreneur'];

        return $this->ligne($c) + [
            'whatsapp' => $c->whatsapp,
            'libelle_entreprise' => $profil['entreprise'],
            'libelle_fonction' => $profil['fonction'],
            'parcours' => $c->parcours,
            'inspiration' => $c->inspiration,
            'lien' => $c->lien,
            'consentement_texte' => $c->consentement_texte,
            'consentement_at' => $c->consentement_at?->toIso8601String(),
            'historique' => collect($c->historique ?? [])->map(fn ($h) => $h + ['label' => CathoImpact::libelleStatut($h['statut'] ?? null)])->all(),
            'community_manager_id' => $c->community_manager_id,
            'community_manager_libre' => $c->community_manager,
            'lien_questionnaire' => $c->lien_questionnaire,
            'lien_publication' => $c->lien_publication,
            'note_interne' => $c->note_interne,
        ];
    }

    private function portrait(CathoImpactPortrait $p, bool $reponseVisible = true): array
    {
        return [
            'id' => $p->id,
            'nom' => $p->nom,
            'accroche' => $p->accroche,
            'image_url' => $p->image_url,
            'lien' => $p->lien,
            'reseau' => $p->reseau,
            'reseau_label' => CathoImpact::RESEAUX[$p->reseau] ?? $p->reseau,
            'candidature_id' => $reponseVisible ? $p->candidature_id : null,
            'candidature_nom' => $reponseVisible ? $p->candidature?->nom_complet : null,
            'ordre' => $p->ordre,
            'visible' => $p->visible,
        ];
    }
}
