<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Groupes sectoriels : pôles qui regroupent les membres par domaine
        // d'activité (miroir des groupes WhatsApp de la communauté). Un membre
        // peut adhérer à autant de groupes qu'il veut.
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 400)->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
        });

        // Les 16 groupes sectoriels officiels de la communauté.
        $groupes = [
            ['Agriculture & Pêche', 'Pour les entrepreneurs des secteurs de l\'agriculture, de l\'agroalimentaire et de la pêche.'],
            ['Informatique & Technologie', 'Pour les acteurs des nouvelles technologies, du numérique et de l\'innovation.'],
            ['Communication & Médias', 'Pour les professionnels de la communication, du marketing et des médias.'],
            ['Finance & Investissement', 'Pour les experts en finance, banquiers, investisseurs et conseillers financiers.'],
            ['Administration & Gestion', 'Pour les professionnels de la gestion d\'entreprise, des ressources humaines et de la comptabilité.'],
            ['Éducation & Formation', 'Pour les acteurs de l\'enseignement, de la formation professionnelle et du développement des compétences.'],
            ['Santé & Bien-être', 'Pour les professionnels de la santé, du bien-être et des services médicaux.'],
            ['BTP & Construction', 'Pour les entrepreneurs dans le bâtiment, les travaux publics et l\'immobilier.'],
            ['Industrie & Production', 'Pour les acteurs de l\'industrie manufacturière et des procédés de production.'],
            ['Commerce & Distribution', 'Pour les entrepreneurs du commerce de détail, de la vente en ligne et de la distribution.'],
            ['Transport & Logistique', 'Pour les professionnels du transport de marchandises et de personnes, et de la chaîne logistique.'],
            ['Hôtellerie & Tourisme', 'Pour les acteurs de l\'hôtellerie, de la restauration et du tourisme.'],
            ['Artisanat & Création', 'Pour les artisans, créateurs et artistes.'],
            ['Événementiel & Loisirs', 'Pour les organisateurs d\'événements, de spectacles et d\'activités de loisirs.'],
            ['Développement Durable & Environnement', 'Pour les entrepreneurs engagés dans les solutions écologiques et durables.'],
            ['Action Sociale & Solidarité', 'Pour ceux qui œuvrent dans le domaine social, humanitaire et le développement communautaire.'],
        ];

        foreach ($groupes as $i => [$name, $description]) {
            DB::table('groups')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'ordre' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
