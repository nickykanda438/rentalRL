<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des employés.
     */
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();

            // Informations de base
            $table->string('nom');
            $table->string('email')->nullable()->unique();
            $table->string('telephone', 30)->nullable();
            $table->string('poste', 100);
            $table->string('role', 50)->default('Chauffeur');
            $table->decimal('salaire', 10, 2)->default(0); // en USD
            $table->date('date_embauche')->nullable();
            $table->string('statut', 20)->default('Actif'); // Actif, Suspendu, Licencié
            $table->string('lieu_travail')->nullable();

            // Informations personnelles
            $table->string('genre', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('etat_civil', 20)->nullable();
            $table->string('niveau_etudes', 50)->nullable();
            $table->string('domaine')->nullable();
            $table->string('adresse')->nullable();

            // Fichier
            $table->string('photo')->nullable(); // chemin dans storage/app/public

            $table->timestamps();

            $table->index(['role', 'statut']);
        });
    }

    /**
     * Supprime la table des employés.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
