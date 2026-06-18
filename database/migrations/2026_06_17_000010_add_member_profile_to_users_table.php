<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom')->nullable()->after('name');
            $table->string('nom')->nullable()->after('prenom');
            $table->string('telephone', 20)->nullable()->after('email');
            $table->string('genre')->nullable();
            $table->string('ville')->nullable();
            $table->string('secteur')->nullable();
            $table->string('profil')->nullable(); // etudiant / porteur / entrepreneur
            $table->string('organisation')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('role')->default('member'); // member / admin
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'prenom', 'nom', 'telephone', 'genre', 'ville', 'secteur',
                'profil', 'organisation', 'bio', 'photo', 'role',
            ]);
        });
    }
};
