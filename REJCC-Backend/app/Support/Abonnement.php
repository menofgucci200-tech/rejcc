<?php

namespace App\Support;

use App\Mail\AbonnementConfirme;
use App\Mail\AbonnementRappel;
use App\Models\MemberNotification;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Abonnement annuel au REJCC : tarif réglable, paiement en ligne via CinetPay
 * (seul moyen d'activation), délai de grâce après l'échéance, abonnement
 * offert à un autre membre, reçu numéroté, rappels d'échéance.
 */
class Abonnement
{
    /** Accès maintenu quelques jours après l'échéance, le temps de renouveler. */
    public const GRACE_JOURS = 5;

    public const MONTANT_DEFAUT = 10000;

    public const DEVISE = 'XOF';

    public const CLE_MONTANT = 'abonnement.montant';

    public static function montant(): int
    {
        $v = SiteSetting::where('key', self::CLE_MONTANT)->first()?->value;

        return is_numeric($v) && (int) $v > 0 ? (int) $v : self::MONTANT_DEFAUT;
    }

    public static function definirMontant(int $montant): void
    {
        SiteSetting::updateOrCreate(['key' => self::CLE_MONTANT], ['value' => $montant]);
    }

    /**
     * Le membre peut-il (re)prendre un abonnement maintenant ? Renouvellement
     * ouvert dans les 30 jours qui précèdent l'échéance (ou après).
     */
    public static function peutRenouveler(User $u): bool
    {
        return ! $u->isExemptFromSubscription()
            && (! $u->subscription_expires_at || $u->subscription_expires_at->lte(now()->addDays(30)));
    }

    // ── CinetPay ────────────────────────────────────────────────────────

    public static function cinetpayApiKey(): ?string
    {
        return self::reglage('payment.cinetpay_api_key') ?: config('services.cinetpay.api_key');
    }

    public static function cinetpaySiteId(): ?string
    {
        return self::reglage('payment.cinetpay_site_id') ?: config('services.cinetpay.site_id');
    }

    private static function reglage(string $cle): ?string
    {
        $v = SiteSetting::where('key', $cle)->first()?->value;

        return is_string($v) && $v !== '' ? $v : null;
    }

    /** Interroge CinetPay (source de vérité) et applique le résultat. */
    public static function verifier(Payment $p): string
    {
        if ($p->status !== 'pending') {
            return $p->status;
        }
        try {
            $r = Http::asJson()->timeout(15)->post(rtrim((string) config('services.cinetpay.base_url'), '/').'/v2/payment/check', [
                'apikey' => self::cinetpayApiKey(),
                'site_id' => self::cinetpaySiteId(),
                'transaction_id' => $p->reference,
            ]);
        } catch (Throwable $e) {
            Log::warning('CinetPay check a échoué', ['reference' => $p->reference, 'error' => $e->getMessage()]);

            return $p->status;
        }
        $p->update(['verifie_at' => now()]);
        $statut = $r->json('data.status');
        if ($statut === 'ACCEPTED') {
            self::activer($p, $r->json('data.payment_method'), $r->json('data.operator_id'));
        } elseif (in_array($statut, ['REFUSED', 'CANCELLED'], true)) {
            $p->update(['status' => 'failed']);
        }

        return $p->fresh()->status;
    }

    private const MOYENS = [
        'OM' => 'Orange Money', 'ORANGE' => 'Orange Money', 'MOMO' => 'MTN Mobile Money', 'MTN' => 'MTN Mobile Money',
        'FLOOZ' => 'Moov Money', 'MOOV' => 'Moov Money', 'WAVE' => 'Wave', 'CARD' => 'Carte bancaire', 'VISA' => 'Carte bancaire',
        'MASTERCARD' => 'Carte bancaire',
    ];

    public static function libelleMoyen(?string $code): ?string
    {
        if (! $code) {
            return null;
        }
        foreach (self::MOYENS as $k => $v) {
            if (str_contains(strtoupper($code), $k)) {
                return $v;
            }
        }

        return $code;
    }

    /**
     * Paiement confirmé : période d'un an (la date anniversaire est conservée
     * si l'abonnement court encore ou est dans son délai de grâce), reçu
     * numéroté, notifications et emails au payeur et au bénéficiaire.
     */
    public static function activer(Payment $p, ?string $moyen = null, ?string $operation = null): void
    {
        if ($p->status === 'success') {
            return;
        }
        $beneficiaire = $p->beneficiaire ?? $p->user;
        if (! $beneficiaire) {
            return;
        }
        $enCours = $beneficiaire->subscription_expires_at
            && $beneficiaire->subscription_expires_at->copy()->addDays(self::GRACE_JOURS)->isFuture();
        $debut = $enCours ? $beneficiaire->subscription_expires_at->copy() : now();
        $fin = $debut->copy()->addYear();

        $p->update([
            'status' => 'success', 'paye_at' => now(), 'moyen' => self::libelleMoyen($moyen),
            'transaction_id' => $operation ?: $p->transaction_id,
            'periode_debut' => $debut->toDateString(), 'periode_fin' => $fin->toDateString(),
            'recu_numero' => 'REJCC-REC-'.now()->format('Y').'-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT),
        ]);
        $beneficiaire->forceFill(['subscription_expires_at' => $fin, 'abonnement_rappel' => null])->save();

        $offert = $p->beneficiaire_id && $p->beneficiaire_id !== $p->user_id;
        $finTxt = $fin->locale('fr')->isoFormat('D MMMM YYYY');
        MemberNotification::create([
            'user_id' => $beneficiaire->id, 'type' => 'success',
            'title' => $offert ? 'Un abonnement vous a été offert !' : 'Paiement confirmé : votre abonnement est actif',
            'body' => ($offert ? trim($p->user?->prenom.' '.$p->user?->nom).' vous offre votre abonnement annuel au REJCC. ' : '')
                ."Il est valable jusqu'au {$finTxt}. Votre reçu est disponible dans « Mon abonnement ».",
            'link' => '/espace-membre/abonnement',
        ]);
        Mailer::send($beneficiaire->email, new AbonnementConfirme($p->fresh(), $beneficiaire, $offert ? 'beneficiaire' : 'membre'));
        if ($offert && $p->user) {
            MemberNotification::create([
                'user_id' => $p->user_id, 'type' => 'success',
                'title' => 'Merci pour votre générosité !',
                'body' => 'Votre paiement est confirmé : '.trim($beneficiaire->prenom.' '.$beneficiaire->nom)." est abonné(e) jusqu'au {$finTxt}. Votre reçu est disponible dans « Mon abonnement ».",
                'link' => '/espace-membre/abonnement',
            ]);
            Mailer::send($p->user->email, new AbonnementConfirme($p->fresh(), $beneficiaire, 'payeur'));
        }
        dispatch(function () use ($p) {
            try {
                self::recu($p->fresh());
            } catch (Throwable $e) {
                Log::warning("Reçu {$p->recu_numero} non produit : ".$e->getMessage());
            }
        })->afterResponse();
    }

    /** Chemin du reçu PDF (produit une fois, conservé). */
    public static function recu(Payment $p): string
    {
        if ($p->recu_fichier && Storage::disk('local')->exists($p->recu_fichier)) {
            return $p->recu_fichier;
        }
        $chemin = 'recus/'.substr((string) $p->recu_numero, 10, 4).'/'.$p->recu_numero.'.pdf';
        Storage::disk('local')->put($chemin, RecuPdf::generer($p));
        $p->update(['recu_fichier' => $chemin]);

        return $chemin;
    }

    // ── Tâches planifiées ───────────────────────────────────────────────

    /**
     * Paiements en attente : revérifiés auprès de CinetPay (le membre a pu
     * fermer la page avant le retour), abandonnés au-delà de 48 h.
     */
    public static function verifierEnAttente(): array
    {
        $n = ['confirmes' => 0, 'abandonnes' => 0];
        foreach (Payment::where('type', 'abonnement')->where('status', 'pending')->where('created_at', '>=', now()->subHours(48))
            ->where(fn ($q) => $q->whereNull('verifie_at')->orWhere('verifie_at', '<', now()->subMinutes(20)))->get() as $p) {
            $n['confirmes'] += self::verifier($p) === 'success' ? 1 : 0;
        }
        $n['abandonnes'] = Payment::where('type', 'abonnement')->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(48))->update(['status' => 'abandonne']);

        return $n;
    }

    /** Étapes de rappel : jours restants avant (ou après) l'échéance. */
    private const RAPPELS = [
        'j30' => [30, 'Votre abonnement arrive à échéance dans un mois', 'Il expire le :date. Vous pouvez le renouveler dès maintenant : la date anniversaire est conservée.'],
        'j7' => [7, 'Votre abonnement expire dans 7 jours', "Il expire le :date. Renouvelez-le pour garder l'accès à la carte membre, à l'annuaire, à la messagerie et à la Marketplace."],
        'j0' => [0, 'Votre abonnement est arrivé à échéance', "Il a expiré le :date. Vous gardez l'accès jusqu'au :grace : renouvelez-le d'ici là pour ne rien perdre."],
        'fin' => [-self::GRACE_JOURS, 'Votre accès premium est suspendu', "Le délai de grâce est terminé. Renouvelez votre abonnement pour retrouver la carte membre, l'annuaire, la messagerie et la Marketplace."],
    ];

    public static function rappels(): int
    {
        if (! SubscriptionMode::enforced()) {
            return 0;
        }
        $n = 0;
        $membres = User::whereNotIn('role', ['admin', 'mentor'])->where('is_active', true)->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now()->subDays(self::GRACE_JOURS + 2), now()->addDays(31)])->get();
        foreach ($membres as $u) {
            $jours = (int) floor(now()->startOfDay()->diffInDays($u->subscription_expires_at->copy()->startOfDay(), false));
            // Étape la plus avancée atteinte (un seul rappel par étape et par échéance).
            $etape = collect(self::RAPPELS)->filter(fn ($r) => $jours <= $r[0])->keys()->last();
            $cle = $u->subscription_expires_at->toDateString().':'.$etape;
            if (! $etape || $u->abonnement_rappel === $cle) {
                continue;
            }
            [, $titre, $texte] = self::RAPPELS[$etape];
            $texte = strtr($texte, [
                ':date' => $u->subscription_expires_at->locale('fr')->isoFormat('D MMMM YYYY'),
                ':grace' => $u->subscription_expires_at->copy()->addDays(self::GRACE_JOURS)->locale('fr')->isoFormat('D MMMM YYYY'),
            ]);
            MemberNotification::create(['user_id' => $u->id, 'type' => $etape === 'fin' ? 'warning' : 'info', 'title' => $titre, 'body' => $texte, 'link' => '/espace-membre/abonnement']);
            Mailer::send($u->email, new AbonnementRappel($u, $titre, $texte));
            $u->forceFill(['abonnement_rappel' => $cle])->save();
            $n++;
        }

        return $n;
    }
}
