<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Abonnement;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbonnementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        SubscriptionMode::set(true);
        SiteSetting::updateOrCreate(['key' => 'payment.cinetpay_api_key'], ['value' => 'cle']);
        SiteSetting::updateOrCreate(['key' => 'payment.cinetpay_site_id'], ['value' => 'site']);
    }

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function cinetpay(string $statut = 'ACCEPTED'): void
    {
        Http::fake([
            '*/v2/payment/check' => Http::response(['code' => '00', 'data' => ['status' => $statut, 'payment_method' => 'OMCIV2', 'operator_id' => 'OP123']]),
            '*/v2/payment' => Http::response(['code' => '201', 'data' => ['payment_url' => 'https://checkout.cinetpay.com/p/x']]),
        ]);
    }

    public function test_tarif_reglable_paiement_recu_et_date_anniversaire(): void
    {
        $this->cinetpay();
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($admin)->putJson('/api/admin/abonnements/tarif', ['montant' => 100])->assertStatus(422);
        $this->withToken($admin)->putJson('/api/admin/abonnements/tarif', ['montant' => 15000])->assertOk();

        // Échéance passée depuis 3 jours : délai de grâce, accès maintenu.
        $u = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré', 'subscription_expires_at' => now()->subDays(3)]);
        $this->assertTrue($u->hasPaidSubscription());
        $this->assertTrue($u->abonnementEnGrace());
        $t = $this->tokenFor($u);
        $s = $this->withToken($t)->getJson('/api/subscription/status')->assertOk();
        $this->assertSame([true, true, 15000], [$s->json('active'), $s->json('grace'), $s->json('amount')]);

        $ref = $this->withToken($t)->postJson('/api/subscription/pay')->assertOk()->json('reference');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v2/payment') && $r['amount'] === 15000);
        $this->withToken($t)->getJson('/api/subscription/status?ref='.$ref)->assertOk()->assertJsonPath('grace', false);

        $p = Payment::where('reference', $ref)->first();
        $this->assertSame(['success', 'Orange Money', 'OP123'], [$p->status, $p->moyen, $p->transaction_id]);
        $this->assertStringStartsWith('REJCC-REC-', $p->recu_numero);
        // Renouvellement pendant la grâce : la date anniversaire est conservée.
        $this->assertSame(now()->subDays(3)->addYear()->toDateString(), $u->fresh()->subscription_expires_at->toDateString());
        $pdf = $this->withToken($t)->get('/api/subscription/recus/'.$ref)->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->withToken($this->tokenFor(User::factory()->create()))->get('/api/subscription/recus/'.$ref)->assertNotFound();
        // Pas de nouveau paiement avant les 30 derniers jours.
        $this->withToken($t)->postJson('/api/subscription/pay')->assertStatus(422);
    }

    public function test_offrir_un_abonnement_a_un_membre(): void
    {
        $this->cinetpay();
        $parrain = User::factory()->abonne()->create(['prenom' => 'Paul', 'nom' => 'Kouassi']);
        $jeune = User::factory()->create(['prenom' => 'Esther', 'nom' => 'Kouamé', 'created_at' => '2026-04-10']);
        $t = $this->tokenFor($parrain);

        // Recherche du bénéficiaire : par nom, ou par numéro de membre complet.
        $this->assertSame(['Esther Kouamé'], array_column($this->withToken($t)->getJson('/api/subscription/beneficiaires?q=kouam')->json('membres'), 'nom'));
        $jeune->update(['preferences' => ['apparaitre_annuaire' => false]]);
        $this->assertSame([], $this->withToken($t)->getJson('/api/subscription/beneficiaires?q=kouam')->json('membres'));
        $this->assertCount(1, $this->withToken($t)->getJson('/api/subscription/beneficiaires?q='.$jeune->memberNumber())->json('membres'));
        $this->assertSame([], $this->withToken($t)->getJson('/api/subscription/beneficiaires?q=REJCC-2020-0101-'.$jeune->cardCode())->json('membres'));

        $this->withToken($t)->postJson('/api/subscription/pay', ['beneficiaire_id' => User::factory()->create(['role' => 'mentor'])->id])->assertStatus(422);
        $ref = $this->withToken($t)->postJson('/api/subscription/pay', ['beneficiaire_id' => $jeune->id])->assertOk()->json('reference');
        $this->withToken($t)->getJson('/api/subscription/status?ref='.$ref)->assertOk();

        $this->assertTrue($jeune->fresh()->hasPaidSubscription());
        $this->assertSame(now()->addYear()->toDateString(), $jeune->fresh()->subscription_expires_at->toDateString());
        $this->assertTrue(MemberNotification::where('user_id', $jeune->id)->where('title', 'Un abonnement vous a été offert !')->exists());
        $this->assertTrue(MemberNotification::where('user_id', $parrain->id)->where('title', 'Merci pour votre générosité !')->exists());
        $this->assertSame('Esther Kouamé', $this->withToken($t)->getJson('/api/subscription/status')->json('history.0.pour'));
        $tj = $this->tokenFor($jeune);
        $this->assertSame('Paul Kouassi', $this->withToken($tj)->getJson('/api/subscription/status')->json('history.0.par'));
        $this->withToken($tj)->get('/api/subscription/recus/'.$ref)->assertOk();
        // Déjà abonnée pour un an : impossible de lui offrir un second abonnement maintenant.
        $this->withToken($t)->postJson('/api/subscription/pay', ['beneficiaire_id' => $jeune->id])->assertStatus(422);
    }

    public function test_paiements_en_attente_rappels_et_administration(): void
    {
        $this->cinetpay();
        $u = User::factory()->create();
        $recent = Payment::create(['user_id' => $u->id, 'type' => 'abonnement', 'reference' => 'ABO-1', 'provider' => 'cinetpay', 'amount' => 10000, 'currency' => 'XOF', 'status' => 'pending']);
        $vieux = Payment::create(['user_id' => $u->id, 'type' => 'abonnement', 'reference' => 'ABO-2', 'provider' => 'cinetpay', 'amount' => 10000, 'currency' => 'XOF', 'status' => 'pending']);
        $vieux->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->assertSame(['confirmes' => 1, 'abandonnes' => 1], Abonnement::verifierEnAttente());
        $this->assertSame(['success', 'abandonne'], [$recent->fresh()->status, $vieux->fresh()->status]);

        // Rappels : une seule fois par étape et par échéance.
        $m = User::factory()->create(['subscription_expires_at' => now()->addDays(6)]);
        $this->assertSame(1, Abonnement::rappels());
        $this->assertSame(0, Abonnement::rappels());
        $this->assertTrue(MemberNotification::where('user_id', $m->id)->where('title', 'Votre abonnement expire dans 7 jours')->exists());
        $this->travel(12)->days();
        Abonnement::rappels();
        $this->assertTrue(MemberNotification::where('user_id', $m->id)->where('title', 'Votre accès premium est suspendu')->exists());
        $this->assertFalse($m->fresh()->hasPaidSubscription());
        $this->travelBack();

        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $r = $this->withToken($admin)->getJson('/api/admin/abonnements')->assertOk();
        $this->assertSame(2, $r->json('stats.actifs'));
        $this->assertSame(10000, $r->json('stats.recettes_annee'));
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/abonnements?statut=echeance')->json('membres'));
        $this->assertCount(2, $this->withToken($admin)->getJson('/api/admin/abonnements/paiements')->json('paiements'));
        $this->withToken($admin)->postJson("/api/admin/abonnements/{$m->id}/relancer")->assertOk();
        $this->assertSame(3, MemberNotification::where('user_id', $m->id)->count());
        $this->withToken($this->tokenFor(User::factory()->create()))->getJson('/api/admin/abonnements')->assertStatus(403);
    }
}
