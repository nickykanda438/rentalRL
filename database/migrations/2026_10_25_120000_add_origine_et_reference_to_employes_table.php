<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute l'origine (province, district, territoire) et la personne de référence.
     */
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('adresse');
            $table->string('district', 100)->nullable()->after('province');
            $table->string('territoire', 100)->nullable()->after('district');
            $table->string('personne_reference')->nullable()->after('territoire');
            $table->string('contact_reference', 30)->nullable()->after('personne_reference');
        });
    }

    /**
     * Retire les colonnes ajoutées.
     */
    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn(['province', 'district', 'territoire', 'personne_reference', 'contact_reference']);
        });
    }
};
