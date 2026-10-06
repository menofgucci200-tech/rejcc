<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Suppression définitive d'un compte clôturé (30 jours après la demande) :
 * les données personnelles sont effacées et le compte anonymisé. Restent,
 * pour les obligations légales, les paiements et le registre des
 * certificats ; les messages déjà échangés apparaissent sous « Membre
 * supprimé ».
 */
class Cloture
{
    public static function anonymiserEcheances(): int
    {
        $n = 0;
        // Un compte réactivé entre-temps (par le membre ou l'équipe) n'est pas supprimé.
        foreach (User::whereNotNull('suppression_prevue_at')->where('suppression_prevue_at', '<=', now())->where('is_active', false)->whereNull('anonymise_at')->get() as $u) {
            self::anonymiser($u);
            $n++;
        }

        return $n;
    }

    public static function anonymiser(User $u): void
    {
        DB::transaction(function () use ($u) {
            $id = $u->id;
            $email = $u->email;
            $suppr = fn (string $t, string $c = 'user_id') => Schema::hasTable($t) ? DB::table($t)->where($c, $id)->delete() : 0;
            $maj = fn (string $t, string $c, array $v) => Schema::hasTable($t) ? DB::table($t)->where($c, $id)->update($v) : 0;

            foreach (['api_tokens', 'push_subscriptions', 'member_notifications', 'job_alerts', 'project_follows', 'group_user', 'personal_documents', 'account_events'] as $t) {
                $suppr($t);
            }
            $suppr('member_reviews', 'reviewer_id');
            $suppr('member_reviews', 'reviewed_id');
            Schema::hasTable('newsletter_subscribers') && DB::table('newsletter_subscribers')->where('email', $email)->delete();
            $maj('marketplace_listings', 'user_id', ['statut' => 'expiree']);
            $maj('opportunities', 'author_id', ['statut' => 'cloturee']);
            $maj('projects', 'user_id', ['statut' => 'retire']);
            Schema::hasTable('mentorships') && DB::table('mentorships')->where(fn ($q) => $q->where('mentor_id', $id)->orWhere('mentore_id', $id))
                ->whereIn('statut', ['en_attente', 'accepte'])->update(['statut' => 'annule']);
            $maj('event_registrations', 'user_id', ['email' => null]);

            $vides = array_fill_keys(array_values(array_intersect(Schema::getColumnListing('users'), [
                'telephone', 'genre', 'ville', 'date_naissance', 'paroisse', 'secteur', 'profil', 'organisation', 'titre', 'diocese',
                'bio', 'competences', 'parcours', 'liens', 'preferences', 'photo', 'piece_identite', 'mentor_expertises', 'mentor_bio',
                'mentor_disponibilites', 'email_nouveau', 'email_jeton', 'email_jeton_at', 'suppression_motif',
            ])), null);
            $u->forceFill($vides + [
                'prenom' => 'Membre', 'nom' => 'supprimé', 'name' => 'Membre supprimé',
                'email' => 'supprime-'.$id.'@rejcc.invalid', 'password' => Str::random(48),
                'is_active' => false, 'anonymise_at' => now(),
            ])->save();
        });
    }
}
