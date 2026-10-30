<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communauté REJCC : espace de discussion de tous les membres sur la
 * plateforme, qui remplace les deux communautés WhatsApp (fusionnées en une
 * seule, sans limite de membres). Un canal Annonces (réservé à l'équipe) et
 * des salons de discussion, modérés par les administrateurs et les référents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80);
            $table->string('slug', 90)->unique();
            $table->string('description', 300)->nullable();
            $table->string('emoji', 16)->nullable();
            $table->string('type', 20)->default('discussion'); // annonces | discussion
            $table->string('acces', 20)->default('tous'); // tous | abonnes
            $table->boolean('lecture_seule')->default(false);
            $table->unsignedInteger('mode_lent_secondes')->default(0);
            $table->text('regles')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamp('archive_at')->nullable();
            $table->timestamps();
        });

        // État de chaque membre dans un salon (créé à sa première visite) :
        // lecture, notifications, rôle de référent, sanctions.
        Schema::create('salon_membres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('membre'); // membre | moderateur
            $table->unsignedBigInteger('lu_jusqua')->default(0);
            $table->string('notifications', 20)->nullable(); // tout | mentions | rien (null = défaut du salon)
            $table->timestamp('silence_jusqua')->nullable(); // rendu muet par un modérateur
            $table->timestamp('exclu_at')->nullable();
            $table->timestamp('regles_acceptees_at')->nullable();
            $table->timestamp('dernier_message_at')->nullable(); // mode lent
            $table->timestamps();
            $table->unique(['salon_id', 'user_id']);
            $table->index(['user_id', 'salon_id']);
        });

        Schema::create('salon_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('texte'); // texte | image | fichier | vocal
            $table->text('body')->nullable();
            $table->foreignId('reponse_a_id')->nullable()->constrained('salon_messages')->nullOnDelete();
            $table->json('mentions')->nullable(); // identifiants des membres mentionnés
            $table->boolean('mention_tous')->default(false);
            $table->string('fichier', 255)->nullable(); // chemin privé (stockage du site)
            $table->string('fichier_nom', 160)->nullable();
            $table->string('fichier_mime', 100)->nullable();
            $table->unsignedInteger('fichier_taille')->nullable();
            $table->unsignedSmallInteger('duree')->nullable(); // note vocale, en secondes
            $table->timestamp('edite_at')->nullable();
            $table->timestamp('supprime_at')->nullable();
            $table->foreignId('supprime_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['salon_id', 'id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('salon_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();
            $table->unique(['salon_message_id', 'user_id', 'emoji']);
        });

        Schema::create('salon_signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('motif', 500)->nullable();
            $table->string('statut', 20)->default('nouveau'); // nouveau | traite
            $table->string('decision', 20)->nullable(); // classe | supprime
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->timestamps();
            $table->unique(['salon_message_id', 'user_id']);
        });

        // Journal des actions de modération (consultable dans le dashboard).
        Schema::create('salon_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('acteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('salon_message_id')->nullable();
            $table->string('action', 40);
            $table->string('detail', 500)->nullable();
            $table->timestamps();
            $table->index(['salon_id', 'created_at']);
        });

        // Les salons repris des communautés WhatsApp « Réseau Entrepreneurial
        // des Jeunes Chrétiens Catholiques » 1 et 2 (mêmes groupes).
        $maintenant = now();
        $salons = [
            ['Annonces', 'annonces', '📣', 'annonces', "Les informations importantes du REJCC. Seule l'équipe du réseau publie ici.", 0],
            ['Spiritualité et Leadership Chrétien', 'spiritualite-et-leadership-chretien', '🙏', 'discussion', 'Espace dédié à la vie spirituelle, à la foi et au leadership chrétien.', 1],
            ['Échanges & Networking', 'echanges-et-networking', '🤝', 'discussion', "Espace d'échanges, d'entraide et de mise en relation entre les membres.", 2],
            ['Entrepreneurs & Projets', 'entrepreneurs-et-projets', '💼', 'discussion', "Espace d'échanges autour de l'entrepreneuriat et des projets des membres.", 3],
            ['Opportunités & Formations', 'opportunites-et-formations', '📚', 'discussion', "Espace dédié au partage : offres d'emploi, stages, formations et appels à projets.", 4],
        ];
        $regles = "Restez bienveillant et respectueux, dans l'esprit de la charte du membre.\nPas de publicité répétée ni de messages en chaîne.\nPas de contenu politique, offensant ou contraire à la foi catholique.\nLes échanges personnels se poursuivent en message privé.";
        foreach ($salons as [$nom, $slug, $emoji, $type, $description, $ordre]) {
            DB::table('salons')->insert([
                'nom' => $nom, 'slug' => $slug, 'emoji' => $emoji, 'type' => $type, 'acces' => 'tous',
                'description' => $description, 'regles' => $type === 'discussion' ? $regles : null,
                'ordre' => $ordre, 'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_moderations');
        Schema::dropIfExists('salon_signalements');
        Schema::dropIfExists('salon_reactions');
        Schema::dropIfExists('salon_messages');
        Schema::dropIfExists('salon_membres');
        Schema::dropIfExists('salons');
    }
};
