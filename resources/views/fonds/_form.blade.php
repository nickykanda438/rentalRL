{{-- Formulaire « somme reçue ». $f : somme ou null ; $p : préfixe unique. --}}
@php $f = $f ?? null; @endphp
<input type="hidden" name="_form" value="{{ $p }}">
<p class="rounded-lg bg-teal-50 px-4 py-3 text-sm text-teal-900">
    Enregistrez ici l'argent remis à l'entreprise <strong>pour un usage précis</strong>
    (ex. une personne vous donne une somme pour acheter des pièces) : chaque dépense faite avec cet argent y sera rattachée et vous saurez ce qu'il en reste.
    Pour un revenu qui va simplement dans la caisse, utilisez plutôt « Autres revenus ».
</p>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field :prefix="$p" label="Qui a donné l'argent ? *" name="provenance" placeholder="Nom de la personne ou de l'organisation" :value="$f?->provenance" required />
    <x-field :prefix="$p" label="Date de réception *" name="date_reception" type="date" :value="$f?->date_reception->format('Y-m-d') ?? now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
    <div class="sm:col-span-2">
        <x-field :prefix="$p" label="Pourquoi ? (motif / à quoi doit servir l'argent) *" name="motif" placeholder="Ex. Réparation de la Land Cruiser V8, achat de pneus..." :value="$f?->motif" required />
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-4">
    <x-field :prefix="$p" label="Devise" name="devise" :options="\App\Support\Devise::LISTE" :value="$f?->devise ?? 'USD'" />
    <x-field :prefix="$p" label="Montant reçu *" name="montant_saisi" inputmode="decimal" placeholder="0,00" :value="$f ? \App\Support\Format::usd($f->montant_saisi) : ''" required />
    <x-field :prefix="$p" label="Taux (CDF pour 1 $) *" name="taux_change" inputmode="decimal" :value="$f ? rtrim(rtrim(number_format((float) $f->taux_change, 2, ',', ''), '0'), ',') : config('rental.taux_change')" required />
    <x-field :prefix="$p" label="Reçu par" name="mode_reception" :options="\App\Models\Fonds::MODES" :value="$f?->mode_reception" />
</div>
<p class="rounded-lg bg-gray-50 px-4 py-2.5 text-sm text-gray-700" data-conversion>—</p>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field :prefix="$p" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." :value="$f?->reference" />
    <div>
        <label for="{{ $p }}-piece_jointe" class="mb-1.5 block text-sm font-medium text-gray-700">Pièce jointe (reçu, bordereau)</label>
        <input id="{{ $p }}-piece_jointe" name="piece_jointe" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
    </div>
    <div class="sm:col-span-2"><x-field :prefix="$p" label="Observation" name="observation" type="textarea" placeholder="Facultatif" :value="$f?->observation" /></div>
</div>
