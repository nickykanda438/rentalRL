<?php

namespace App\Support;

/**
 * Conversion USD ↔ CDF (AR-01 / DE-01) : on saisit dans une devise, on stocke les deux, arrondis à 2 décimales.
 */
class Devise
{
    public const LISTE = ['USD', 'CDF'];

    /**
     * @return array{montant_usd: float, montant_cdf: float}
     */
    public static function convertir(string $devise, float $montant, float $taux): array
    {
        $taux = max($taux, 0.01);

        return $devise === 'CDF'
            ? ['montant_usd' => round($montant / $taux, 2), 'montant_cdf' => round($montant, 2)]
            : ['montant_usd' => round($montant, 2), 'montant_cdf' => round($montant * $taux, 2)];
    }
}
