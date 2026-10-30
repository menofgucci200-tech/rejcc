<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\SalonMembre;
use App\Models\SalonMessage;
use App\Models\SalonModeration;
use App\Models\SalonSignalement;
use App\Models\User;
use App\Support\Communaute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * Administration de la Communauté (dashboard, rubrique « salons ») :
 * salons, référents, signalements, sanctions, mots interdits et journal.
 */
class CommunauteAdminController extends Controller
{
    /** GET /admin/communaute — vue d'ensemble de la Communauté. */
    public function index(Request $request)
    {
        $semaine = now()->subDays(7);
        $salons = Salon::orderBy('ordre')->orderBy('id')->get()->map(function (Salon $s) use ($semaine) {
            $referents = SalonMembre::with('user:id,prenom,nom,photo,role')->where('salon_id', $s->id)->where('role', 'moderateur')->get();

            return [
                'id' => $s->id,
                'nom' => $s->nom,
                'slug' => $s->slug,
                'emoji' => $s->emoji,
                'description' => $s->description,
                'type' => $s->type,
                'acces' => $s->acces,
                'lecture_seule' => $s->lecture_seule,
                'mode_lent_secondes' => $s->mode_lent_secondes,
                'regles' => $s->regles,
                'ordre' => $s->ordre,
                'archive' => $s->archive_at !== null,
                'messages_semaine' => SalonMessage::where('salon_id', $s->id)->where('created_at', '>=', $semaine)->whereNull('supprime_at')->count(),
                'actifs_semaine' => SalonMessage::where('salon_id', $s->id)->where('created_at', '>=', $semaine)->distinct()->count('user_id'),
                'membres' => SalonMembre::where('salon_id', $s->id)->whereNull('exclu_at')->count(),
                'referents' => $referents->map(fn (SalonMembre $e) => Communaute::auteur($e->user))->filter()->values(),
                'sanctions' => SalonMembre::with('user:id,prenom,nom')->where('salon_id', $s->id)
                    ->where(fn ($q) => $q->whereNotNull('exclu_at')->orWhere('silence_jusqua', '>', now()))->get()
                    ->map(fn (SalonMembre $e) => [
                        'user' => Communaute::auteur($e->user),
                        'exclu' => $e->exclu_at !== null,
                        'silence_jusqua' => $e->silence_jusqua?->isFuture() ? $e->silence_jusqua->toIso8601String() : null,
                    ])->values(),
            ];
        });

        $signalements = SalonSignalement::with(['message.user:id,prenom,nom,photo,role', 'message.salon:id,nom,slug,emoji', 'user:id,prenom,nom'])
            ->orderByRaw("case when statut = 'nouveau' then 0 else 1 end")->latest('id')->limit(60)->get()
            ->filter(fn (SalonSignalement $r) => $r->message !== null)
            ->groupBy('salon_message_id')
            ->map(function ($liste) {
                $r = $liste->first();
                $m = $r->message;

                return [
                    'message_id' => $m->id,
                    'statut' => $liste->contains('statut', 'nouveau') ? 'nouveau' : 'traite',
                    'decision' => $liste->firstWhere('decision', '!=', null)?->decision,
                    'nombre' => $liste->count(),
                    'motifs' => $liste->pluck('motif')->filter()->values(),
                    'signale_par' => $liste->map(fn ($x) => $x->user ? trim($x->user->prenom.' '.$x->user->nom) : null)->filter()->values(),
                    'date' => $liste->max('created_at')?->toIso8601String(),
                    'salon' => $m->salon ? ['id' => $m->salon->id, 'nom' => $m->salon->nom, 'emoji' => $m->salon->emoji] : null,
                    'message' => [
                        'type' => $m->type,
                        'body' => $m->body,
                        'extrait' => Communaute::extrait($m),
                        'fichier_nom' => $m->fichier_nom,
                        'a_fichier' => $m->fichier !== null,
                        'date' => $m->created_at?->toIso8601String(),
                        'supprime' => $m->estSupprime(),
                        'auteur' => Communaute::auteur($m->user),
                    ],
                ];
            })->values();

        $journal = SalonModeration::with(['acteur:id,prenom,nom', 'cible:id,prenom,nom', 'salon:id,nom'])->latest('id')->limit(80)->get()
            ->map(fn (SalonModeration $j) => [
                'id' => $j->id,
                'date' => $j->created_at?->toIso8601String(),
                'action' => SalonModeration::ACTIONS[$j->action] ?? $j->action,
                'detail' => $j->detail,
                'salon' => $j->salon?->nom,
                'acteur' => $j->acteur ? trim($j->acteur->prenom.' '.$j->acteur->nom) : null,
                'cible' => $j->cible ? trim($j->cible->prenom.' '.$j->cible->nom) : null,
            ]);

        return response()->json([
            'ok' => true,
            'salons' => $salons,
            'signalements' => $signalements,
            'journal' => $journal,
            'mots_interdits' => implode("\n", Communaute::motsInterdits()),
            'stats' => [
                'messages_semaine' => SalonMessage::where('created_at', '>=', $semaine)->whereNull('supprime_at')->count(),
                'actifs_semaine' => SalonMessage::where('created_at', '>=', $semaine)->distinct()->count('user_id'),
                'membres_venus' => SalonMembre::distinct()->count('user_id'),
                'signalements' => SalonSignalement::where('statut', 'nouveau')->distinct()->count('salon_message_id'),
            ],
            'modes_lents' => Salon::MODES_LENTS,
        ]);
    }

    /** POST /admin/communaute/salons — créer un salon. */
    public function creer(Request $request)
    {
        if ($erreur = $this->valider($request)) {
            return $erreur;
        }
        $salon = Salon::create($this->donnees($request) + [
            'slug' => $this->slugUnique((string) $request->input('nom')),
            'ordre' => (int) Salon::max('ordre') + 1,
        ]);
        Communaute::journal($salon, $request->user(), 'salon_cree', $salon->nom);

        return response()->json(['ok' => true, 'id' => $salon->id]);
    }

    /** PUT /admin/communaute/salons/{id} — modifier un salon. */
    public function modifier(Request $request, int $id)
    {
        $salon = Salon::find($id);
        if (! $salon) {
            return response()->json(['ok' => false, 'message' => 'Salon introuvable.'], 404);
        }
        if ($erreur = $this->valider($request)) {
            return $erreur;
        }
        $salon->fill($this->donnees($request))->save();
        Communaute::journal($salon, $request->user(), 'salon_modifie', $salon->nom);

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communaute/salons/{id}/archiver|restaurer */
    public function archiver(Request $request, int $id, string $action)
    {
        $salon = Salon::find($id);
        if (! $salon || ! in_array($action, ['archiver', 'restaurer'], true)) {
            return response()->json(['ok' => false, 'message' => 'Salon introuvable.'], 404);
        }
        if ($action === 'archiver' && $salon->estAnnonces() && Salon::where('type', 'annonces')->whereNull('archive_at')->count() <= 1) {
            return response()->json(['ok' => false, 'message' => 'Le canal Annonces ne peut pas être archivé : c\'est le seul.'], 422);
        }
        $salon->forceFill(['archive_at' => $action === 'archiver' ? now() : null])->save();
        Communaute::journal($salon, $request->user(), $action === 'archiver' ? 'salon_archive' : 'salon_restaure', $salon->nom);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/communaute/ordre — ordre d'affichage des salons. */
    public function ordre(Request $request)
    {
        foreach (array_values((array) $request->input('ids', [])) as $i => $id) {
            Salon::where('id', (int) $id)->update(['ordre' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communaute/salons/{id}/referents — nommer un référent ; DELETE .../{userId} pour le retirer. */
    public function nommerReferent(Request $request, int $id)
    {
        $salon = Salon::find($id);
        $user = User::where('is_active', true)->find((int) $request->input('user_id'));
        if (! $salon || ! $user) {
            return response()->json(['ok' => false, 'message' => 'Salon ou membre introuvable.'], 404);
        }
        $etat = Communaute::etat($salon, $user);
        $etat->forceFill(['role' => 'moderateur', 'exclu_at' => null, 'silence_jusqua' => null])->save();
        Communaute::journal($salon, $request->user(), 'moderateur_nomme', trim($user->prenom.' '.$user->nom), $user->id);
        \App\Models\MemberNotification::create([
            'user_id' => $user->id,
            'type' => 'communaute',
            'title' => 'Vous êtes référent du salon « '.$salon->nom.' »',
            'body' => 'Vous pouvez désormais modérer ce salon : supprimer un message, rendre un membre muet, activer le mode lent ou la lecture seule.',
            'link' => Communaute::lien($salon),
        ]);

        return response()->json(['ok' => true]);
    }

    public function retirerReferent(Request $request, int $id, int $userId)
    {
        $salon = Salon::find($id);
        $etat = $salon ? SalonMembre::where('salon_id', $salon->id)->where('user_id', $userId)->first() : null;
        if (! $etat) {
            return response()->json(['ok' => false, 'message' => 'Référent introuvable.'], 404);
        }
        $etat->forceFill(['role' => 'membre'])->save();
        $u = User::find($userId);
        Communaute::journal($salon, $request->user(), 'moderateur_retire', $u ? trim($u->prenom.' '.$u->nom) : null, $userId);

        return response()->json(['ok' => true]);
    }

    /** GET /admin/communaute/salons/{id}/messages?before= — fil du salon pour la modération (messages supprimés compris). */
    public function messages(Request $request, int $id)
    {
        $salon = Salon::find($id);
        if (! $salon) {
            return response()->json(['ok' => false, 'message' => 'Salon introuvable.'], 404);
        }
        $before = (int) $request->query('before', 0);
        $autour = (int) $request->query('autour', 0);
        $q = SalonMessage::with(['user:id,prenom,nom,photo,role', 'reponseA.user:id,prenom,nom'])->where('salon_id', $salon->id);
        if ($autour > 0) {
            $avant = (clone $q)->where('id', '<=', $autour)->latest('id')->limit(15)->get();
            $apres = (clone $q)->where('id', '>', $autour)->orderBy('id')->limit(15)->get();
            $messages = $avant->concat($apres)->sortBy('id')->values();
        } else {
            $messages = $q->when($before > 0, fn ($w) => $w->where('id', '<', $before))->latest('id')->limit(60)->get()->sortBy('id')->values();
        }
        $moi = $request->user();

        return response()->json([
            'ok' => true,
            'salon' => ['id' => $salon->id, 'nom' => $salon->nom, 'emoji' => $salon->emoji, 'slug' => $salon->slug],
            'messages' => $messages->map(fn (SalonMessage $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'body' => $m->body,
                'extrait' => Communaute::extrait($m),
                'fichier_nom' => $m->fichier_nom,
                'a_fichier' => $m->fichier !== null,
                'date' => $m->created_at?->toIso8601String(),
                'edite' => $m->edite_at !== null,
                'supprime' => $m->estSupprime(),
                'auteur' => Communaute::auteur($m->user),
                'reponse_a' => $m->reponseA ? Communaute::extrait($m->reponseA) : null,
                'signale' => $m->signalements()->where('statut', 'nouveau')->exists(),
                'cible' => $m->id === $autour,
            ])->values(),
            'moi' => $moi->id,
        ]);
    }

    /** DELETE /admin/communaute/messages/{id} — supprimer un message. */
    public function supprimerMessage(Request $request, int $id)
    {
        $m = SalonMessage::with('salon')->find($id);
        if (! $m) {
            return response()->json(['ok' => false, 'message' => 'Message introuvable.'], 404);
        }
        if (! $m->estSupprime()) {
            $m->forceFill(['supprime_at' => now(), 'supprime_par' => $request->user()->id])->save();
            Communaute::journal($m->salon, $request->user(), 'message_supprime', Communaute::extrait($m), $m->user_id, $m->id);
        }
        SalonSignalement::where('salon_message_id', $m->id)->where('statut', 'nouveau')
            ->update(['statut' => 'traite', 'decision' => 'supprime', 'traite_par' => $request->user()->id, 'traite_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/communaute/signalements/{messageId}/classer — classer sans suite. */
    public function classer(Request $request, int $messageId)
    {
        $m = SalonMessage::with('salon')->find($messageId);
        $n = SalonSignalement::where('salon_message_id', $messageId)->where('statut', 'nouveau')
            ->update(['statut' => 'traite', 'decision' => 'classe', 'traite_par' => $request->user()->id, 'traite_at' => now()]);
        if ($n && $m) {
            Communaute::journal($m->salon, $request->user(), 'signalement_classe', Communaute::extrait($m), $m->user_id, $m->id);
        }

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communaute/salons/{id}/membres/{userId}/{action} — sanctions. */
    public function sanctionner(Request $request, int $id, int $userId, string $action)
    {
        $salon = Salon::find($id);
        if (! $salon) {
            return response()->json(['ok' => false, 'message' => 'Salon introuvable.'], 404);
        }

        return CommunauteController::appliquerSanction($request->user(), $salon, $userId, $action, $request->input('duree'));
    }

    /** PUT /admin/communaute/mots-interdits */
    public function motsInterdits(Request $request)
    {
        $texte = (string) $request->input('texte', '');
        if (mb_strlen($texte) > 5000) {
            return response()->json(['ok' => false, 'message' => 'La liste est trop longue (5 000 caractères maximum).'], 422);
        }
        Communaute::enregistrerMotsInterdits($texte);
        Communaute::journal(null, $request->user(), 'mots_interdits', count(Communaute::motsInterdits()).' terme(s)');

        return response()->json(['ok' => true, 'mots' => implode("\n", Communaute::motsInterdits())]);
    }

    /** GET /admin/communaute/membres?q= — recherche d'un membre (référent). */
    public function membres(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'membres' => []]);
        }
        $membres = User::where('is_active', true)->whereNull('anonymise_at')
            ->where(fn ($w) => $w->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('prenom')->limit(10)->get(['id', 'prenom', 'nom', 'photo', 'role']);

        return response()->json(['ok' => true, 'membres' => $membres->map(fn ($u) => Communaute::auteur($u))->values()]);
    }

    /**
     * GET /admin/communaute/statistiques — animation de la Communauté :
     * activité des 30 derniers jours, tendance de chaque salon (salons qui
     * s'essoufflent), membres les plus actifs.
     */
    public function statistiques()
    {
        $debut = now()->subDays(29)->startOfDay();
        $messages = SalonMessage::where('created_at', '>=', $debut)->whereNull('supprime_at')->get(['id', 'salon_id', 'user_id', 'type', 'created_at']);

        $parJour = collect(range(0, 29))->map(function ($i) use ($debut, $messages) {
            $jour = $debut->copy()->addDays($i);
            $duJour = $messages->filter(fn ($m) => $m->created_at->isSameDay($jour));

            return [
                'date' => $jour->toDateString(),
                'messages' => $duJour->count(),
                'membres' => $duJour->pluck('user_id')->unique()->count(),
            ];
        })->values();

        $semaine = now()->subDays(7);
        $precedente = now()->subDays(14);
        $salons = Salon::whereNull('archive_at')->orderBy('ordre')->orderBy('id')->get()->map(function (Salon $s) use ($semaine, $precedente) {
            $base = SalonMessage::where('salon_id', $s->id)->whereNull('supprime_at');
            $cette = (clone $base)->where('created_at', '>=', $semaine)->count();
            $avant = (clone $base)->whereBetween('created_at', [$precedente, $semaine])->count();
            $dernier = (clone $base)->max('created_at');
            $tendance = $avant > 0 ? (int) round(($cette - $avant) * 100 / $avant) : null;
            $alerte = match (true) {
                $s->estAnnonces() => null,
                ! $dernier => 'Aucun message pour l\'instant',
                \Illuminate\Support\Carbon::parse($dernier)->lt(now()->subDays(7)) => 'Aucun message depuis plus de 7 jours',
                $avant >= 5 && $cette < $avant / 2 => 'Activité en forte baisse',
                default => null,
            };

            return [
                'id' => $s->id,
                'nom' => $s->nom,
                'emoji' => $s->emoji,
                'annonces' => $s->estAnnonces(),
                'semaine' => $cette,
                'semaine_precedente' => $avant,
                'tendance' => $tendance,
                'mois' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
                'actifs_semaine' => (clone $base)->where('created_at', '>=', $semaine)->distinct()->count('user_id'),
                'dernier' => $dernier ? \Illuminate\Support\Carbon::parse($dernier)->toIso8601String() : null,
                'alerte' => $alerte,
            ];
        })->values();

        $contributeurs = $messages->groupBy('user_id')->map->count()->sortDesc()->take(10);
        $noms = User::whereIn('id', $contributeurs->keys())->get(['id', 'prenom', 'nom', 'photo', 'role'])->keyBy('id');

        return response()->json([
            'ok' => true,
            'par_jour' => $parJour,
            'salons' => $salons,
            'contributeurs' => $contributeurs->map(fn ($n, $id) => ['membre' => Communaute::auteur($noms->get($id)), 'messages' => $n])->filter(fn ($c) => $c['membre'])->values(),
            'totaux' => [
                'messages' => $messages->count(),
                'membres' => $messages->pluck('user_id')->unique()->count(),
                'sondages' => $messages->where('type', 'sondage')->count(),
                'votes' => \App\Models\SalonSondageVote::where('created_at', '>=', $debut)->distinct()->count('user_id'),
                'reactions' => \App\Models\SalonReaction::where('created_at', '>=', $debut)->count(),
                'partages' => $messages->where('type', 'carte')->count(),
            ],
        ]);
    }

    // ── Outils internes ──────────────────────────────────────────────────

    private function valider(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nom' => 'required|string|max:80',
            'description' => 'nullable|string|max:300',
            'emoji' => 'nullable|string|max:16',
            'type' => 'required|in:annonces,discussion',
            'acces' => 'required|in:tous,abonnes',
            'lecture_seule' => 'nullable|boolean',
            'mode_lent_secondes' => 'nullable|integer|in:'.implode(',', array_keys(Salon::MODES_LENTS)),
            'regles' => 'nullable|string|max:2000',
        ], [
            'nom.required' => 'Donnez un nom au salon.',
            'nom.max' => 'Le nom du salon est trop long (80 caractères maximum).',
        ]);

        return $v->fails() ? response()->json(['ok' => false, 'message' => $v->errors()->first()], 422) : null;
    }

    private function donnees(Request $request): array
    {
        $regles = trim((string) $request->input('regles', ''));

        return [
            'nom' => trim((string) $request->input('nom')),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'emoji' => trim((string) $request->input('emoji', '')) ?: null,
            'type' => (string) $request->input('type'),
            'acces' => (string) $request->input('acces'),
            'lecture_seule' => $request->boolean('lecture_seule'),
            'mode_lent_secondes' => (int) $request->input('mode_lent_secondes', 0),
            'regles' => $regles !== '' ? $regles : null,
        ];
    }

    private function slugUnique(string $nom): string
    {
        $base = Str::slug($nom) ?: 'salon';
        $slug = $base;
        for ($i = 2; Salon::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
