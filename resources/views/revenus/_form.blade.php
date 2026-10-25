{{-- Formulaire « autre revenu ». $r : revenu ou null ; $p : préfixe unique ; $sources : sources déjà utilisées. --}}
@php $r = $r ?? null; @endphp
<input type="hidden" name="_form" value="{{ $p }}">
<div class="grid gap-4 sm:grid-cols-2">
    <x-field :prefix="$p" label="Date *" name="date_revenu" type="date" :value="$r?->date_revenu->format('Y-m-d') ?? now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
    <x-field :prefix="$p" label="Source / provenance *" name="source" placeholder="Ex. Vente de pièces, partenariat, M. Kabasele..." :value="$r?->source" list="liste-sources" autocomplete="off" required />
</div>
<div class="grid gap-4 sm:grid-cols-4">
    <x-field :prefix="$p" label="Devise" name="devise" :options="\App\Support\Devise::LISTE" :value="$r?->devise ?? 'USD'" />
    <x-field :prefix="$p" label="Montant *" name="montant_saisi" inputmode="decimal" placeholder="0,00" :value="$r ? \App\Support\Format::usd($r->montant_saisi) : ''" required />
    <x-field :prefix="$p" label="Taux (CDF pour 1 $) *" name="taux_change" inputmode="decimal" :value="$r ? rtrim(rtrim(number_format((float) $r->taux_change, 2, ',', ''), '0'), ',') : config('rental.taux_change')" required />
    <x-field :prefix="$p" label="Reçu par" name="mode_reception" :options="\App\Models\AutreRevenu::MODES" :value="$r?->mode_reception" />
</div>
<p class="rounded-lg bg-gray-50 px-4 py-2.5 text-sm text-gray-700" data-conversion>—</p>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field :prefix="$p" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." :value="$r?->reference" />
    <div>
        <label for="{{ $p }}-piece_jointe" class="mb-1.5 block text-sm font-medium text-gray-700">Pièce jointe</label>
        <input id="{{ $p }}-piece_jointe" name="piece_jointe" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
    </div>
    <div class="sm:col-span-2"><x-field :prefix="$p" label="Description" name="description" type="textarea" placeholder="Facultatif" :value="$r?->description" /></div>
</div>
<p class="text-xs text-gray-500">Ce revenu pourra servir à payer des dépenses : vous verrez à tout moment combien a été utilisé et combien il reste.</p>
