<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MemberNotification;
use App\Models\Group;
use App\Models\MemberReview;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Marketplace des membres : chacun peut proposer un service ou un produit ;
 * l'annonce n'apparaît qu'après validation par l'administration.
 */
class MarketplaceController extends Controller
{
    /** Catégories : les 16 groupes sectoriels (une seule classification dans le réseau). */
    private function categories()
    {
        return Group::orderBy('ordre')->orderBy('id')->get(['id', 'name', 'icone', 'couleur'])
            ->map(fn (Group $g) => ['id' => $g->id, 'nom' => $g->name, 'icone' => $g->icone ?: 'network', 'couleur' => $g->couleur ?: '#031D59'])->values();
    }

    /**
     * GET /marketplace?q=&type=&groupe=&ville=&tri=&favoris=1&page= — catalogue
     * des annonces en ligne, recherche en langage courant (« traiteur Cocody »),
     * filtres, tri (récentes, prix croissant/décroissant) et pagination.
     */
    public function index(Request $request)
    {
        $moi = $request->user();
        $query = MarketplaceListing::enLigne()
            ->select('marketplace_listings.*')
            ->join('users', 'users.id', '=', 'marketplace_listings.user_id')
            ->with(['user:id,prenom,nom,ville,photo,role', 'group:id,name,icone,couleur']);

        RechercheMots::appliquer($query, (string) $request->query('q', ''), [
            'marketplace_listings.title', 'marketplace_listings.description', 'marketplace_listings.category',
            'users.prenom', 'users.nom', 'users.ville',
        ]);
        if (in_array($request->query('type'), ['service', 'produit'], true)) {
            $query->where('marketplace_listings.type', $request->query('type'));
        }
        if ($groupe = (int) $request->query('groupe')) {
            $query->where('marketplace_listings.group_id', $groupe);
        }
        if ($ville = trim((string) $request->query('ville', ''))) {
            $query->where('users.ville', $ville);
        }
        if ($request->boolean('favoris')) {
            $query->whereIn('marketplace_listings.id', DB::table('marketplace_favoris')->where('user_id', $moi->id)->select('listing_id'));
        }

        match ($request->query('tri')) {
            'prix_asc' => $query->orderByRaw('marketplace_listings.prix_valeur is null')->orderBy('marketplace_listings.prix_valeur'),
            'prix_desc' => $query->orderByRaw('marketplace_listings.prix_valeur is null')->orderByDesc('marketplace_listings.prix_valeur'),
            default => null,
        };
        $query->orderByDesc('marketplace_listings.publie_le')->orderByDesc('marketplace_listings.id');

        $page = $query->paginate(12);
        $favoris = DB::table('marketplace_favoris')->where('user_id', $moi->id)->pluck('listing_id')->flip();

        return response()->json([
            'ok' => true,
            'listings' => collect($page->items())->map(fn ($l) => $this->payload($l) + ['favori' => isset($favoris[$l->id])])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'total_catalogue' => MarketplaceListing::enLigne()->count(),
            'categories' => $this->categories(),
            'villes' => MarketplaceListing::enLigne()->join('users', 'users.id', '=', 'marketplace_listings.user_id')
                ->whereNotNull('users.ville')->distinct()->orderBy('users.ville')->pluck('users.ville')->values(),
            'nb_favoris' => $favoris->count(),
        ]);
    }

    /** POST /marketplace/{id}/favori — ajouter ou retirer des favoris. */
    public function favori(Request $request, int $id)
    {
        $moi = $request->user()->id;
        if (! MarketplaceListing::find($id)) {
            return response()->json(['ok' => false, 'message' => "Cette annonce n'est plus disponible."], 404);
        }
        $existe = DB::table('marketplace_favoris')->where('user_id', $moi)->where('listing_id', $id);
        if ($existe->exists()) {
            $existe->delete();
            $favori = false;
        } else {
            DB::table('marketplace_favoris')->insert(['user_id' => $moi, 'listing_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            $favori = true;
        }

        return response()->json(['ok' => true, 'favori' => $favori]);
    }

    /**
     * GET /marketplace/{id} — fiche complète d'une annonce en ligne (ou de
     * sa propre annonce) : vendeur, note des membres, groupes. Le téléphone
     * saisi dans l'annonce n'est donné qu'aux membres abonnés. Compte une
     * vue par membre et par jour (hors vendeur).
     */
    public function show(Request $request, int $id)
    {
        $moi = $request->user();
        $l = MarketplaceListing::with(['user', 'group'])->find($id);
        $enLigne = $l && MarketplaceListing::enLigne()->whereKey($l->id)->exists();
        if (! $l || (! $enLigne && $l->user_id !== $moi->id)) {
            return response()->json(['ok' => false, 'message' => "Cette annonce n'est plus disponible."], 404);
        }

        $estVendeur = $l->user_id === $moi->id;
        if (! $estVendeur && Cache::add("marketplace:vue:{$l->id}:{$moi->id}", true, now()->endOfDay())) {
            $l->increment('vues');
        }

        $u = $l->user;
        $data = $this->payload($l, withStatus: $estVendeur);
        $data['contact'] = $moi->hasActiveSubscription() || $estVendeur ? $l->contact : null;
        $data['seller'] += [
            'role_label' => $u->roleLabel(),
            'titre' => $u->titre,
            'avis' => MemberReview::resume($u->id),
            'groupes' => $u->groups()->orderBy('ordre')->get(['groups.id', 'groups.name'])
                ->map(fn ($g) => ['id' => $g->id, 'nom' => $g->name])->values(),
            'annonces' => MarketplaceListing::where('user_id', $u->id)->where('statut', 'approuve')->count(),
        ];
        $data['est_vendeur'] = $estVendeur;
        $data['favori'] = DB::table('marketplace_favoris')->where('user_id', $moi->id)->where('listing_id', $l->id)->exists();
        $data['deja_signalee'] = DB::table('listing_reports')->where('listing_id', $l->id)
            ->where('reporter_id', $moi->id)->where('statut', 'nouveau')->exists();
        if ($estVendeur) {
            $data['vues'] = $l->vues;
            $data['contacts'] = $l->contacts;
        }

        return response()->json(['ok' => true, 'listing' => $data]);
    }

    /** POST /marketplace/{id}/signaler — signaler une annonce à l'administration. */
    public function signaler(Request $request, int $id)
    {
        $moi = $request->user();
        $l = MarketplaceListing::enLigne()->find($id);
        if (! $l) {
            return response()->json(['ok' => false, 'message' => "Cette annonce n'est plus disponible."], 404);
        }
        if ($l->user_id === $moi->id) {
            return response()->json(['ok' => false, 'message' => 'Vous ne pouvez pas signaler votre propre annonce.'], 422);
        }

        DB::table('listing_reports')->updateOrInsert(
            ['listing_id' => $l->id, 'reporter_id' => $moi->id, 'statut' => 'nouveau'],
            ['motif' => mb_substr(trim((string) $request->input('motif')), 0, 500) ?: null, 'created_at' => now(), 'updated_at' => now()],
        );

        return response()->json(['ok' => true, 'message' => "Merci, l'annonce a été signalée à l'administration."]);
    }

    /** GET /marketplace/mine — les annonces du membre connecté, tous statuts. */
    public function mine(Request $request)
    {
        // Échéances de ses annonces traitées à l'ouverture (en plus de la tâche quotidienne).
        MarketplaceListing::traiterEcheances($request->user()->id);

        $listings = MarketplaceListing::where('user_id', $request->user()->id)
            ->with(['group:id,name,icone,couleur', 'user'])
            ->latest()
            ->get()
            ->map(fn ($l) => $this->payload($l, withStatus: true) + [
                'vues' => $l->vues,
                'contacts' => $l->contacts,
                'suspendue' => $l->suspendue(),
                'publie_le' => $l->publie_le?->toIso8601String(),
                'expire_le' => $l->expire_le?->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'listings' => $listings, 'abonne' => $request->user()->hasActiveSubscription()]);
    }

    /** POST /marketplace — soumettre une annonce (statut en attente). */
    public function store(Request $request)
    {
        $validator = $this->validateur($request);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Garde-fou anti-spam : 5 annonces en attente maximum par membre.
        $pending = MarketplaceListing::where('user_id', $request->user()->id)
            ->where('statut', 'en_attente')->count();
        if ($pending >= 5) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà 5 annonces en attente de validation. Patientez avant d\'en soumettre de nouvelles.'], 422);
        }

        $d = $validator->validated();
        $listing = MarketplaceListing::create([
            'user_id' => $request->user()->id,
            ...$d,
            'category' => Group::find($d['group_id'])->name,
            'statut' => 'en_attente',
        ]);

        return response()->json(['ok' => true, 'listing' => $this->payload($listing, withStatus: true)]);
    }

    private function validateur(Request $request)
    {
        return Validator::make($request->all(), [
            'type' => 'required|in:service,produit',
            'title' => 'required|string|min:3|max:120',
            'group_id' => 'required|integer|exists:groups,id',
            'description' => 'required|string|min:20|max:2000',
            'price' => 'nullable|string|max:80',
            'contact' => 'nullable|string|max:60',
            'photo' => 'nullable|url|max:500',
        ], [
            'type.required' => 'Choisissez « Service » ou « Produit ».',
            'title.required' => 'Donnez un titre à votre annonce.',
            'title.min' => 'Le titre doit faire au moins 3 caractères.',
            'group_id.required' => 'Choisissez une catégorie.',
            'group_id.exists' => 'Choisissez une catégorie de la liste.',
            'description.required' => 'Décrivez votre offre.',
            'description.min' => 'Décrivez votre offre en quelques phrases (20 caractères minimum).',
            'photo.url' => 'Le visuel doit être une image, une vidéo ou un lien valide.',
        ]);
    }

    /**
     * PUT /marketplace/{id} — modifier sa propre annonce. Prix et téléphone :
     * immédiat. Titre, description, visuel, type ou catégorie d'une annonce
     * en ligne : elle repasse en validation. Une annonce refusée corrigée
     * est resoumise.
     */
    public function update(Request $request, int $id)
    {
        $l = MarketplaceListing::where('user_id', $request->user()->id)->find($id);
        if (! $l) {
            return response()->json(['ok' => false, 'message' => 'Annonce introuvable.'], 404);
        }
        if ($l->statut === 'expiree') {
            return response()->json(['ok' => false, 'message' => "Renouvelez d'abord cette annonce pour la modifier."], 422);
        }

        $validator = $this->validateur($request);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }
        $d = $validator->validated();
        $d['photo'] = $d['photo'] ?? null;
        $d['price'] = $d['price'] ?? null;
        $d['contact'] = $d['contact'] ?? null;
        $d['category'] = Group::find($d['group_id'])->name;

        $contenuModifie = collect(['type', 'title', 'group_id', 'description', 'photo'])
            ->contains(fn ($champ) => (string) ($d[$champ] ?? '') !== (string) ($l->{$champ} ?? ''));

        $l->fill($d);
        $revalidation = false;
        if (in_array($l->statut, ['refuse', 'retiree'], true) || (in_array($l->statut, ['approuve', 'indisponible'], true) && $contenuModifie)) {
            $l->statut = 'en_attente';
            $l->reject_reason = null;
            $revalidation = true;
        }
        $l->save();

        return response()->json([
            'ok' => true,
            'revalidation' => $revalidation,
            'message' => $revalidation
                ? "Modifications enregistrées : l'annonce repasse en validation avant d'être de nouveau visible."
                : 'Modifications enregistrées.',
        ]);
    }

    /** POST /marketplace/{id}/disponibilite — « Vendu / indisponible » ou remise en ligne. */
    public function disponibilite(Request $request, int $id)
    {
        $l = MarketplaceListing::where('user_id', $request->user()->id)->find($id);
        if (! $l || ! in_array($l->statut, ['approuve', 'indisponible'], true)) {
            return response()->json(['ok' => false, 'message' => 'Cette annonce ne peut pas changer de disponibilité.'], 422);
        }
        $l->update(['statut' => $request->boolean('disponible') ? 'approuve' : 'indisponible']);

        return response()->json(['ok' => true, 'statut' => $l->statut]);
    }

    /** POST /marketplace/{id}/renouveler — 90 jours de plus, sans nouvelle validation. */
    public function renouveler(Request $request, int $id)
    {
        $l = MarketplaceListing::where('user_id', $request->user()->id)->find($id);
        if (! $l || ! in_array($l->statut, ['approuve', 'expiree', 'indisponible'], true) || ! $l->publie_le) {
            return response()->json(['ok' => false, 'message' => 'Seule une annonce déjà validée peut être renouvelée.'], 422);
        }
        $l->update([
            'statut' => $l->statut === 'expiree' ? 'approuve' : $l->statut,
            'expire_le' => now()->addDays(MarketplaceListing::DUREE_JOURS),
            'rappel_expiration_at' => null,
        ]);

        return response()->json(['ok' => true, 'expire_le' => $l->expire_le->toIso8601String()]);
    }

    /** DELETE /marketplace/{id} — retirer sa propre annonce. */
    public function destroy(Request $request, int $id)
    {
        $listing = MarketplaceListing::where('user_id', $request->user()->id)->find($id);
        if (! $listing) {
            return response()->json(['ok' => false, 'message' => 'Annonce introuvable.'], 404);
        }

        $listing->delete();

        return response()->json(['ok' => true]);
    }

    /** Statuts proposés en filtre dans l'administration. */
    private const FILTRES_ADMIN = ['en_attente', 'signalees', 'approuve', 'refuse', 'indisponible', 'expiree', 'retiree', 'tous'];

    /**
     * GET /admin/marketplace?filtre=&q=&page= — annonces à modérer, avec
     * recherche (titre, vendeur, e-mail), signalements et statistiques.
     */
    public function adminIndex(Request $request)
    {
        $filtre = in_array($request->query('filtre'), self::FILTRES_ADMIN, true) ? $request->query('filtre') : 'en_attente';
        $signalees = DB::table('listing_reports')->where('statut', 'nouveau')->select('listing_id');

        $query = MarketplaceListing::with(['user:id,prenom,nom,email,ville,telephone,photo,role', 'group:id,name,icone,couleur'])
            ->select('marketplace_listings.*');
        match ($filtre) {
            'tous' => null,
            'signalees' => $query->whereIn('marketplace_listings.id', $signalees),
            default => $query->where('statut', $filtre),
        };
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")));
        }
        $page = $query->latest('updated_at')->paginate(20);

        $rapports = DB::table('listing_reports')->where('statut', 'nouveau')
            ->whereIn('listing_id', collect($page->items())->pluck('id'))->get()->groupBy('listing_id');

        $compteurs = MarketplaceListing::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        return response()->json([
            'ok' => true,
            'listings' => collect($page->items())->map(fn ($l) => $this->payload($l, withStatus: true, withEmail: true) + [
                'vues' => $l->vues,
                'contacts' => $l->contacts,
                'expire_le' => $l->expire_le?->toIso8601String(),
                'updated_at' => $l->updated_at?->toIso8601String(),
                'signalements' => ($rapports[$l->id] ?? collect())->map(fn ($r) => ['motif' => $r->motif, 'date' => $r->created_at])->values(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'compteurs' => [
                'en_attente' => (int) ($compteurs['en_attente'] ?? 0),
                'signalees' => DB::table('listing_reports')->where('statut', 'nouveau')->distinct()->count('listing_id'),
                'approuve' => (int) ($compteurs['approuve'] ?? 0),
                'refuse' => (int) ($compteurs['refuse'] ?? 0),
                'indisponible' => (int) ($compteurs['indisponible'] ?? 0),
                'expiree' => (int) ($compteurs['expiree'] ?? 0),
                'retiree' => (int) ($compteurs['retiree'] ?? 0),
                'tous' => (int) $compteurs->sum(),
            ],
            'categories' => $this->categories(),
        ]);
    }

    /**
     * PUT /admin/marketplace/{id} — petite correction par l'administration
     * (faute, catégorie, prix…). Le vendeur est prévenu.
     */
    public function adminUpdate(Request $request, int $id)
    {
        $l = MarketplaceListing::find($id);
        if (! $l) {
            return response()->json(['ok' => false, 'message' => 'Annonce introuvable.'], 404);
        }
        $v = Validator::make($request->all(), [
            'type' => 'required|in:service,produit',
            'title' => 'required|string|min:3|max:120',
            'group_id' => 'required|integer|exists:groups,id',
            'description' => 'required|string|min:20|max:2000',
            'price' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:300',
        ], ['title.required' => 'Le titre est obligatoire.', 'description.min' => 'La description doit faire au moins 20 caractères.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $l->update([
            'type' => $d['type'], 'title' => $d['title'], 'group_id' => $d['group_id'],
            'category' => Group::find($d['group_id'])->name, 'description' => $d['description'], 'price' => $d['price'] ?? null,
        ]);

        $note = trim((string) ($d['note'] ?? ''));
        MemberNotification::create([
            'user_id' => $l->user_id,
            'type' => 'info',
            'title' => 'Votre annonce a été corrigée',
            'body' => "L'administration a apporté une correction à « {$l->title} ».".($note !== '' ? " {$note}" : ''),
            'link' => "/espace-membre/marketplace?onglet=mes-annonces",
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * PUT /admin/marketplace/{id}/retirer — retire une annonce du catalogue
     * avec un motif transmis au vendeur ; les signalements sont traités.
     */
    public function retirer(Request $request, int $id)
    {
        $l = MarketplaceListing::find($id);
        if (! $l) {
            return response()->json(['ok' => false, 'message' => 'Annonce introuvable.'], 404);
        }
        $motif = trim((string) $request->input('motif'));
        if (mb_strlen($motif) < 5) {
            return response()->json(['ok' => false, 'message' => 'Indiquez le motif du retrait : il sera transmis au vendeur.'], 422);
        }
        $l->update(['statut' => 'retiree', 'reject_reason' => mb_substr($motif, 0, 300)]);
        DB::table('listing_reports')->where('listing_id', $l->id)->where('statut', 'nouveau')->update(['statut' => 'traite', 'updated_at' => now()]);

        MemberNotification::create([
            'user_id' => $l->user_id,
            'type' => 'warning',
            'title' => 'Annonce retirée par l\'administration',
            'body' => "« {$l->title} » a été retirée de la Marketplace. Motif : {$motif}",
            'link' => '/espace-membre/marketplace?onglet=mes-annonces',
        ]);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/marketplace/{id}/signalements — classer les signalements sans suite. */
    public function classerSignalements(int $id)
    {
        DB::table('listing_reports')->where('listing_id', $id)->where('statut', 'nouveau')->update(['statut' => 'traite', 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/marketplace/{id}/approve — publier l'annonce. */
    public function approve(int $id)
    {
        $listing = MarketplaceListing::findOrFail($id);
        $listing->update([
            'statut' => 'approuve', 'reject_reason' => null,
            'publie_le' => now(), 'expire_le' => now()->addDays(MarketplaceListing::DUREE_JOURS), 'rappel_expiration_at' => null,
        ]);

        MemberNotification::create([
            'user_id' => $listing->user_id,
            'type' => 'info',
            'title' => 'Votre annonce est en ligne !',
            'body' => "« {$listing->title} » a été validée par l'administration et est maintenant visible sur la Marketplace.",
            'link' => "/espace-membre/marketplace?annonce={$listing->id}",
        ]);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/marketplace/{id}/reject — refuser avec motif. */
    public function reject(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|min:5|max:300',
        ], [
            'motif.required' => 'Indiquez le motif du refus : il aide le membre à corriger son annonce.',
            'motif.min' => 'Indiquez le motif du refus : il aide le membre à corriger son annonce.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $listing = MarketplaceListing::findOrFail($id);
        $motif = $request->input('motif') ?: null;
        $listing->update(['statut' => 'refuse', 'reject_reason' => $motif]);

        MemberNotification::create([
            'user_id' => $listing->user_id,
            'type' => 'alert',
            'title' => 'Annonce non retenue',
            'body' => "« {$listing->title} » n'a pas été validée.".($motif ? " Motif : {$motif}" : '').' Vous pouvez la modifier et la soumettre à nouveau.',
            'link' => '/espace-membre/marketplace?onglet=mes-annonces',
        ]);

        return response()->json(['ok' => true]);
    }

    /** DELETE /admin/marketplace/{id} — suppression définitive. */
    public function adminDestroy(int $id)
    {
        $l = MarketplaceListing::findOrFail($id);
        MemberNotification::create([
            'user_id' => $l->user_id,
            'type' => 'warning',
            'title' => 'Annonce supprimée',
            'body' => "« {$l->title} » a été supprimée définitivement de la Marketplace par l'administration.",
            'link' => '/espace-membre/marketplace?onglet=mes-annonces',
        ]);
        $l->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(MarketplaceListing $l, bool $withStatus = false, bool $withEmail = false): array
    {
        $data = [
            'id' => $l->id,
            'type' => $l->type,
            'title' => $l->title,
            'category' => $l->category,
            'groupe' => $l->group_id ? [
                'id' => $l->group_id,
                'nom' => $l->group->name ?? $l->category,
                'icone' => $l->group->icone ?? 'network',
                'couleur' => $l->group->couleur ?? '#031D59',
            ] : null,
            'description' => $l->description,
            'price' => $l->price,
            'contact' => $l->contact,
            'photo' => $l->photo,
            'created_at' => $l->created_at?->toIso8601String(),
            'seller' => $l->relationLoaded('user') && $l->user ? [
                'id' => $l->user->id,
                'prenom' => $l->user->prenom,
                'nom' => $l->user->nom,
                'ville' => $l->user->ville,
                'photo' => $l->user->photo,
                'role' => $l->user->role,
                // Coordonnées personnelles réservées à l'administration ; les
                // membres contactent le vendeur par la messagerie.
                ...($withEmail ? ['email' => $l->user->email, 'telephone' => $l->user->telephone] : []),
            ] : null,
        ];

        if ($withStatus) {
            $data['statut'] = $l->statut;
            $data['reject_reason'] = $l->reject_reason;
        }

        return $data;
    }
}
