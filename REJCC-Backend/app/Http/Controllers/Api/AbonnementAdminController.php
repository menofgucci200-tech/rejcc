<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AbonnementRappel;
use App\Models\MemberNotification;
use App\Models\Payment;
use App\Models\User;
use App\Support\Abonnement;
use App\Support\Mailer;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Suivi des abonnements : abonnés, délais de grâce, échéances à venir,
 * recettes, paiements, tarif. L'activation se fait uniquement par paiement
 * en ligne : l'équipe consulte et relance, elle n'active pas.
 */
class AbonnementAdminController extends Controller
{
    private function membres()
    {
        return User::whereNotIn('role', ['admin', 'mentor']);
    }

    private function filtrer($q, string $statut)
    {
        $g = Abonnement::GRACE_JOURS;

        return match ($statut) {
            'actifs' => $q->where('subscription_expires_at', '>', now()),
            'grace' => $q->whereBetween('subscription_expires_at', [now()->subDays($g), now()]),
            'echeance' => $q->whereBetween('subscription_expires_at', [now(), now()->addDays(30)]),
            'expires' => $q->where('subscription_expires_at', '<', now()->subDays($g)),
            'jamais' => $q->whereNull('subscription_expires_at'),
            default => $q,
        };
    }

    public function index(Request $request)
    {
        $statut = (string) $request->query('statut', '');
        $query = $this->filtrer($this->membres(), $statut);
        if ($q = trim((string) $request->query('q', ''))) {
            RechercheMots::appliquer($query, $q, ['prenom', 'nom', 'email', 'telephone']);
        }
        $page = $query->orderByRaw('subscription_expires_at IS NULL')->orderBy('subscription_expires_at')->paginate(25);
        $derniers = Payment::where('type', 'abonnement')->where('status', 'success')
            ->whereIn('beneficiaire_id', collect($page->items())->pluck('id'))->orWhere(fn ($w) => $w->where('type', 'abonnement')->where('status', 'success')
            ->whereNull('beneficiaire_id')->whereIn('user_id', collect($page->items())->pluck('id')))
            ->latest('paye_at')->get()->groupBy(fn ($p) => $p->beneficiaire_id ?? $p->user_id);

        $ok = Payment::where('type', 'abonnement')->where('status', 'success');

        return response()->json([
            'ok' => true,
            'montant' => Abonnement::montant(),
            'grace_jours' => Abonnement::GRACE_JOURS,
            'stats' => [
                'actifs' => $this->filtrer($this->membres(), 'actifs')->count(),
                'grace' => $this->filtrer($this->membres(), 'grace')->count(),
                'echeance' => $this->filtrer($this->membres(), 'echeance')->count(),
                'expires' => $this->filtrer($this->membres(), 'expires')->count(),
                'jamais' => $this->filtrer($this->membres(), 'jamais')->count(),
                'recettes_mois' => (int) (clone $ok)->where('paye_at', '>=', now()->startOfMonth())->sum('amount'),
                'recettes_annee' => (int) (clone $ok)->where('paye_at', '>=', now()->startOfYear())->sum('amount'),
                'paiements_annee' => (clone $ok)->where('paye_at', '>=', now()->startOfYear())->count(),
                'offerts_annee' => (clone $ok)->where('paye_at', '>=', now()->startOfYear())->whereNotNull('beneficiaire_id')->count(),
                'en_attente' => Payment::where('type', 'abonnement')->where('status', 'pending')->count(),
            ],
            'membres' => collect($page->items())->map(function (User $u) use ($derniers) {
                $dernier = $derniers->get($u->id)?->first();
                $exp = $u->subscription_expires_at;

                return [
                    'id' => $u->id, 'nom' => trim($u->prenom.' '.$u->nom), 'email' => $u->email, 'telephone' => $u->telephone,
                    'numero' => $u->memberNumber(), 'photo' => $u->photo,
                    'expire_le' => $exp?->toDateString(),
                    'statut' => match (true) {
                        ! $exp => 'jamais',
                        $exp->isFuture() => $exp->lte(now()->addDays(30)) ? 'echeance' : 'actif',
                        $u->abonnementEnGrace() => 'grace',
                        default => 'expire',
                    },
                    'dernier_paiement' => $dernier ? ['le' => $dernier->paye_at?->toDateString(), 'moyen' => $dernier->moyen, 'offert' => $dernier->estOffert()] : null,
                    'relance_le' => $u->abonnement_rappel ? explode(':', $u->abonnement_rappel)[1] ?? null : null,
                ];
            })->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function paiements(Request $request)
    {
        $query = Payment::with(['user:id,prenom,nom,email', 'beneficiaire:id,prenom,nom'])->where('type', 'abonnement')
            ->when(array_key_exists((string) $request->query('statut'), Payment::STATUTS), fn ($w) => $w->where('status', $request->query('statut')));
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('reference', 'like', "%{$q}%")->orWhere('recu_numero', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => RechercheMots::appliquer($u, $q, ['prenom', 'nom', 'email']))
                ->orWhereHas('beneficiaire', fn ($u) => RechercheMots::appliquer($u, $q, ['prenom', 'nom'])));
        }
        $page = $query->latest()->paginate(30);

        return response()->json([
            'ok' => true,
            'paiements' => collect($page->items())->map(fn (Payment $p) => [
                'reference' => $p->reference, 'recu' => $p->recu_numero, 'montant' => (int) $p->amount, 'statut' => $p->status,
                'statut_label' => Payment::STATUTS[$p->status] ?? $p->status, 'moyen' => $p->moyen,
                'payeur' => trim(($p->user?->prenom ?? '').' '.($p->user?->nom ?? '')), 'email' => $p->user?->email,
                'beneficiaire' => $p->estOffert() ? trim($p->beneficiaire?->prenom.' '.$p->beneficiaire?->nom) : null,
                'periode_fin' => $p->periode_fin?->toDateString(),
                'created_at' => $p->created_at?->toIso8601String(), 'paye_at' => $p->paye_at?->toIso8601String(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function recu(string $ref)
    {
        $p = Payment::where('reference', $ref)->where('type', 'abonnement')->where('status', 'success')->first();
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Reçu introuvable.'], 404);
        }

        return response(Storage::disk('local')->get(Abonnement::recu($p)), 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$p->recu_numero.'.pdf"',
        ]);
    }

    public function tarif(Request $request)
    {
        $m = (int) $request->input('montant');
        if ($m < 500 || $m > 1000000) {
            return response()->json(['ok' => false, 'message' => 'Indiquez un montant entre 500 et 1 000 000 F CFA.'], 422);
        }
        Abonnement::definirMontant($m);

        return response()->json(['ok' => true, 'montant' => $m]);
    }

    /** Relance personnelle d'un membre (notification + email). */
    public function relancer(Request $request, int $id)
    {
        $u = $this->membres()->find($id);
        if (! $u) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable.'], 404);
        }
        $texte = trim((string) $request->input('message')) ?: ($u->subscription_expires_at
            ? 'Votre abonnement annuel au REJCC '.($u->subscription_expires_at->isFuture() ? 'expire le ' : 'a expiré le ').$u->subscription_expires_at->locale('fr')->isoFormat('D MMMM YYYY').' : pensez à le renouveler pour garder l\'accès à toutes les fonctionnalités du réseau.'
            : 'Activez votre abonnement annuel au REJCC pour accéder à la carte membre, à l\'annuaire, à la messagerie, à la Marketplace et aux projets.');
        $titre = 'Votre abonnement REJCC';
        MemberNotification::create(['user_id' => $u->id, 'type' => 'info', 'title' => $titre, 'body' => $texte, 'link' => '/espace-membre/abonnement']);
        Mailer::send($u->email, new AbonnementRappel($u, $titre, $texte));

        return response()->json(['ok' => true, 'message' => 'Relance envoyée à '.trim($u->prenom.' '.$u->nom).'.']);
    }
}
