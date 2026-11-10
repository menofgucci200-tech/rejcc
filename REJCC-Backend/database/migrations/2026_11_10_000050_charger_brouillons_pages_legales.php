<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Charge les premières rédactions des pages légales (database/data/pages-legales/*.md)
     * comme BROUILLONS : elles restent non publiées, le Bureau les relit, complète les
     * passages marqués « [À compléter : …] » et les publie depuis l'administration.
     * Un texte déjà saisi dans l'administration n'est jamais remplacé.
     */
    public function up(): void
    {
        foreach (glob(database_path('data/pages-legales/*.md')) as $fichier) {
            $slug = basename($fichier, '.md');
            DB::table('legal_pages')
                ->where('slug', $slug)
                ->where(fn ($q) => $q->whereNull('contenu')->orWhere('contenu', ''))
                ->update(['contenu' => trim(file_get_contents($fichier))."\n", 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : seuls des brouillons vides ont été remplis.
    }
};
