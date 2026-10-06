<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Support\Abonnement;
use App\Support\RechercheMots;
use App\Support\SubscriptionMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Abonnement annuel au REJCC (tarif réglable par l'administration) donnant
 * accès aux fonctionnalités réservées aux membres abonnés. Paiement en ligne
 * uniquement, via CinetPay (Wave, Orange Money, MTN, Moov, carte). Un membre
 * peut aussi offrir l'abonnement d'un autre membre.
 */
class SubscriptionController extends Controller
{
    private function paiementPayload(Payment $p, User $moi): array
    {
        $benef = $p->beneficiaire ?? $p->user;

        return [
            'reference' => $p->reference,
            'montant' => (int) $p->amount,
            'devise' => $p->currency,
            'statut' => $p->status,
            'statut_label' => Payment::STATUTS[$p->status] ?? $p->status,
            'moyen' => $p->moyen,
            'created_at' => $p->created_at?->toIso8601String(),
            'paye_at' => $p->paye_at?->toIso8601String(),
            'periode_debut' => $p->periode_debut?->toDateString(),
            'periode_fin' => $p->periode_fin?->toDateString(),
            'recu' => $p->status === 'success' ? $p->recu_numero : null,
            'offert' => $p->estOffert(),
            // Abonnement offert : à qui (pour le payeur) ou par qui (pour le bénéficiaire).
            'pour' => $p->estOffert() && $p->user_id === $moi->id ? trim($benef?->prenom.' '.$benef?->nom) : null,
            'par' => $p->estOffert() && $p->user_id !== $moi->id ? trim($p->user?->prenom.' '.$p->user?->nom) : null,
        ];
    }

    /** Statut d'abonnement, paiements (les siens, ceux qu'il a offerts et ceux reçus). */
    public function status(Request $request)
    {
        $user = $request->user();

        // Retour depuis la page CinetPay : vérification immédiate de ce paiement.
        if ($ref = $request->query('ref')) {
            $p = Payment::where('reference', $ref)->where('type', 'abonnement')->where('user_id', $user->id)->first();
            if ($p) {
                Abonnement::verifier($p);
                $user->refresh();
            }
        }

        $paiements = Payment::with(['user:id,prenom,nom', 'beneficiaire:id,prenom,nom'])->where('type', 'abonnement')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('beneficiaire_id', $user->id))
            ->latest()->limit(20)->get();
        $exp = $user->subscription_expires_at;

        return response()->json([
            'ok' => true,
            'active' => $user->hasPaidSubscription(),
            'exempt' => $user->isExemptFromSubscription(),
            'role' => $user->role,
            'enforced' => SubscriptionMode::enforced(),
            'expires_at' => $exp?->toDateString(),
            'jours_restants' => $exp && $exp->isFuture() ? (int) ceil(now()->diffInDays($exp)) : null,
            'grace' => $user->abonnementEnGrace(),
            'grace_fin' => $exp ? $exp->copy()->addDays(Abonnement::GRACE_JOURS)->toDateString() : null,
            'grace_jours' => Abonnement::GRACE_JOURS,
            'peut_renouveler' => Abonnement::peutRenouveler($user),
            'amount' => Abonnement::montant(),
            'currency' => Abonnement::DEVISE,
            'en_attente' => $paiements->first(fn ($p) => $p->status === 'pending' && $p->user_id === $user->id && ! $p->estOffert())
                ? $this->paiementPayload($paiements->first(fn ($p) => $p->status === 'pending' && $p->user_id === $user->id && ! $p->estOffert()), $user) : null,
            'history' => $paiements->map(fn ($p) => $this->paiementPayload($p, $user))->values(),
        ]);
    }

    /**
     * Initie un paiement CinetPay pour soi ou, avec `beneficiaire_id`, pour
     * offrir l'abonnement à un autre membre. Renvoie l'URL de paiement.
     */
    public function initiate(Request $request)
    {
        $user = $request->user();
        if (! SubscriptionMode::enforced()) {
            return response()->json(['ok' => false, 'message' => 'Les abonnements ne sont pas encore ouverts : toutes les fonctionnalités sont accessibles gratuitement pour le moment.'], 422);
        }

        $beneficiaire = $user;
        if ($request->filled('beneficiaire_id')) {
            $beneficiaire = User::where('is_active', true)->find((int) $request->input('beneficiaire_id'));
            if (! $beneficiaire || $beneficiaire->id === $user->id) {
                return response()->json(['ok' => false, 'message' => 'Membre introuvable.'], 404);
            }
        }
        if ($beneficiaire->isExemptFromSubscription()) {
            return response()->json(['ok' => false, 'message' => $beneficiaire->id === $user->id
                ? 'Votre statut vous dispense d\'abonnement : vous avez accès à toutes les fonctionnalités.'
                : trim($beneficiaire->prenom.' '.$beneficiaire->nom).' est dispensé(e) d\'abonnement.'], 422);
        }
        if (! Abonnement::peutRenouveler($beneficiaire)) {
            return response()->json(['ok' => false, 'message' => $beneficiaire->id === $user->id
                ? 'Votre abonnement est déjà actif. Le renouvellement sera possible dans les 30 jours avant son échéance.'
                : trim($beneficiaire->prenom.' '.$beneficiaire->nom).' est déjà abonné(e) : un nouvel abonnement pourra lui être offert dans les 30 jours avant son échéance.'], 422);
        }

        $apiKey = Abonnement::cinetpayApiKey();
        $siteId = Abonnement::cinetpaySiteId();
        if (! $apiKey || ! $siteId) {
            return response()->json(['ok' => false, 'message' => "Le paiement en ligne n'est pas encore configuré. Contactez un administrateur."], 503);
        }

        $montant = Abonnement::montant();
        $reference = 'ABO-'.$user->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        $p = Payment::create([
            'user_id' => $user->id,
            'beneficiaire_id' => $beneficiaire->id !== $user->id ? $beneficiaire->id : null,
            'type' => 'abonnement', 'reference' => $reference, 'provider' => 'cinetpay',
            'amount' => $montant, 'currency' => Abonnement::DEVISE, 'status' => 'pending',
        ]);

        try {
            $r = Http::asJson()->timeout(15)->post(rtrim((string) config('services.cinetpay.base_url'), '/').'/v2/payment', [
                'apikey' => $apiKey, 'site_id' => $siteId, 'transaction_id' => $reference,
                'amount' => $montant, 'currency' => Abonnement::DEVISE,
                'description' => $p->beneficiaire_id ? 'Abonnement REJCC offert' : 'Abonnement annuel REJCC',
                'notify_url' => rtrim((string) config('app.url'), '/').'/api/subscription/notify',
                'return_url' => rtrim((string) config('app.frontend_url'), '/').'/espace-membre/abonnement?ref='.$reference,
                'channels' => 'ALL',
                'customer_name' => $user->nom ?? $user->name, 'customer_surname' => $user->prenom ?? '',
                'customer_email' => $user->email, 'customer_phone_number' => $user->telephone,
            ]);
        } catch (\Throwable $e) {
            Log::warning('CinetPay initiate a échoué', ['reference' => $reference, 'error' => $e->getMessage()]);
            $p->update(['status' => 'failed']);

            return response()->json(['ok' => false, 'message' => 'Impossible de contacter le service de paiement. Réessayez plus tard.'], 502);
        }
        $body = $r->json() ?? [];
        if ((string) ($body['code'] ?? null) !== '201' || empty($body['data']['payment_url'])) {
            Log::warning('CinetPay initiate refusé', ['reference' => $reference, 'response' => $body]);
            $p->update(['status' => 'failed']);

            return response()->json(['ok' => false, 'message' => $body['message'] ?? "Impossible d'initier le paiement pour le moment."], 502);
        }

        return response()->json(['ok' => true, 'payment_url' => $body['data']['payment_url'], 'reference' => $reference]);
    }

    /** Webhook CinetPay (serveur à serveur) : le statut est toujours revérifié auprès de CinetPay. */
    public function notify(Request $request)
    {
        $ref = $request->input('cpm_trans_id') ?? $request->input('transaction_id');
        $p = $ref ? Payment::where('reference', $ref)->where('type', 'abonnement')->first() : null;
        if ($p && $p->status === 'pending') {
            Abonnement::verifier($p);
        }

        return response('OK', 200);
    }

    /** POST /subscription/verifier/{ref} — « Vérifier mon paiement » (paiement resté en attente). */
    public function verifierPaiement(Request $request, string $ref)
    {
        $p = Payment::where('reference', $ref)->where('type', 'abonnement')->where('user_id', $request->user()->id)->first();
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Paiement introuvable.'], 404);
        }
        $statut = Abonnement::verifier($p);

        return response()->json(['ok' => true, 'statut' => $statut, 'message' => match ($statut) {
            'success' => 'Paiement confirmé : l\'abonnement est actif.',
            'failed' => 'Ce paiement n\'a pas abouti. Vous pouvez en lancer un nouveau.',
            default => 'Le paiement n\'est pas encore confirmé par l\'opérateur. Réessayez dans quelques minutes.',
        }]);
    }

    /** GET /subscription/recus/{ref} — reçu PDF (payeur ou bénéficiaire). */
    public function recu(Request $request, string $ref)
    {
        $moi = $request->user()->id;
        $p = Payment::where('reference', $ref)->where('type', 'abonnement')->where('status', 'success')
            ->where(fn ($q) => $q->where('user_id', $moi)->orWhere('beneficiaire_id', $moi))->first();
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Reçu introuvable.'], 404);
        }

        return response(Storage::disk('local')->get(Abonnement::recu($p)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$p->recu_numero.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * GET /subscription/beneficiaires?q= — membre à qui offrir l'abonnement :
     * recherche par nom parmi les membres visibles dans l'annuaire, ou par
     * numéro de membre exact. Informations minimales, jamais de coordonnées.
     */
    public function beneficiaires(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 3) {
            return response()->json(['ok' => true, 'membres' => []]);
        }
        $moi = $request->user();
        $query = User::where('is_active', true)->where('id', '!=', $moi->id)->whereNotIn('role', ['admin', 'mentor']);
        if (preg_match('/^REJCC-\d{4}-\d{4}-(\d+)$/i', $q, $m)) {
            // Numéro de membre complet (date d'adhésion comprise) : impossible à deviner à partir du seul identifiant.
            $u = (clone $query)->find((int) $m[1]);
            $query->whereKey($u && strtoupper($u->memberNumber()) === strtoupper($q) ? $u->id : 0);
        } else {
            $query->where(fn ($w) => $w->whereNull('preferences->apparaitre_annuaire')->orWhere('preferences->apparaitre_annuaire', true));
            RechercheMots::appliquer($query, $q, ['prenom', 'nom']);
        }

        return response()->json(['ok' => true, 'membres' => $query->limit(8)->get()->map(fn (User $u) => [
            'id' => $u->id, 'nom' => trim($u->prenom.' '.$u->nom), 'ville' => $u->ville, 'photo' => $u->photo,
            'abonne' => $u->hasPaidSubscription() && ! $u->abonnementEnGrace(),
            'peut_recevoir' => Abonnement::peutRenouveler($u),
        ])->values()]);
    }
}
