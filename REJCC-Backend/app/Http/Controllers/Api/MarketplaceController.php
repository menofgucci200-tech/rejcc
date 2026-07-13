<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MemberNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Marketplace des membres : chacun peut proposer un service ou un produit ;
 * l'annonce n'apparaît qu'après validation par l'administration.
 */
class MarketplaceController extends Controller
{
    private const CATEGORIES = [
        'Artisanat & BTP', 'Alimentation & Restauration', 'Mode & Beauté',
        'Services numériques', 'Transport & Logistique', 'Éducation & Formation',
        'Santé & Bien-être', 'Agriculture', 'Commerce & Distribution',
        'Finance & Conseil', 'Événementiel', 'Autre',
    ];

    /** GET /marketplace — annonces approuvées, visibles par tous les membres. */
    public function index()
    {
        $listings = MarketplaceListing::where('statut', 'approuve')
            ->with('user:id,prenom,nom,ville,telephone')
            ->latest()
            ->get()
            ->map(fn ($l) => $this->payload($l));

        return response()->json(['ok' => true, 'listings' => $listings, 'categories' => self::CATEGORIES]);
    }

    /** GET /marketplace/mine — les annonces du membre connecté, tous statuts. */
    public function mine(Request $request)
    {
        $listings = MarketplaceListing::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($l) => $this->payload($l, withStatus: true));

        return response()->json(['ok' => true, 'listings' => $listings]);
    }

    /** POST /marketplace — soumettre une annonce (statut en attente). */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:service,produit',
            'title' => 'required|string|min:3|max:120',
            'category' => 'required|string|max:60',
            'description' => 'required|string|min:20|max:2000',
            'price' => 'nullable|string|max:80',
            'contact' => 'nullable|string|max:60',
            'photo' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Garde-fou anti-spam : 5 annonces en attente maximum par membre.
        $pending = MarketplaceListing::where('user_id', $request->user()->id)
            ->where('statut', 'en_attente')->count();
        if ($pending >= 5) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà 5 annonces en attente de validation. Patientez avant d\'en soumettre de nouvelles.'], 422);
        }

        $listing = MarketplaceListing::create([
            'user_id' => $request->user()->id,
            ...$validator->validated(),
            'statut' => 'en_attente',
        ]);

        return response()->json(['ok' => true, 'listing' => $this->payload($listing, withStatus: true)]);
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

    /** GET /admin/marketplace — toutes les annonces pour modération. */
    public function adminIndex()
    {
        $listings = MarketplaceListing::with('user:id,prenom,nom,email,ville,telephone')
            ->orderByRaw("statut = 'en_attente' DESC")
            ->latest()
            ->get()
            ->map(fn ($l) => $this->payload($l, withStatus: true, withEmail: true));

        return response()->json(['ok' => true, 'listings' => $listings]);
    }

    /** PUT /admin/marketplace/{id}/approve — publier l'annonce. */
    public function approve(int $id)
    {
        $listing = MarketplaceListing::findOrFail($id);
        $listing->update(['statut' => 'approuve', 'reject_reason' => null]);

        MemberNotification::create([
            'user_id' => $listing->user_id,
            'type' => 'info',
            'title' => 'Votre annonce est en ligne !',
            'body' => "« {$listing->title} » a été validée par l'administration et est maintenant visible sur la Marketplace.",
            'link' => '/espace-membre/marketplace',
        ]);

        return response()->json(['ok' => true]);
    }

    /** PUT /admin/marketplace/{id}/reject — refuser avec motif. */
    public function reject(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'nullable|string|max:300',
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
            'link' => '/espace-membre/marketplace',
        ]);

        return response()->json(['ok' => true]);
    }

    /** DELETE /admin/marketplace/{id} — suppression définitive. */
    public function adminDestroy(int $id)
    {
        MarketplaceListing::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(MarketplaceListing $l, bool $withStatus = false, bool $withEmail = false): array
    {
        $data = [
            'id' => $l->id,
            'type' => $l->type,
            'title' => $l->title,
            'category' => $l->category,
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
                'telephone' => $l->user->telephone,
                ...($withEmail ? ['email' => $l->user->email] : []),
            ] : null,
        ];

        if ($withStatus) {
            $data['statut'] = $l->statut;
            $data['reject_reason'] = $l->reject_reason;
        }

        return $data;
    }
}
