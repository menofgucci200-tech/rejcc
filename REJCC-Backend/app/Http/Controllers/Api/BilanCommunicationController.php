<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationPublication;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\SalonMessage;
use App\Models\User;
use App\Support\CalendrierEditorial as Cal;
use App\Support\CathoImpact;
use App\Support\DemandesCommunication;
use App\Support\Departements;
use App\Support\PlanDiffusion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Bilan mensuel du département Communication (rubrique « communication ») :
 * régularité des publications (qui publie quand c'est son tour), CathoImpact
 * et chiffres du réseau. Les visites du site et les clics des liens de suivi
 * sont ajoutés par le dashboard (fichiers de mesure partagés). Pas d'abonnés
 * newsletter ni d'abonnés par réseau (demande de la Com).
 */
class BilanCommunicationController extends Controller
{
    /** GET /admin/communication/bilan?mois=AAAA-MM */
    public function show(Request $request)
    {
        $mois = (string) $request->query('mois', now()->format('Y-m'));
        if (! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mois, $m)) {
            return response()->json(['ok' => false, 'message' => 'Mois invalide (AAAA-MM attendu).'], 422);
        }
        $debut = Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth();
        $periode = [$debut, $fin];

        return response()->json(['ok' => true,
            'mois' => $mois,
            'libelle' => ucfirst($debut->copy()->locale('fr')->isoFormat('MMMM YYYY')),
            'du' => $debut->toDateString(),
            'au' => $fin->toDateString(),
            'publications' => self::publications($debut, $fin),
            'cathoimpact' => self::cathoimpact($request->user(), $periode),
            'reseau' => self::reseau($periode),
            'demandes' => self::demandes($debut, $fin, $request->user()),
        ]);
    }

    /**
     * GET /admin/communication/cockpit : accueil de l'espace Communication.
     * Chiffres du mois en cours (version légère du bilan : les chiffres du
     * réseau sont gardés 60 s en cache) et « À traiter » : demandes nouvelles
     * ou urgentes, réponses CathoImpact à examiner. Les publications du jour,
     * le tour en cours et les retards viennent de /calendrier/aujourdhui ;
     * les visites du site sont ajoutées par le dashboard.
     */
    public function cockpit(Request $request)
    {
        $me = $request->user();
        $debut = now()->startOfMonth();
        $fin = now()->endOfMonth();
        $periode = [$debut, $fin];

        $reseau = Cache::remember('communication.cockpit.reseau.'.$debut->format('Y-m'), 60, fn () => self::reseau($periode));
        $publications = CommunicationPublication::whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->where('statut', '!=', 'annule')->selectRaw("count(*) as prevues, sum(case when statut = 'publie' then 1 else 0 end) as publiees")->first();

        // Demandes à traiter (comptes de test : celles des testeurs seulement).
        $ouvertes = DemandesCommunication::visibles($me)->whereIn('statut', DemandesCommunication::STATUTS_OUVERTS)
            ->orderBy('date_souhaitee')->orderBy('id')->get();
        $aTraiter = $ouvertes->filter(fn ($d) => $d->statut === 'nouvelle' || DemandesCommunication::estUrgente($d));

        $reponses = CathoImpact::visibles($me)->where('statut', 'recu')->oldest()->get(['id', 'nom_complet', 'profil', 'entreprise', 'fonction', 'created_at']);

        return response()->json(['ok' => true,
            'mois' => $debut->format('Y-m'),
            'libelle' => ucfirst($debut->copy()->locale('fr')->isoFormat('MMMM YYYY')),
            'chiffres' => [
                'nouveaux_membres' => $reseau['nouveaux_membres'],
                'adhesions_recues' => $reseau['adhesions_recues'],
                'messages_communaute' => $reseau['messages_communaute'],
                'membres_actifs_communaute' => $reseau['membres_actifs_communaute'],
                'inscriptions_evenements' => $reseau['inscriptions_evenements'],
                'cathoimpact' => self::cathoimpact($me, $periode),
                'demandes_en_attente' => $ouvertes->count(),
                'demandes_nouvelles' => $ouvertes->where('statut', 'nouvelle')->count(),
                'publications_prevues' => (int) ($publications->prevues ?? 0),
                'publications_publiees' => (int) ($publications->publiees ?? 0),
            ],
            'a_traiter' => [
                'demandes' => $aTraiter->take(6)->map(fn ($d) => [
                    'id' => $d->id,
                    'titre' => $d->titre,
                    'departement_label' => $d->libelleDepartement(),
                    'type_label' => $d->libelleType(),
                    'statut' => $d->statut,
                    'statut_label' => $d->libelleStatut(),
                    'date_souhaitee' => $d->date_souhaitee?->toDateString(),
                    'jours_avant' => PlanDiffusion::joursAvant($d->date_souhaitee),
                    'niveau_delai' => PlanDiffusion::niveauDelai($d->date_souhaitee),
                    'urgente' => DemandesCommunication::estUrgente($d),
                ])->values(),
                'demandes_total' => $aTraiter->count(),
                'cathoimpact' => $reponses->take(5)->map(fn ($c) => [
                    'id' => $c->id,
                    'nom_complet' => $c->nom_complet,
                    'profil_label' => CathoImpact::PROFILS[$c->profil]['label'] ?? $c->profil,
                    'entreprise' => $c->entreprise,
                    'fonction' => $c->fonction,
                    'created_at' => $c->created_at?->toIso8601String(),
                ])->values(),
                'cathoimpact_total' => $reponses->count(),
            ],
        ]);
    }

    /** CathoImpact du mois (comptes de test : leurs propres réponses seulement). */
    private static function cathoimpact(?User $u, array $periode): array
    {
        return [
            'recues' => CathoImpact::visibles($u)->whereBetween('created_at', $periode)->count(),
            'publiees' => CathoImpact::visibles($u)->where('statut', 'publie')->whereBetween('statut_at', $periode)->count(),
        ];
    }

    /** Chiffres du réseau sur la période (comptes de la campagne de test exclus des nouveaux membres). */
    private static function reseau(array $periode): array
    {
        return [
            'nouveaux_membres' => User::where('role', 'member')->whereNull('departement')->whereBetween('created_at', $periode)->count(),
            'adhesions_recues' => MembershipApplication::whereBetween('created_at', $periode)->count() + Member::whereBetween('created_at', $periode)->count(),
            'adhesions_acceptees' => MembershipApplication::where('statut', 'accepte')->whereBetween('updated_at', $periode)->count(),
            'messages_communaute' => SalonMessage::whereNull('supprime_at')->whereBetween('created_at', $periode)->count(),
            'membres_actifs_communaute' => SalonMessage::whereNull('supprime_at')->whereBetween('created_at', $periode)->distinct()->count('user_id'),
            'evenements' => Event::where('statut', 'publie')->whereBetween('starts_at', $periode)->count(),
            'inscriptions_evenements' => EventRegistration::whereBetween('created_at', $periode)->count(),
        ];
    }

    /** Publications du mois : totaux, par responsable (tour compris), par canal et par type. */
    public static function publications(Carbon $debut, Carbon $fin): array
    {
        $tours = Cal::toursEntre($debut->toDateString(), $fin->toDateString());
        $pubs = CommunicationPublication::with('responsableCompte:id,prenom,nom,email')
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])->get();

        $compter = function ($lot) {
            $actives = $lot->where('statut', '!=', 'annule');
            $publiees = $actives->where('statut', 'publie');
            // À temps : publiée le jour prévu ou avant.
            $aTemps = $publiees->filter(fn ($p) => ! $p->publie_at || $p->publie_at->toDateString() <= $p->date->toDateString());
            $nonPubliees = $actives->filter(fn ($p) => $p->enRetard());
            $echues = $publiees->count() + $nonPubliees->count();

            return [
                'prevues' => $actives->count(),
                'publiees' => $publiees->count(),
                'a_temps' => $aTemps->count(),
                'non_publiees' => $nonPubliees->count(),
                'a_venir' => $actives->count() - $echues,
                'annulees' => $lot->count() - $actives->count(),
                // Taux de régularité : publiées à temps parmi les publications échues.
                'taux_regularite' => $echues > 0 ? (int) round($aTemps->count() / $echues * 100) : null,
            ];
        };

        $parResponsable = $pubs->groupBy(fn ($p) => Cal::responsable($p, $tours)['nom'] ?? 'Sans responsable')
            ->map(fn ($lot, $nom) => ['nom' => $nom] + $compter($lot))
            ->sortByDesc('prevues')->values();

        $parCanal = collect(Cal::CANAUX)->map(function ($label, $cle) use ($pubs, $compter) {
            $lot = $pubs->filter(fn ($p) => in_array($cle, $p->canaux ?? [], true));

            return ['canal' => $cle, 'label' => $label] + $compter($lot);
        })->filter(fn ($l) => $l['prevues'] + $l['annulees'] > 0)->values();

        $parType = $pubs->groupBy('type')->map(fn ($lot, $type) => ['type' => $type, 'label' => Cal::TYPES[$type]['label'] ?? $type] + $compter($lot))
            ->sortByDesc('prevues')->values();

        return $compter($pubs) + ['par_responsable' => $parResponsable, 'par_canal' => $parCanal, 'par_type' => $parType];
    }

    /**
     * Demandes de communication reçues des autres départements (table
     * demandes_communication, construite en parallèle) : null tant qu'elle
     * n'existe pas, et le bilan affiche alors « bientôt disponible ».
     */
    private static function demandes(Carbon $debut, Carbon $fin, ?User $u = null): ?array
    {
        if (! Schema::hasTable('demandes_communication')) {
            return null;
        }
        $statuts = DemandesCommunication::STATUTS;
        $libelles = collect(Departements::LISTE)->map(fn ($d) => $d['label']);
        // Comptes de test : les demandes des testeurs seulement.
        $base = fn () => DemandesCommunication::visibles($u)->toBase();
        $recues = $base()->whereBetween('created_at', [$debut, $fin]);

        return [
            'recues' => (clone $recues)->count(),
            'publiees' => $base()->where('statut', 'publiee')->whereBetween('statut_at', [$debut, $fin])->count(),
            'refusees' => $base()->where('statut', 'refusee')->whereBetween('statut_at', [$debut, $fin])->count(),
            'en_attente' => $base()->whereIn('statut', DemandesCommunication::STATUTS_OUVERTS)->count(),
            'par_statut' => (clone $recues)->selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut')
                ->map(fn ($n, $s) => ['label' => $statuts[$s] ?? $s, 'nombre' => (int) $n])->values(),
            'par_departement' => (clone $recues)->selectRaw('departement, count(*) as nombre')->groupBy('departement')->orderByDesc('nombre')->get()
                ->map(fn ($l) => ['label' => $l->departement ? ($libelles[$l->departement] ?? $l->departement) : 'Administration', 'nombre' => (int) $l->nombre])->values(),
        ];
    }
}
