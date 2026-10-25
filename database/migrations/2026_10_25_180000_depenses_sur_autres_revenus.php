<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une dépense est payée soit avec les recettes de location, soit avec un autre revenu précis.
     * Les « sommes reçues » (table fonds) sont transférées dans les autres revenus, avec leurs dépenses.
     */
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->foreignId('autre_revenu_id')->nullable()->after('fonds_id')->constrained('autres_revenus')->restrictOnDelete();
        });

        // 1. Copie de chaque somme reçue en autre revenu (nouveau numéro REV-…) + rattachement de ses dépenses
        $compteurs = [];
        foreach (DB::table('fonds')->orderBy('date_reception')->orderBy('id')->get() as $fonds) {
            $prefixe = 'REV-'.str_replace('-', '', $fonds->mois).'-';
            if (! isset($compteurs[$prefixe])) {
                $dernier = DB::table('autres_revenus')->where('numero', 'like', $prefixe.'%')->max('numero');
                $compteurs[$prefixe] = $dernier ? (int) substr($dernier, -4) : 0;
            }
            $compteurs[$prefixe]++;

            $description = trim($fonds->motif.($fonds->observation ? "\n".$fonds->observation : ''));

            $revenuId = DB::table('autres_revenus')->insertGetId([
                'numero' => $prefixe.str_pad((string) $compteurs[$prefixe], 4, '0', STR_PAD_LEFT),
                'mois' => $fonds->mois,
                'date_revenu' => $fonds->date_reception,
                'source' => $fonds->provenance,
                'description' => $description." (ancienne somme {$fonds->numero})",
                'devise' => $fonds->devise,
                'montant_saisi' => $fonds->montant_saisi,
                'taux_change' => $fonds->taux_change,
                'montant_usd' => $fonds->montant_usd,
                'montant_cdf' => $fonds->montant_cdf,
                'mode_reception' => $fonds->mode_reception,
                'reference' => $fonds->reference,
                'piece_jointe' => $fonds->piece_jointe,
                'created_at' => $fonds->created_at,
                'updated_at' => now(),
            ]);

            DB::table('depenses')->where('fonds_id', $fonds->id)->update(['source' => 'revenu', 'autre_revenu_id' => $revenuId]);
        }

        // 2. « recettes » devient « location »
        DB::table('depenses')->where('source', 'recettes')->update(['source' => 'location']);
        DB::table('depenses')->where('source', 'fonds')->update(['source' => 'location']); // sécurité : aucune ne devrait rester

        // 3. Suppression de l'ancienne notion de somme reçue
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex(['source', 'fonds_id']);
            $table->dropConstrainedForeignId('fonds_id');
            $table->string('source', 20)->default('location')->change();
            $table->index(['source', 'autre_revenu_id']);
        });
        Schema::dropIfExists('fonds');
    }

    /**
     * Retour arrière partiel : la structure est recréée, mais les sommes ne sont pas reconstituées.
     */
    public function down(): void
    {
        Schema::create('fonds', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();
            $table->char('mois', 7)->index();
            $table->date('date_reception');
            $table->string('provenance');
            $table->string('motif');
            $table->char('devise', 3)->default('USD');
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

        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex(['source', 'autre_revenu_id']);
            $table->dropConstrainedForeignId('autre_revenu_id');
            $table->foreignId('fonds_id')->nullable()->after('source')->constrained('fonds')->restrictOnDelete();
            $table->index(['source', 'fonds_id']);
        });
        DB::table('depenses')->whereIn('source', ['location', 'revenu'])->update(['source' => 'recettes']);
    }
};
