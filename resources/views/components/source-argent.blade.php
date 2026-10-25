{{-- « Avec quel argent ? » : recettes de location ou un autre revenu précis.
     $p : préfixe du formulaire ; $caisse : Caisse::locations() ; $revenus : autres revenus avec un reste ;
     $montant : montant fixe à payer (sinon le champ « montant » du formulaire est utilisé pour l'alerte). --}}
@props(['p', 'caisse', 'revenus', 'montant' => null])
@php
    $source = old('_form') === $p ? old('source', 'location') : 'location';
    $revenuChoisi = old('_form') === $p ? (int) old('autre_revenu_id') : 0;
@endphp
<div class="space-y-3 rounded-xl border border-gray-200 p-4" data-source-argent @if ($montant !== null) data-montant="{{ $montant }}" @endif>
    <p class="text-sm font-bold text-gray-900">Avec quel argent ?</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50">
            <input type="radio" name="source" value="location" class="mt-1 text-primary-600 focus:ring-primary-600" @checked($source === 'location')>
            <span class="text-sm">
                <span class="block font-semibold text-gray-900">Recettes de location</span>
                <span class="text-xs {{ $caisse['solde'] < 0 ? 'text-red-600' : 'text-gray-500' }}">Disponible : {{ \App\Support\Format::usd($caisse['solde']) }} $</span>
            </span>
        </label>
        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 has-[:checked]:border-accent-500 has-[:checked]:bg-accent-50 {{ $revenus->isEmpty() ? 'opacity-60' : '' }}">
            <input type="radio" name="source" value="revenu" class="mt-1 text-accent-600 focus:ring-accent-500" @checked($source === 'revenu') @disabled($revenus->isEmpty())>
            <span class="text-sm">
                <span class="block font-semibold text-gray-900">Un autre revenu</span>
                <span class="text-xs text-gray-500">{{ $revenus->isEmpty() ? 'Aucun autre revenu avec un reste' : $revenus->count().' revenu(s) disponible(s)' }}</span>
            </span>
        </label>
    </div>
    <div data-source-choix-revenu class="{{ $source === 'revenu' ? '' : 'hidden' }}">
        <label for="{{ $p }}-autre_revenu_id" class="mb-1.5 block text-sm font-medium text-gray-700">Avec quel revenu ? *</label>
        <select id="{{ $p }}-autre_revenu_id" name="autre_revenu_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600">
            <option value="">— Choisir le revenu —</option>
            @foreach ($revenus as $r)
                <option value="{{ $r->id }}" data-reste="{{ $r->reste() }}" @selected($revenuChoisi === $r->id)>{{ $r->numero }} · {{ $r->source }} — reste {{ \App\Support\Format::usd($r->reste()) }} $</option>
            @endforeach
        </select>
    </div>
    <p class="hidden rounded-lg px-3 py-2 text-xs" data-source-alerte></p>
</div>
