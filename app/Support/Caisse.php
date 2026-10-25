<?php

namespace App\Support;

use App\Models\AutreRevenu;
use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\Credit;
use App\Models\Depense;
use App\Models\Location;

/**
 * Argent disponible, par source. Toutes les sorties sont comptées :
 * dépenses, avances sur salaire, salaires payés (net) et crédits accordés.
 * - recettes de location : locations non annulées − sorties payées avec ;
 * - autres revenus : chaque revenu a son propre reste (voir AutreRevenu::reste()).
 */
class Caisse
{
    /**
     * Sorties d'argent d'une source (« location » ou « revenu »), détaillées.
     *
     * @return array{depenses: float, avances: float, salaires: float, credits: float, total: float}
     */
    public static function sorties(string $source): array
    {
        $s = [
            'depenses' => (float) Depense::where('source', $source)->sum('montant_usd'),
            'avances' => (float) Avance::where('source', $source)->sum('montant'),
            'salaires' => (float) Bulletin::where('source', $source)->where('statut', 'Payé')->sum('net_a_payer'),
            'credits' => (float) Credit::where('source', $source)->sum('montant'),
        ];
        $s['total'] = round(array_sum($s), 2);

        return $s;
    }

    /**
     * @return array{entrees: float, sorties: float, detail: array, solde: float}
     */
    public static function locations(): array
    {
        $entrees = (float) Location::where('statut', '!=', 'Annulé')->sum('total_usd');
        $detail = self::sorties('location');

        return ['entrees' => $entrees, 'sorties' => $detail['total'], 'detail' => $detail, 'solde' => round($entrees - $detail['total'], 2)];
    }

    /**
     * @return array{entrees: float, sorties: float, detail: array, solde: float}
     */
    public static function autresRevenus(): array
    {
        $entrees = (float) AutreRevenu::sum('montant_usd');
        $detail = self::sorties('revenu');

        return ['entrees' => $entrees, 'sorties' => $detail['total'], 'detail' => $detail, 'solde' => round($entrees - $detail['total'], 2)];
    }

    /** Trésorerie totale disponible (les deux sources). */
    public static function total(): float
    {
        return round(self::locations()['solde'] + self::autresRevenus()['solde'], 2);
    }
}
