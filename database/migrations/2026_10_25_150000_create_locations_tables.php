<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Budget location : véhicules, clients et locations (recettes).
     */
    public function up(): void
    {
        // Flotte (la page Flotte sera rendue dynamique à l'étape suivante)
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->string('marque', 50);
            $table->string('modele', 100);
            $table->unsignedSmallInteger('annee')->nullable();
            $table->string('plaque', 20)->unique();
            $table->decimal('tarif_journalier', 10, 2)->default(0);
            $table->string('categorie', 30)->default('SUV');
            $table->string('statut', 20)->default('Disponible'); // Disponible, Loué, En maintenance
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        // Client loueur (il n'utilise pas l'application)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->index();
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('numero_contrat', 20)->unique();       // CONT-2026-000001
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('chauffeur_id')->nullable()->constrained('employes')->restrictOnDelete();
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->restrictOnDelete();

            $table->string('destination')->nullable();
            $table->date('date_depart')->index();
            $table->unsignedSmallInteger('nombre_jours');
            $table->date('date_fin');                               // départ + nombre de jours
            $table->date('date_retour')->nullable();                // retour réel

            // Montants (recalculés côté serveur)
            $table->decimal('montant_journalier', 10, 2);
            $table->decimal('sous_total', 12, 2);
            $table->decimal('pourcentage_commission', 5, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
            $table->decimal('total_usd', 12, 2);                    // sous-total − commission
            $table->decimal('taux_change', 10, 2);                  // CDF pour 1 USD
            $table->decimal('total_cdf', 16, 2);

            $table->date('date_perception')->nullable();            // date où l'argent a été perçu
            $table->string('statut', 20)->default('Actif')->index(); // Actif, Terminé, Annulé
            $table->string('piece_jointe')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('vehicules');
    }
};
