<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avances, salaires (bulletins payés) et crédits sortent soit des recettes de location,
     * soit d'un autre revenu précis. Les lignes existantes sont rattachées aux recettes de location.
     */
    public function up(): void
    {
        foreach (['avances', 'bulletins', 'credits'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('source', 20)->default('location');   // location, revenu
                $t->foreignId('autre_revenu_id')->nullable()->constrained('autres_revenus')->restrictOnDelete();
                $t->index(['source', 'autre_revenu_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['avances', 'bulletins', 'credits'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['source', 'autre_revenu_id']);
                $t->dropConstrainedForeignId('autre_revenu_id');
                $t->dropColumn('source');
            });
        }
    }
};
