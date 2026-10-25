<x-layouts.app title="Employés">
    <x-page-header title="Employés" subtitle="Fiches du personnel ; un changement de salaire est reporté sur la paie">
        <x-button variant="light" icon="settings" :href="route('parametres.index')">Gérer les rôles</x-button>
        <x-button id="btn-nouvel-employe" icon="plus" data-modal-target="modal-employe" data-modal-toggle="modal-employe">Nouvel employé</x-button>
    </x-page-header>

    <x-flash />

    {{-- Recherche et filtres (GET) --}}
    <form method="GET" action="{{ route('employes.index') }}" class="flex flex-wrap gap-3">
        <div class="relative min-w-60 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher un employé..." class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
        </div>
        <label for="filtre-role" class="sr-only">Rôle</label>
        <select id="filtre-role" name="role" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
            <option value="">Tous les rôles</option>
            @foreach (\App\Models\Employe::ROLES as $r)<option @selected(request('role') === $r)>{{ $r }}</option>@endforeach
        </select>
        <label for="filtre-statut" class="sr-only">Statut</label>
        <select id="filtre-statut" name="statut" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Employe::STATUTS as $s)<option @selected(request('statut') === $s)>{{ $s }}</option>@endforeach
        </select>
    </form>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">Nom &amp; prénom</th><th class="px-5 py-3">Poste</th><th class="px-5 py-3">Rôle</th><th class="px-5 py-3">Téléphone</th><th class="px-5 py-3 text-right">Salaire (USD)</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($employes as $employe)
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3"><a href="{{ route('employes.show', $employe) }}" class="hover:underline"><x-avatar :name="$employe->nom" :tone="$loop->index" /></a></td>
                    <td class="px-5 py-3">{{ $employe->poste }}</td>
                    <td class="px-5 py-3">{{ $employe->role }}</td>
                    <td class="px-5 py-3 tabular-nums">{{ $employe->telephone ?? '—' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $employe->salaireFormate() }}</td>
                    <td class="px-5 py-3"><x-badge :color="$employe->couleurStatut()">{{ $employe->statut }}</x-badge></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                        @if ($employe->statut === 'Actif')
                            <a href="{{ route('employes.show', [$employe, 'ouvrir' => 'salaire']) }}" class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100" title="Payer le salaire ou donner une avance"><x-icon name="money" class="h-3.5 w-3.5" />Payer</a>
                        @endif
                        <x-row-actions :show="route('employes.show', $employe)" :modal="'modal-employe-'.$employe->id" :delete="route('employes.destroy', $employe)" :confirm="'Supprimer '.$employe->nom.' ?'" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">
                    @if (request()->hasAny(['q', 'role', 'statut']))
                        Aucun employé ne correspond à la recherche.
                    @else
                        Aucun employé enregistré. Cliquez sur « Nouvel employé » pour commencer.
                    @endif
                </td></tr>
            @endforelse
        </tbody>
        @if ($employes->total() > 0)
            <x-slot:footer>
                <span>Affichage de {{ $employes->firstItem() }} à {{ $employes->lastItem() }} sur {{ $employes->total() }} employé(s)</span>
                @if ($employes->hasPages())
                    <nav aria-label="Pagination">
                        <ul class="inline-flex -space-x-px text-sm">
                            <li><a href="{{ $employes->previousPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $employes->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}">Précédent</a></li>
                            @foreach ($employes->getUrlRange(1, $employes->lastPage()) as $page => $url)
                                <li><a href="{{ $url }}" @if ($page === $employes->currentPage()) aria-current="page" @endif class="flex h-8 items-center border px-3 {{ $page === $employes->currentPage() ? 'border-primary-600 bg-primary-600 font-semibold text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50' }}">{{ $page }}</a></li>
                            @endforeach
                            <li><a href="{{ $employes->nextPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $employes->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}">Suivant</a></li>
                        </ul>
                    </nav>
                @endif
            </x-slot:footer>
        @endif
    </x-table>

    <x-slot:modals>
        {{-- Ajout --}}
        <x-modal id="modal-employe" title="Nouvel employé" size="max-w-3xl" :action="route('employes.store')">
            @include('employes._form', ['e' => null, 'p' => 'nouveau'])
        </x-modal>

        {{-- Modification : une fenêtre par employé de la page --}}
        @foreach ($employes as $employe)
            <x-modal :id="'modal-employe-'.$employe->id" :title="'Modifier : '.$employe->nom" size="max-w-3xl" :action="route('employes.update', $employe)" method="PUT" submit="Mettre à jour">
                @include('employes._form', ['e' => $employe, 'p' => 'emp'.$employe->id])
            </x-modal>
        @endforeach

        {{-- Rouvre la bonne fenêtre si la validation a échoué --}}
        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouveau' ? 'modal-employe' : 'modal-employe-' + form.replace('emp', '');
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>
