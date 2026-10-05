<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Clôture d'un mentorat : bilan, puis évaluation du mentor par le mentoré. */
    public function up(): void
    {
        Schema::table('mentorships', function (Blueprint $table) {
            $table->text('bilan')->nullable();
            $table->foreignId('termine_par')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('note')->nullable(); // 1 à 5
            $table->text('avis')->nullable();
            $table->timestamp('evalue_at')->nullable();
            $table->boolean('cree_par_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mentorships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('termine_par');
            $table->dropColumn(['bilan', 'note', 'avis', 'evalue_at', 'cree_par_admin']);
        });
    }
};
