<?php

namespace App\Support;

use App\Models\AutreRevenu;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Choix de la source de l'argent (recettes de location ou autre revenu précis)
 * pour les avances, les salaires et les crédits.
 */
class SourceArgent
{
    public const SOURCES = ['location' => 'Recettes de location', 'revenu' => 'Autre revenu'];

    /**
     * Valide « source » et « autre_revenu_id » du formulaire.
     *
     * @return array{source: string, autre_revenu_id: ?int}
     */
    public static function valider(Request $request): array
    {
        $request->merge([
            'source' => $request->input('source', 'location'),
            'autre_revenu_id' => $request->input('source') === 'revenu' ? ($request->input('autre_revenu_id') ?: null) : null,
        ]);

        $d = $request->validate([
            'source' => ['required', Rule::in(array_keys(self::SOURCES))],
            'autre_revenu_id' => ['nullable', 'required_if:source,revenu', Rule::exists('autres_revenus', 'id')],
        ], [
            'source.in' => "Source de l'argent invalide.",
            'autre_revenu_id.required_if' => 'Choisissez le revenu avec lequel payer.',
            'autre_revenu_id.exists' => 'Ce revenu est introuvable.',
        ]);

        return ['source' => $d['source'], 'autre_revenu_id' => $d['autre_revenu_id'] ? (int) $d['autre_revenu_id'] : null];
    }

    /**
     * Vérifie qu'un autre revenu a assez de reste pour payer ce montant.
     */
    public static function verifierReste(array $source, float $montantUsd, string $champ = 'source'): void
    {
        if ($source['source'] !== 'revenu') {
            return;
        }

        $revenu = AutreRevenu::findOrFail($source['autre_revenu_id']);
        $reste = $revenu->reste();

        if ($montantUsd > $reste + 0.001) {
            throw ValidationException::withMessages([
                $champ => 'Il ne reste que '.Format::usd($reste)." $ sur le revenu {$revenu->numero} ({$revenu->source}) : impossible de payer ".Format::usd($montantUsd).' $.',
            ]);
        }
    }

    /**
     * Données des menus « Avec quel argent ? » : solde des locations et revenus qui ont encore un reste.
     *
     * @return array{caisse: array, revenusDisponibles: Collection}
     */
    public static function options(): array
    {
        return [
            'caisse' => Caisse::locations(),
            'revenusDisponibles' => AutreRevenu::avecUtilisation()->orderByDesc('date_revenu')->get()
                ->filter(fn (AutreRevenu $r) => $r->reste() > 0)->values(),
        ];
    }

    /** Message d'avertissement si la caisse des locations devient négative (RG-07). */
    public static function avertissement(array $source): ?string
    {
        if ($source['source'] === 'location' && ($solde = Caisse::locations()['solde']) < 0) {
            return 'Attention : la caisse des recettes de location est négative ('.Format::usd($solde).' $).';
        }

        return null;
    }
}
