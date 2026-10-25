<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Période d'un rapport : raccourci (mois, mois précédent, trimestre, année) ou dates libres « du / au ».
 */
class Periode
{
    public const RACCOURCIS = [
        'mois' => 'Ce mois',
        'mois_precedent' => 'Mois précédent',
        'trimestre' => 'Ce trimestre',
        'annee' => 'Cette année',
        'personnalise' => 'Dates libres',
    ];

    public function __construct(
        public readonly Carbon $du,
        public readonly Carbon $au,
        public readonly string $raccourci,
    ) {}

    public static function depuisRequete(Request $request): self
    {
        $raccourci = array_key_exists($request->query('periode'), self::RACCOURCIS) ? $request->query('periode') : 'mois';
        $aujourdhui = today();

        [$du, $au] = match ($raccourci) {
            'mois_precedent' => [$aujourdhui->copy()->subMonthNoOverflow()->startOfMonth(), $aujourdhui->copy()->subMonthNoOverflow()->endOfMonth()],
            'trimestre' => [$aujourdhui->copy()->startOfQuarter(), $aujourdhui->copy()->endOfQuarter()],
            'annee' => [$aujourdhui->copy()->startOfYear(), $aujourdhui->copy()->endOfYear()],
            'personnalise' => self::datesLibres($request),
            default => [$aujourdhui->copy()->startOfMonth(), $aujourdhui->copy()->endOfMonth()],
        };

        return new self($du->startOfDay(), $au->startOfDay(), $raccourci);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private static function datesLibres(Request $request): array
    {
        try {
            $du = Carbon::parse((string) $request->query('du'));
            $au = Carbon::parse((string) $request->query('au'));
        } catch (\Throwable) {
            return [today()->startOfMonth(), today()->endOfMonth()];
        }

        return $du->lte($au) ? [$du, $au] : [$au, $du];
    }

    /** [« 2026-10-01 », « 2026-10-31 »] pour whereBetween sur une colonne date. */
    public function bornes(): array
    {
        return [$this->du->format('Y-m-d'), $this->au->format('Y-m-d')];
    }

    /** Nombre de jours de la période (bornes incluses). */
    public function jours(): int
    {
        return (int) $this->du->diffInDays($this->au) + 1;
    }

    /** Mois couverts (AAAA-MM). */
    public function mois(): array
    {
        $liste = [];
        for ($m = $this->du->copy()->startOfMonth(); $m->lte($this->au); $m->addMonthNoOverflow()) {
            $liste[] = $m->format('Y-m');
        }

        return $liste;
    }

    public function libelle(): string
    {
        if ($this->du->isSameDay($this->du->copy()->startOfMonth()) && $this->au->isSameDay($this->du->copy()->endOfMonth())) {
            return Format::mois($this->du->format('Y-m'));
        }

        return 'du '.$this->du->format('d/m/Y').' au '.$this->au->format('d/m/Y');
    }

    /** Paramètres d'URL à conserver (liens, export). */
    public function parametres(): array
    {
        return ['periode' => $this->raccourci, 'du' => $this->du->format('Y-m-d'), 'au' => $this->au->format('Y-m-d')];
    }
}
