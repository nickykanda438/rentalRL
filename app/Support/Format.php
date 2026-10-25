<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Petites fonctions de mise en forme utilisées dans les vues et les contrôleurs.
 */
class Format
{
    /** 1500 → « 1 500,00 » */
    public static function usd(float|int|string|null $montant): string
    {
        return number_format((float) $montant, 2, ',', ' ');
    }

    /** 13600000 → « 13 600 000 » (francs congolais, sans décimales à l'affichage) */
    public static function cdf(float|int|string|null $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ');
    }

    /** « 1 500,00 » → « 1500.00 » (pour la validation numeric) */
    public static function nombre(mixed $saisie): string
    {
        return str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $saisie));
    }

    /** « 2026-10 » → « Octobre 2026 » */
    public static function mois(string $mois): string
    {
        return ucfirst(Carbon::createFromFormat('!Y-m', $mois)->locale('fr')->translatedFormat('F Y'));
    }
}
