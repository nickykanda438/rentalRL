<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables de la paie : avances, crédits, bulletins et remboursements de crédit.
     * Les mois sont stockés au format « AAAA-MM » (ex. 2026-10).
     */
    public function up(): void
    {
        // Avance sur salaire : déduite en totalité du bulletin du mois
        Schema::create('avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->restrictOnDelete();
            $table->decimal('montant', 10, 2);
            $table->date('date_avance');
            $table->char('mois', 7)->index(); // mois de déduction
            $table->string('mode_paiement', 30)->default('Espèces');
            $table->string('reference')->nullable();
            $table->text('commentaire')->nullable();
            $table->string('statut', 20)->default('En cours'); // En cours, Déduite
            $table->timestamps();
        });

        // Crédit : remboursé par mensualités sur les bulletins
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->restrictOnDelete();
            $table->decimal('montant', 10, 2);          // montant accordé
            $table->decimal('taux', 5, 2)->default(0);  // intérêt simple en %
            $table->unsignedSmallInteger('duree_mois');
            $table->decimal('montant_total', 10, 2);    // montant + intérêt
            $table->decimal('mensualite', 10, 2);
            $table->date('date_octroi');
            $table->char('mois_debut', 7);              // premier mois de retenue
            $table->text('commentaire')->nullable();
            $table->string('statut', 20)->default('Actif'); // Actif, Soldé
            $table->timestamps();
        });

        // Bulletin de paie : un par employé et par mois
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->restrictOnDelete();
            $table->char('mois', 7)->index();
            $table->decimal('salaire_base', 10, 2);
            $table->decimal('primes', 10, 2)->default(0);
            $table->decimal('total_avances', 10, 2)->default(0);
            $table->decimal('total_credits', 10, 2)->default(0);
            $table->decimal('net_a_payer', 10, 2);
            $table->string('statut', 20)->default('En attente'); // En attente, Payé
            $table->date('date_paiement')->nullable();
            $table->string('mode_paiement', 30)->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->unique(['employe_id', 'mois']);
        });

        // Retenue de crédit appliquée sur un bulletin
        Schema::create('credit_remboursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained('credits')->cascadeOnDelete();
            $table->foreignId('bulletin_id')->nullable()->constrained('bulletins')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_remboursements');
        Schema::dropIfExists('bulletins');
        Schema::dropIfExists('credits');
        Schema::dropIfExists('avances');
    }
};
