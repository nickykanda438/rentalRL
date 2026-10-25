@use('App\Support\Format')
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bulletins->count() > 1 ? 'Bulletins de paie · '.Format::mois($bulletins->first()->mois) : 'Bulletin · '.$bulletins->first()->employe->nom }} · Rental</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print { .bulletin { break-after: page; } .bulletin:last-child { break-after: auto; } }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900 antialiased print:bg-white">
    <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-6 py-3 print:hidden">
        <p class="text-sm text-gray-600">{{ $bulletins->count() }} bulletin(s) prêt(s) à imprimer ou à enregistrer en PDF.</p>
        <button type="button" onclick="window.print()" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Imprimer / PDF</button>
    </div>

    @foreach ($bulletins as $b)
        @php $e = $b->employe; @endphp
        <article class="bulletin mx-auto my-6 max-w-3xl rounded-xl bg-white p-10 shadow-sm print:my-0 print:max-w-none print:rounded-none print:p-0 print:shadow-none">
            <header class="flex items-start justify-between border-b-2 border-primary-600 pb-5">
                <x-logo variant="complet" class="h-28 w-auto" />
                <div class="text-right">
                    <h1 class="text-xl font-bold uppercase tracking-wide">Bulletin de paie</h1>
                    <p class="text-sm text-gray-600">{{ Format::mois($b->mois) }}</p>
                    <p class="mt-1 font-mono text-sm font-semibold">Réf. {{ $b->numero }}</p>
                </div>
            </header>

            <section class="mt-6 grid grid-cols-2 gap-x-8 gap-y-1.5 text-sm">
                <p><span class="text-gray-500">Employé :</span> <strong>{{ $e->nom }}</strong></p>
                <p><span class="text-gray-500">Poste :</span> {{ $e->poste }}</p>
                <p><span class="text-gray-500">Matricule :</span> {{ str_pad($e->id, 4, '0', STR_PAD_LEFT) }}</p>
                <p><span class="text-gray-500">Rôle :</span> {{ $e->role }}</p>
                <p><span class="text-gray-500">Date d'embauche :</span> {{ $e->date_embauche?->format('d/m/Y') ?? '—' }}</p>
                <p><span class="text-gray-500">Lieu de travail :</span> {{ $e->lieu_travail ?? '—' }}</p>
            </section>

            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="border-y border-gray-300 bg-gray-50 text-left text-xs font-semibold uppercase text-gray-600">
                        <th class="px-3 py-2">Libellé</th><th class="px-3 py-2 text-right">Gains (USD)</th><th class="px-3 py-2 text-right">Retenues (USD)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="px-3 py-2">Salaire de base</td><td class="px-3 py-2 text-right tabular-nums">{{ Format::usd($b->salaire_base) }}</td><td></td></tr>
                    @if ((float) $b->primes > 0)
                        <tr><td class="px-3 py-2">Primes</td><td class="px-3 py-2 text-right tabular-nums">{{ Format::usd($b->primes) }}</td><td></td></tr>
                    @endif
                    @if ((float) $b->total_avances > 0)
                        <tr><td class="px-3 py-2">Avances sur salaire du mois</td><td></td><td class="px-3 py-2 text-right tabular-nums">{{ Format::usd($b->total_avances) }}</td></tr>
                    @endif
                    @foreach ($b->remboursements as $r)
                        <tr><td class="px-3 py-2">Remboursement crédit du {{ $r->credit->date_octroi->format('d/m/Y') }}</td><td></td><td class="px-3 py-2 text-right tabular-nums">{{ Format::usd($r->montant) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300 font-semibold">
                        <td class="px-3 py-2">Totaux</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ Format::usd((float) $b->salaire_base + (float) $b->primes) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ Format::usd((float) $b->total_avances + (float) $b->total_credits) }}</td>
                    </tr>
                    <tr class="bg-primary-50 text-base font-bold text-primary-800">
                        <td class="px-3 py-3" colspan="2">Net à payer</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ Format::usd($b->net_a_payer) }} $</td>
                    </tr>
                </tfoot>
            </table>

            <p class="mt-4 text-sm text-gray-600">
                @if ($b->estPaye())
                    Payé le {{ $b->date_paiement->format('d/m/Y') }} par {{ $b->mode_paiement }}@if ($b->reference) (transaction n° {{ $b->reference }})@endif.
                @else
                    <strong class="text-accent-700">Non encore payé.</strong>
                @endif
            </p>

            <footer class="mt-16 grid grid-cols-2 gap-8 text-center text-sm">
                <div><p class="text-gray-500">Signature de l'employé</p><div class="mt-14 border-t border-gray-400"></div></div>
                <div><p class="text-gray-500">Pour la direction</p><div class="mt-14 border-t border-gray-400"></div></div>
            </footer>
        </article>
    @endforeach
</body>
</html>
