{{-- Formulaire de dépense (ajout et modification).
     $d : dépense ou null ; $p : préfixe unique ; $revenuFixe : revenu imposé (depuis la fiche d'un revenu) ;
     $revenusDisponibles, $caisse, $categories, $vehicules, $employes. --}}
@php
    $d = $d ?? null;
    $revenuFixe = $revenuFixe ?? null;
    $choix = fn (string $champ, $actuel) => old('_form') === $p ? old($champ) : $actuel;
    $sourceActuelle = $revenuFixe ? 'revenu' : $choix('source', $d?->source ?? 'location');
    $revenuActuel = (int) $choix('autre_revenu_id', $d?->autre_revenu_id);
    $select = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700';
    // Le revenu de la dépense modifiée reste proposé même s'il est épuisé
    $listeRevenus = $revenusDisponibles ?? collect();
    if ($d?->autreRevenu && ! $listeRevenus->contains('id', $d->autre_revenu_id)) {
        $listeRevenus = $listeRevenus->concat([$d->autreRevenu]);
    }
    $resteRevenu = fn ($r) => $r->reste($d?->autre_revenu_id === $r->id ? $d->id : null);
@endphp
<input type="hidden" name="_form" value="{{ $p }}">

{{-- 1. Avec quel argent ? --}}
<p class="text-sm font-bold text-gray-900">Avec quel argent ?</p>
@if ($revenuFixe)
    <input type="hidden" name="source" value="revenu">
    <input type="hidden" name="autre_revenu_id" value="{{ $revenuFixe->id }}">
    <input type="hidden" name="retour" value="revenu">
    <span hidden data-reste-fixe="{{ $resteRevenu($revenuFixe) }}"></span>
    <p class="rounded-lg border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-accent-900">
        Revenu <strong>{{ $revenuFixe->numero }}</strong> de <strong>{{ $revenuFixe->source }}</strong> ·
        reçu {{ \App\Support\Format::usd($revenuFixe->montant_usd) }} $ · reste <strong>{{ \App\Support\Format::usd($resteRevenu($revenuFixe)) }} $</strong>
    </p>
@else
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50">
            <input type="radio" name="source" value="location" class="mt-1 text-primary-600 focus:ring-primary-600" @checked($sourceActuelle === 'location')>
            <span class="text-sm">
                <span class="block font-semibold text-gray-900">Recettes de location</span>
                <span class="text-xs {{ $caisse['solde'] < 0 ? 'text-red-600' : 'text-gray-500' }}">Disponible : {{ \App\Support\Format::usd($caisse['solde']) }} $</span>
            </span>
        </label>
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 has-[:checked]:border-accent-500 has-[:checked]:bg-accent-50 {{ $listeRevenus->isEmpty() ? 'opacity-60' : '' }}">
            <input type="radio" name="source" value="revenu" class="mt-1 text-accent-600 focus:ring-accent-500" @checked($sourceActuelle === 'revenu') @disabled($listeRevenus->isEmpty())>
            <span class="text-sm">
                <span class="block font-semibold text-gray-900">Un autre revenu</span>
                <span class="text-xs text-gray-500">{{ $listeRevenus->isEmpty() ? 'Aucun autre revenu avec un reste' : $listeRevenus->count().' revenu(s) avec un reste' }}</span>
            </span>
        </label>
    </div>
    <div data-source-revenu class="{{ $sourceActuelle === 'revenu' ? '' : 'hidden' }}">
        <label for="{{ $p }}-autre_revenu_id" class="{{ $label }}">Avec quel revenu ? *</label>
        <select id="{{ $p }}-autre_revenu_id" name="autre_revenu_id" class="{{ $select }}">
            <option value="">— Choisir le revenu —</option>
            @foreach ($listeRevenus as $r)
                <option value="{{ $r->id }}" data-reste="{{ $resteRevenu($r) }}" @selected($revenuActuel === $r->id)>
                    {{ $r->numero }} · {{ $r->source }} — reçu {{ \App\Support\Format::usd($r->montant_usd) }} $, reste {{ \App\Support\Format::usd($resteRevenu($r)) }} $
                </option>
            @endforeach
        </select>
    </div>
@endif

{{-- 2. Quoi, à qui, par qui ? --}}
<p class="text-sm font-bold text-gray-900">Quelle dépense ?</p>
<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-field :prefix="$p" label="Description : qu'est-ce qui a été payé ? *" name="description" placeholder="Ex. 4 pneus pour la Prado, vidange, carburant Matadi..." :value="$d?->description" required />
    </div>
    <div>
        <label for="{{ $p }}-categorie_id" class="{{ $label }}">Catégorie *</label>
        <select id="{{ $p }}-categorie_id" name="categorie_id" class="{{ $select }}" required>
            @foreach ($categories as $c)
                @if ($c->active || $c->id === $d?->categorie_id)
                    <option value="{{ $c->id }}" @selected((int) $choix('categorie_id', $d?->categorie_id) === $c->id)>{{ $c->nom }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <x-field :prefix="$p" label="Date *" name="date_depense" type="date" :value="$d?->date_depense->format('Y-m-d') ?? now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
    <x-field :prefix="$p" label="Payé à (bénéficiaire)" name="beneficiaire" placeholder="Garage, station, fournisseur, personne..." :value="$d?->beneficiaire" />
    <div>
        <label for="{{ $p }}-employe_id" class="{{ $label }}">Dépense effectuée par</label>
        <select id="{{ $p }}-employe_id" name="employe_id" class="{{ $select }}">
            <option value="">— Non précisé —</option>
            @foreach ($employes as $e)
                <option value="{{ $e->id }}" @selected((int) $choix('employe_id', $d?->employe_id) === $e->id)>{{ $e->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $p }}-vehicule_id" class="{{ $label }}">Véhicule concerné (facultatif)</label>
        <select id="{{ $p }}-vehicule_id" name="vehicule_id" class="{{ $select }}">
            <option value="">— Aucun —</option>
            @foreach ($vehicules as $v)
                <option value="{{ $v->id }}" @selected((int) $choix('vehicule_id', $d?->vehicule_id) === $v->id)>{{ $v->libelle() }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- 3. Combien ? --}}
<p class="text-sm font-bold text-gray-900">Combien ?</p>
<div class="grid gap-4 sm:grid-cols-4">
    <x-field :prefix="$p" label="Devise" name="devise" :options="\App\Support\Devise::LISTE" :value="$d?->devise ?? 'USD'" />
    <x-field :prefix="$p" label="Montant *" name="montant_saisi" inputmode="decimal" placeholder="0,00" :value="$d ? \App\Support\Format::usd($d->montant_saisi) : ''" required />
    <x-field :prefix="$p" label="Taux (CDF pour 1 $) *" name="taux_change" inputmode="decimal" :value="$d ? rtrim(rtrim(number_format((float) $d->taux_change, 2, ',', ''), '0'), ',') : config('rental.taux_change')" required />
    <x-field :prefix="$p" label="Mode de paiement" name="mode_paiement" :options="\App\Models\Depense::MODES" :value="$d?->mode_paiement" />
</div>
<p class="rounded-lg bg-gray-50 px-4 py-2.5 text-sm text-gray-700" data-conversion>—</p>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $p }}-piece_jointe" class="{{ $label }}">Pièce justificative (facture, reçu)</label>
        <input id="{{ $p }}-piece_jointe" name="piece_jointe" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
        <p class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG, 5 Mo maximum.@if ($d?->piece_jointe) Un fichier est déjà joint.@endif</p>
    </div>
    <x-field :prefix="$p" label="Observation" name="observation" type="textarea" placeholder="Facultatif" :value="$d?->observation" />
</div>
