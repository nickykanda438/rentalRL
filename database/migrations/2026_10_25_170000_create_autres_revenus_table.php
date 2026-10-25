<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Autres revenus (AR-01) : entrées d'argent hors location, qui alimentent la caisse des recettes.
     */
    public function up(): void
    {
        Schema::create('autres_revenus', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();   // REV-AAAAMM-0001
            $table->char('mois', 7)->index();
            $table->date('date_revenu');
            $table->string('source');                             // source / provenance
            $table->text('description')->nullable();
            $table->char('devise', 3)->default('USD');
            $table->decimal('montant_saisi', 16, 2);
            $table->decimal('taux_change', 10, 2);
            $table->decimal('montant_usd', 14, 2);
            $table->decimal('montant_cdf', 16, 2);
            $table->string('mode_reception', 30)->default('Espèces');
            $table->string('reference')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autres_revenus');
    }
};
