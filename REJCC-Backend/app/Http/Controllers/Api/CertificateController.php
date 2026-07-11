<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormationEnrollment;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Certificats du membre courant : émis automatiquement pour chaque
     * formation certifiante terminée (aucune action admin requise).
     */
    public function mine(Request $request)
    {
        $certificates = $this->completedCertifying()
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(fn (FormationEnrollment $e) => [
                'id' => $e->id,
                'reference' => $this->reference($e),
                'title' => $e->formation->title,
                'category' => $e->formation->category,
                'issued_at' => $e->completed_at->toDateString(),
            ]);

        return response()->json(['ok' => true, 'certificates' => $certificates->values()]);
    }

    /** Registre complet des certificats émis (admin). */
    public function adminIndex()
    {
        $certificates = $this->completedCertifying()
            ->with('user:id,prenom,nom,name,email')
            ->get()
            ->map(fn (FormationEnrollment $e) => [
                'id' => $e->id,
                'reference' => $this->reference($e),
                'title' => $e->formation->title,
                'member' => $e->user ? (trim($e->user->prenom.' '.$e->user->nom) ?: $e->user->name) : '—',
                'email' => $e->user?->email,
                'issued_at' => $e->completed_at->toDateString(),
            ]);

        return response()->json(['ok' => true, 'certificates' => $certificates->values()]);
    }

    private function completedCertifying()
    {
        return FormationEnrollment::with('formation')
            ->whereNotNull('completed_at')
            ->whereHas('formation', fn ($q) => $q->where('is_certifying', true))
            ->orderByDesc('completed_at');
    }

    private function reference(FormationEnrollment $e): string
    {
        return 'REJCC-CERT-'.$e->completed_at->format('Y').'-'.str_pad((string) $e->id, 4, '0', STR_PAD_LEFT);
    }
}
