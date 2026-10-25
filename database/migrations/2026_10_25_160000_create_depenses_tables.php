<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dépenses et sommes reçues (fonds).
     * Une dépense est payée soit avec les recettes de location, soit avec une somme reçue précise.
     */
    public function up(): void
    {
        Schema::create('categories_depense', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique();
            $table->string('couleur', 20)->default('gray'); // couleur du badge
            $table->boolean('par_defaut')->default(false);  // les catégories par défaut ne se suppriment pas
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // DE-02 : 6 catégories par défaut
        $maintenant = now();
        DB::table('categories_depense')->insert(collect([
            ['Carburant', 'orange'], ['Maintenance', 'green'], ['Assurance', 'teal'],
            ['Salaire', 'lime'], ['Marketing', 'gray'], ['Autre', 'red'],
        ])->map(fn ($c) => ['nom' => $c[0], 'couleur' => $c[1], 'par_defaut' => true, 'active' => true,
            'created_at' => $maintenant, 'updated_at' => $maintenant])->all());

        // Somme d'argent reçue (hors recettes de location) : qui l'a donnée, quand, pourquoi
        Schema::create('fonds', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();   // FDS-AAAAMM-0001
            $table->char('mois', 7)->index();
            $table->date('date_reception');
            $table->string('provenance');                         // qui a donné l'argent
            $table->string('motif');                              // pourquoi / pour quoi faire
            $table->char('devise', 3)->default('USD');            // devise de la saisie
            $table->decimal('montant_saisi', 16, 2);
            $table->decimal('taux_change', 10, 2);
            $table->decimal('montant_usd', 14, 2);
            $table->decimal('montant_cdf', 16, 2);
            $table->string('mode_reception', 30)->default('Espèces');
            $table->string('reference')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();
        });

        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();   // DEP-AAAAMM-0001
            $table->char('mois', 7)->index();
            $table->date('date_depense');
            $table->foreignId('categorie_id')->constrained('categories_depense')->restrictOnDelete();
            $table->string('description');                        // ce qui a été fait / acheté
            $table->string('beneficiaire')->nullable();           // à qui l'argent a été payé

            // Avec quel argent : recettes de location ou une somme reçue
            $table->string('source', 20)->default('recettes');    // recettes, fonds
            $table->foreignId('fonds_id')->nullable()->constrained('fonds')->restrictOnDelete();

            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();
            $table->foreignId('employe_id')->nullable()->constrained('employes')->nullOnDelete(); // qui a effectué la dépense

            $table->char('devise', 3)->default('USD');
            $table->decimal('montant_saisi', 16, 2);
            $table->decimal('taux_change', 10, 2);
            $table->decimal('montant_usd', 14, 2);
            $table->decimal('montant_cdf', 16, 2);
            $table->string('mode_paiement', 30)->default('Espèces');
            $table->string('piece_jointe')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['source', 'fonds_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
        Schema::dropIfExists('fonds');
        Schema::dropIfExists('categories_depense');
    }
};
