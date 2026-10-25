{{-- Suggestions de clients déjà enregistrés (le téléphone et l'adresse se remplissent seuls, voir rental.js) --}}
<datalist id="liste-clients">
    @foreach ($clients as $client)
        <option value="{{ $client->nom }}" data-telephone="{{ $client->telephone }}" data-adresse="{{ $client->adresse }}">{{ $client->telephone }}</option>
    @endforeach
</datalist>
