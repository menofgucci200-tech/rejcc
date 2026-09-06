<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Abonnement annuel (10 000 F XOF) donnant accès aux fonctionnalités
 * premium (carte membre, annuaire, messagerie, marketplace, projets &
 * incubateur). Paiement encaissé via CinetPay (Wave / Orange Money / MTN
 * / Moov / carte), qui agrège ces moyens de paiement derrière une seule
 * API — évite d'intégrer chaque opérateur séparément.
 */
class SubscriptionController extends Controller
{
    public const AMOUNT = 10000;

    public const CURRENCY = 'XOF';

    /** Statut d'abonnement de l'utilisateur connecté + historique des paiements. */
    public function status(Request $request)
    {
        $user = $request->user();

        // Retour depuis la page de paiement CinetPay : on vérifie tout de
        // suite ce paiement plutôt que d'attendre le webhook `notify`.
        $ref = $request->query('ref');
        if ($ref) {
            $payment = Payment::where('user_id', $user->id)
                ->where('reference', $ref)
                ->where('type', 'abonnement')
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                $this->verify($payment);
                $user->refresh();
            }
        }

        return response()->json([
            'ok' => true,
            'active' => $user->hasActiveSubscription(),
            'expires_at' => $user->subscription_expires_at?->toDateString(),
            'amount' => self::AMOUNT,
            'currency' => self::CURRENCY,
            'history' => $user->payments()
                ->where('type', 'abonnement')
                ->latest()
                ->limit(10)
                ->get(['reference', 'provider', 'amount', 'currency', 'status', 'created_at']),
        ]);
    }

    /** Initie un paiement CinetPay et renvoie l'URL de la page de paiement hébergée. */
    public function initiate(Request $request)
    {
        $user = $request->user();

        if ($user->hasActiveSubscription()) {
            return response()->json(['ok' => false, 'message' => 'Votre abonnement est déjà actif.'], 422);
        }

        $apiKey = $this->cinetpayApiKey();
        $siteId = $this->cinetpaySiteId();

        if (! $apiKey || ! $siteId) {
            return response()->json(['ok' => false, 'message' => "Le paiement en ligne n'est pas encore configuré. Contactez un administrateur."], 503);
        }

        $reference = 'ABO-'.$user->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'abonnement',
            'reference' => $reference,
            'provider' => 'cinetpay',
            'amount' => self::AMOUNT,
            'currency' => self::CURRENCY,
            'status' => 'pending',
        ]);

        try {
            $response = Http::asJson()->timeout(15)->post(rtrim(config('services.cinetpay.base_url'), '/').'/v2/payment', [
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => $reference,
                'amount' => self::AMOUNT,
                'currency' => self::CURRENCY,
                'description' => 'Abonnement annuel REJCC',
                'notify_url' => rtrim(config('app.url'), '/').'/api/subscription/notify',
                'return_url' => rtrim(config('app.frontend_url'), '/').'/espace-membre/abonnement?ref='.$reference,
                'channels' => 'ALL',
                'customer_name' => $user->nom ?? $user->name,
                'customer_surname' => $user->prenom ?? '',
                'customer_email' => $user->email,
                'customer_phone_number' => $user->telephone,
            ]);
        } catch (\Throwable $e) {
            Log::warning('CinetPay initiate a échoué', ['reference' => $reference, 'error' => $e->getMessage()]);
            $payment->update(['status' => 'failed']);

            return response()->json(['ok' => false, 'message' => 'Impossible de contacter le service de paiement. Réessayez plus tard.'], 502);
        }

        $body = $response->json() ?? [];

        if ((string) ($body['code'] ?? null) !== '201' || empty($body['data']['payment_url'])) {
            Log::warning('CinetPay initiate refusé', ['reference' => $reference, 'response' => $body]);
            $payment->update(['status' => 'failed']);

            return response()->json(['ok' => false, 'message' => $body['message'] ?? "Impossible d'initier le paiement pour le moment."], 502);
        }

        return response()->json(['ok' => true, 'payment_url' => $body['data']['payment_url'], 'reference' => $reference]);
    }

    /** Webhook CinetPay (notification serveur à serveur, POST). */
    public function notify(Request $request)
    {
        $reference = $request->input('cpm_trans_id') ?? $request->input('transaction_id');

        $payment = $reference
            ? Payment::where('reference', $reference)->where('type', 'abonnement')->first()
            : null;

        if ($payment && $payment->status === 'pending') {
            $this->verify($payment);
        }

        return response('OK', 200);
    }

    /** Interroge l'API CinetPay pour connaître le statut réel d'une transaction (source de vérité). */
    private function verify(Payment $payment): void
    {
        try {
            $response = Http::asJson()->timeout(15)->post(rtrim(config('services.cinetpay.base_url'), '/').'/v2/payment/check', [
                'apikey' => $this->cinetpayApiKey(),
                'site_id' => $this->cinetpaySiteId(),
                'transaction_id' => $payment->reference,
            ]);
        } catch (\Throwable $e) {
            Log::warning('CinetPay check a échoué', ['reference' => $payment->reference, 'error' => $e->getMessage()]);

            return;
        }

        $status = $response->json('data.status');

        if ($status === 'ACCEPTED') {
            $payment->update([
                'status' => 'success',
                'transaction_id' => $response->json('data.operator_id') ?? $response->json('data.payment_method'),
            ]);

            $user = $payment->user;
            if ($user) {
                $base = ($user->subscription_expires_at && $user->subscription_expires_at->isFuture())
                    ? $user->subscription_expires_at
                    : now();
                $user->subscription_expires_at = $base->copy()->addYear();
                $user->save();
            }
        } elseif (in_array($status, ['REFUSED', 'CANCELLED'], true)) {
            $payment->update(['status' => 'failed']);
        }
    }

    /**
     * Identifiants CinetPay : priorité aux réglages saisis par l'admin
     * (Réglages du site → Paiement), sinon repli sur le `.env` du serveur.
     */
    private function cinetpayApiKey(): ?string
    {
        return $this->siteSetting('payment.cinetpay_api_key') ?: config('services.cinetpay.api_key');
    }

    private function cinetpaySiteId(): ?string
    {
        return $this->siteSetting('payment.cinetpay_site_id') ?: config('services.cinetpay.site_id');
    }

    private function siteSetting(string $key): ?string
    {
        // ->first()->value (pas ->value('value')) : la colonne est castée en
        // JSON, un accès direct en query builder renverrait la valeur brute
        // encodée (avec guillemets) plutôt que la chaîne décodée.
        $value = SiteSetting::where('key', $key)->first()?->value;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
