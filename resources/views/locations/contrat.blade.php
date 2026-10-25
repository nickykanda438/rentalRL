@use('App\Support\Format')
@php $l = $location; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrat {{ $l->numero_contrat }} · Rental</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/app.css'])
    <style>@page { size: A4; margin: 14mm; }</style>
</head>
<body class="bg-gray-100 font-sans text-gray-900 antialiased print:bg-white">
    <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-6 py-3 print:hidden">
        <a href="{{ route('locations.show', $l) }}" class="text-sm font-semibold text-primary-700 hover:underline">← Retour à la location</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Imprimer / PDF</button>
    </div>

    <article class="mx-auto my-6 max-w-3xl rounded-xl bg-white p-10 shadow-sm print:my-0 print:max-w-none print:rounded-none print:p-0 print:shadow-none">
        <header class="flex items-start justify-between border-b-2 border-primary-600 pb-5">
            <x-logo variant="complet" class="h-28 w-auto" />
            <div class="text-right">
                <h1 class="text-xl font-bold uppercase tracking-wide">Contrat de location</h1>
                <p class="mt-1 font-mono text-sm font-semibold">{{ $l->numero_contrat }}</p>
                <p class="text-xs text-gray-500">Établi le {{ $l->created_at->format('d/m/Y') }}</p>
                @if ($l->statut !== 'Actif')<p class="mt-1 text-xs font-semibold uppercase text-gray-600">{{ $l->statut }}</p>@endif
            </div>
        </header>

        <section class="mt-6 grid grid-cols-2 gap-8 text-sm">
            <div>
                <h2 class="mb-1 text-xs font-semibold uppercase text-gray-500">Client</h2>
                <p class="font-semibold">{{ $l->client->nom }}</p>
                <p>{{ $l->client->telephone }}</p>
                <p>{{ $l->client->adresse }}</p>
            </div>
            <div>
                <h2 class="mb-1 text-xs font-semibold uppercase text-gray-500">Véhicule et chauffeur</h2>
                <p class="font-semibold">{{ $l->vehicule ? $l->vehicule->marque.' '.$l->vehicule->modele : 'Non assigné' }}</p>
                @if ($l->vehicule)<p>Immatriculation : {{ $l->vehicule->plaque }}</p>@endif
                <p>Chauffeur : {{ $l->chauffeur?->nom ?? '—' }}</p>
            </div>
        </section>

        <table class="mt-6 w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr><td class="py-2 text-gray-500">Destination</td><td class="py-2 text-right font-medium">{{ $l->destination ?? '—' }}</td></tr>
                <tr><td class="py-2 text-gray-500">Départ</td><td class="py-2 text-right font-medium">{{ ucfirst($l->date_depart->locale('fr')->translatedFormat('l d F Y')) }}</td></tr>
                <tr><td class="py-2 text-gray-500">Retour prévu</td><td class="py-2 text-right font-medium">{{ ucfirst($l->date_fin->locale('fr')->translatedFormat('l d F Y')) }}</td></tr>
                <tr><td class="py-2 text-gray-500">Durée</td><td class="py-2 text-right font-medium">{{ $l->nombre_jours }} jour(s)</td></tr>
                <tr><td class="py-2 text-gray-500">Tarif journalier</td><td class="py-2 text-right tabular-nums">{{ Format::usd($l->montant_journalier) }} $</td></tr>
                <tr class="font-semibold"><td class="py-2">Montant de la location</td><td class="py-2 text-right tabular-nums">{{ Format::usd($l->sous_total) }} $</td></tr>
                <tr class="bg-primary-50 font-bold text-primary-800"><td class="px-3 py-3">Équivalent en francs congolais</td><td class="px-3 py-3 text-right tabular-nums">{{ Format::cdf($l->sous_total * $l->taux_change) }} FC</td></tr>
            </tbody>
        </table>
        <p class="mt-2 text-xs text-gray-500">Taux appliqué : 1 $ = {{ Format::cdf($l->taux_change) }} FC.@if ($l->date_perception) Montant perçu le {{ $l->date_perception->format('d/m/Y') }}.@endif</p>

        <section class="mt-6 text-xs leading-relaxed text-gray-600">
            <h2 class="mb-1 font-semibold uppercase text-gray-500">Conditions</h2>
            <p>Le véhicule est loué avec chauffeur pour la destination et la période indiquées. Tout dépassement de la date de retour prévue est facturé au tarif journalier. Le client s'engage à respecter le véhicule et à signaler tout incident sans délai.</p>
        </section>

        <footer class="mt-16 grid grid-cols-2 gap-8 text-center text-sm">
            <div><p class="text-gray-500">Le client</p><div class="mt-14 border-t border-gray-400"></div></div>
            <div><p class="text-gray-500">Pour Rental</p><div class="mt-14 border-t border-gray-400"></div></div>
        </footer>
    </article>
</body>
</html>
