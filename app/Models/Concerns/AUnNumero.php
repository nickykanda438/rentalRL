<?php

namespace App\Models\Concerns;

/**
 * Attribue automatiquement un numéro de référence unique à la création :
 * PREFIXE-AAAAMM-0001, PREFIXE-AAAAMM-0002… (la numérotation repart à 1 chaque mois).
 *
 * Le modèle doit avoir une colonne « numero », une colonne « mois » (AAAA-MM)
 * et une constante PREFIXE_NUMERO (ex. 'SAL').
 */
trait AUnNumero
{
    protected static function bootAUnNumero(): void
    {
        static::creating(function (self $modele) {
            if (blank($modele->numero)) {
                $modele->numero = static::prochainNumero($modele->mois);
            }
        });
    }

    public static function prochainNumero(string $mois): string
    {
        $prefixe = static::PREFIXE_NUMERO.'-'.str_replace('-', '', $mois).'-';

        $dernier = static::query()
            ->where('numero', 'like', $prefixe.'%')
            ->lockForUpdate()                 // évite deux numéros identiques en même temps
            ->orderByDesc('numero')
            ->value('numero');

        $suivant = $dernier ? (int) substr($dernier, -4) + 1 : 1;

        return $prefixe.str_pad((string) $suivant, 4, '0', STR_PAD_LEFT);
    }
}
