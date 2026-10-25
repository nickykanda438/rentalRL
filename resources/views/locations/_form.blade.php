{{-- Formulaire de location (ajout et modification).
     $l : location à modifier (null pour une nouvelle) ; $p : préfixe unique des id. --}}
@php
    $l = $l ?? null;
    // Véhicule présélectionné (bouton « Louer ce véhicule » de la Flotte)
    $vehiculeDefaut = $l ? null : $vehicules->firstWhere('id', (int) ($vehiculeParDefaut ?? 0));
    // Valeur d'un menu déroulant : ancienne saisie si c'est ce formulaire qui a été soumis, sinon la valeur enregistrée
    $choix = fn (string $champ, $actuel) => old('_form') === $p ? old($champ) : $actuel;
    $select = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700';
@endphp
<input type="hidden" name="_form" value="{{ $p }}">

<p class="text-sm font-bold text-gray-900">Informations générales</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Date de départ *" name="date_depart" type="date" :value="$l?->date_depart->format('Y-m-d') ?? now()->format('Y-m-d')" required />
    <x-field :prefix="$p" label="Nombre de jours *" name="nombre_jours" type="number" min="1" max="365" :value="$l?->nombre_jours ?? 1" required />
    <x-field :prefix="$p" label="Destination" name="destination" placeholder="Ex. Matadi" :value="$l?->destination" />
    <div>
        <label for="{{ $p }}-chauffeur_id" class="{{ $label }}">Chauffeur</label>
        <select id="{{ $p }}-chauffeur_id" name="chauffeur_id" class="{{ $select }}">
            <option value="">— Aucun —</option>
            @foreach ($chauffeurs as $c)
                <option value="{{ $c->id }}" @selected((int) $choix('chauffeur_id', $l?->chauffeur_id) === $c->id)>{{ $c->nom }}</option>
            @endforeach
            @if ($l?->chauffeur && ! $chauffeurs->contains('id', $l->chauffeur_id))
                <option value="{{ $l->chauffeur_id }}" selected>{{ $l->chauffeur->nom }} (inactif)</option>
            @endif
        </select>
        @if ($chauffeurs->isEmpty())<p class="mt-1 text-xs text-accent-700">Aucun employé actif avec le rôle « Chauffeur ».</p>@endif
    </div>
    <div>
        <label for="{{ $p }}-vehicule_id" class="{{ $label }}">Véhicule (facultatif)</label>
        <select id="{{ $p }}-vehicule_id" name="vehicule_id" class="{{ $select }}">
            <option value="">— Aucun —</option>
            @foreach ($vehicules as $v)
                <option value="{{ $v->id }}" data-tarif="{{ $v->tarif_journalier }}" @selected((int) $choix('vehicule_id', $l?->vehicule_id ?? $vehiculeDefaut?->id) === $v->id)>{{ $v->libelle() }}{{ $v->statut === 'Loué' && $v->id !== $l?->vehicule_id ? ' (loué)' : '' }}</option>
            @endforeach
        </select>
        @if ($vehicules->isEmpty())<p class="mt-1 text-xs text-gray-500">Aucun véhicule enregistré dans la flotte pour l'instant.</p>@endif
    </div>
    <x-field :prefix="$p" label="Montant journalier (USD) *" name="montant_journalier" inputmode="decimal" :value="$l ? \App\Support\Format::usd($l->montant_journalier) : ($vehiculeDefaut ? \App\Support\Format::usd($vehiculeDefaut->tarif_journalier) : '')" placeholder="0,00" required />
</div>

<p class="text-sm font-bold text-gray-900">Client</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Nom *" name="client_nom" placeholder="Nom du client ou de l'organisation" :value="$l?->client->nom" list="liste-clients" autocomplete="off" required />
    <x-field :prefix="$p" label="Téléphone" name="client_telephone" type="tel" placeholder="+243 ..." :value="$l?->client->telephone" />
    <x-field :prefix="$p" label="Adresse" name="client_adresse" placeholder="Adresse" :value="$l?->client->adresse" />
</div>

<p class="text-sm font-bold text-gray-900">Commission et taux</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Pourcentage de commission *" name="pourcentage_commission" inputmode="decimal" :value="$l ? rtrim(rtrim(number_format((float) $l->pourcentage_commission, 2, ',', ''), '0'), ',') : config('rental.commission')" required />
    <x-field :prefix="$p" label="Taux de change (CDF pour 1 USD) *" name="taux_change" inputmode="decimal" :value="$l ? rtrim(rtrim(number_format((float) $l->taux_change, 2, ',', ''), '0'), ',') : config('rental.taux_change')" required />
    <x-field :prefix="$p" label="Date de perception" name="date_perception" type="date" :value="$l?->date_perception?->format('Y-m-d')" />
</div>

{{-- Calcul automatique (resources/js/rental.js) --}}
<div class="grid grid-cols-2 gap-3 rounded-xl border border-primary-100 bg-primary-50 p-4 sm:grid-cols-5">
    <div><p class="text-xs text-gray-600">Sous-total</p><p class="mt-1 font-bold tabular-nums" data-calc="brut">0,00 $</p></div>
    <div><p class="text-xs text-gray-600">Commission</p><p class="mt-1 font-bold tabular-nums" data-calc="commission">0,00 $</p></div>
    <div><p class="text-xs text-gray-600">Total USD</p><p class="mt-1 font-bold tabular-nums text-primary-700" data-calc="total">0,00 $</p></div>
    <div><p class="text-xs text-gray-600">Total CDF</p><p class="mt-1 font-bold tabular-nums" data-calc="cdf">0 FC</p></div>
    <div><p class="text-xs text-gray-600">Retour prévu</p><p class="mt-1 font-bold tabular-nums" data-calc="fin">—</p></div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $p }}-piece_jointe" class="{{ $label }}">Pièce jointe</label>
        <input id="{{ $p }}-piece_jointe" name="piece_jointe" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
        <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG, 5 Mo maximum.@if ($l?->piece_jointe) Un fichier est déjà joint ; en choisir un nouveau le remplace.@endif</p>
    </div>
    <x-field :prefix="$p" label="Observation" name="observation" type="textarea" placeholder="Facultatif" :value="$l?->observation" />
</div>
