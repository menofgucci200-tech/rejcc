<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fusion du module « Inscriptions (QR) » dans « Événements » : un seul
 * événement par rendez-vous, avec en option l'inscription publique (QR code)
 * pour les non-membres. Membres et invités sont dans une seule liste
 * (event_registrations) : même capacité, même billet, même pointage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('inscription_publique')->default(false)->after('date_limite');
            // Questions du formulaire d'inscription publique.
            $table->json('champs')->nullable()->after('inscription_publique');
            // Slug de l'ancien module : les QR déjà imprimés restent valides.
            $table->string('ancien_slug')->nullable()->index()->after('slug');
            // Annonce envoyée aux membres à la publication (une seule fois).
            $table->timestamp('annonce_at')->nullable()->after('champs');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            // Invité inscrit par le formulaire public (user_id vide).
            $table->string('prenom', 80)->nullable()->after('user_id');
            $table->string('nom', 80)->nullable()->after('prenom');
            $table->string('telephone', 30)->nullable()->after('nom');
            $table->string('email')->nullable()->after('telephone');
            $table->boolean('se_dit_membre')->default(false)->after('email');
            $table->json('reponses')->nullable()->after('se_dit_membre');
            $table->index(['event_id', 'telephone']);
        });

        if (! Schema::hasTable('registration_events')) {
            return;
        }

        foreach (DB::table('registration_events')->orderBy('id')->get() as $r) {
            $slug = $r->slug;
            for ($i = 2; DB::table('events')->where('slug', $slug)->exists(); $i++) {
                $slug = "{$r->slug}-{$i}";
            }
            $eventId = DB::table('events')->insertGetId([
                'title' => $r->title,
                'slug' => $slug,
                'ancien_slug' => $r->slug,
                'description' => $r->description,
                'location' => $r->location,
                'category' => 'Événement',
                // Sans date, l'événement reste en brouillon : l'admin la complète avant publication.
                'statut' => $r->starts_at ? 'publie' : 'brouillon',
                'starts_at' => $r->starts_at ?? now()->addMonth()->startOfDay()->setTime(9, 0),
                'image' => $r->poster,
                'capacity' => $r->capacity,
                'inscriptions_ouvertes' => (bool) $r->is_open,
                'date_limite' => $r->registration_deadline,
                'inscription_publique' => true,
                'champs' => $r->fields,
                'created_at' => $r->created_at,
                'updated_at' => now(),
            ]);

            foreach (DB::table('event_participants')->where('registration_event_id', $r->id)->orderBy('id')->get() as $p) {
                do {
                    $billet = 'B-'.strtoupper(Str::random(8));
                } while (DB::table('event_registrations')->where('billet', $billet)->exists());

                DB::table('event_registrations')->insert([
                    'event_id' => $eventId,
                    'user_id' => null,
                    'prenom' => $p->prenom,
                    'nom' => $p->nom,
                    'telephone' => $p->telephone,
                    'email' => $p->email,
                    'se_dit_membre' => (bool) $p->is_member,
                    'reponses' => $p->answers,
                    'billet' => $billet,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);
            }
        }

        // Les données sont reprises dans events / event_registrations.
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('registration_events');
    }

    public function down(): void
    {
        Schema::create('registration_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('poster', 500)->nullable();
            $table->json('fields')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('registration_deadline')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_event_id')->constrained()->cascadeOnDelete();
            $table->string('prenom');
            $table->string('nom');
            $table->string('telephone', 30);
            $table->string('email')->nullable();
            $table->boolean('is_member')->default(false);
            $table->json('answers')->nullable();
            $table->timestamps();
            $table->unique(['registration_event_id', 'telephone']);
        });

        DB::table('event_registrations')->whereNull('user_id')->delete();
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'telephone']);
            $table->dropColumn(['prenom', 'nom', 'telephone', 'email', 'se_dit_membre', 'reponses']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['ancien_slug']);
            $table->dropColumn(['inscription_publique', 'champs', 'ancien_slug', 'annonce_at']);
        });
    }
};
