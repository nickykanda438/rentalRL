<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Numéro de référence automatique : SAL-AAAAMM-0001 (salaires) et AV-AAAAMM-0001 (avances).
     * Les lignes déjà enregistrées reçoivent aussi un numéro.
     */
    public function up(): void
    {
        foreach (['bulletins' => 'SAL', 'avances' => 'AV'] as $table => $prefixe) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('numero', 20)->nullable()->unique()->after('id');
            });

            $compteurs = [];
            DB::table($table)->orderBy('mois')->orderBy('id')->get(['id', 'mois'])
                ->each(function ($ligne) use ($table, $prefixe, &$compteurs) {
                    $compteurs[$ligne->mois] = ($compteurs[$ligne->mois] ?? 0) + 1;
                    DB::table($table)->where('id', $ligne->id)->update([
                        'numero' => $prefixe.'-'.str_replace('-', '', $ligne->mois).'-'.str_pad($compteurs[$ligne->mois], 4, '0', STR_PAD_LEFT),
                    ]);
                });
        }
    }

    public function down(): void
    {
        foreach (['bulletins', 'avances'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['numero']);
                $t->dropColumn('numero');
            });
        }
    }
};
