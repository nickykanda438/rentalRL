{{-- Messages après une action : succès, erreur et erreurs de validation --}}
@if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 print:hidden" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 print:hidden" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden" role="alert">
        <p class="font-semibold">Le formulaire contient des erreurs :</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $erreur)<li>{{ $erreur }}</li>@endforeach
        </ul>
    </div>
@endif
