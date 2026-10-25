<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · Rental</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-gray-900 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Visuel : vraie photo + voile vert --}}
    <section class="relative hidden overflow-hidden lg:block">
        <x-photo name="hero" alt="SUV noir sur une route de nuit" :width="1600" class="absolute inset-0 h-full w-full" />
        <div class="absolute inset-0 bg-gradient-to-b from-primary-900/90 via-primary-900/55 to-primary-950/95"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div>
                <x-logo variant="light" class="h-11" />
                <p class="mt-8 max-w-md text-lg leading-relaxed text-primary-50">Gestion intelligente de votre entreprise de location de véhicules avec chauffeur.</p>
            </div>
            <ul class="grid max-w-md grid-cols-2 gap-4 text-sm text-primary-50">
                <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10"><x-icon name="receipt" class="h-[18px] w-[18px]" /></span>Recettes &amp; dépenses</li>
                <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10"><x-icon name="car" class="h-[18px] w-[18px]" /></span>Flotte de véhicules</li>
                <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10"><x-icon name="users" class="h-[18px] w-[18px]" /></span>Personnel &amp; paie</li>
                <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10"><x-icon name="chart" class="h-[18px] w-[18px]" /></span>Tableau de bord</li>
            </ul>
        </div>
    </section>

    {{-- Formulaire --}}
    <section class="flex items-center justify-center px-6 py-12">
        <form method="POST" action="{{ route('login.store') }}" class="w-full max-w-sm space-y-5">
            @csrf
            <x-logo class="h-10 lg:hidden" />
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Connexion</h1>
                <p class="mt-1.5 text-sm text-gray-500">Accédez à votre espace</p>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
            @endif

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Identifiant ou email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="ex. admin@rental.cd"
                       class="block w-full rounded-lg border border-gray-300 px-3.5 py-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Mot de passe</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Votre mot de passe"
                           class="block w-full rounded-lg border border-gray-300 px-3.5 py-3 pr-11 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
                    <button type="button" data-toggle-password="password" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-900">
                        <x-icon name="eye" class="h-[18px] w-[18px]" /><span class="sr-only">Afficher le mot de passe</span>
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded-sm border-gray-300 text-primary-600 focus:ring-primary-600"> Se souvenir de moi</label>
                <a href="#" class="text-sm font-medium text-primary-700 hover:underline">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-3 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-200">Se connecter</button>

            @if (app()->isLocal())
                <p class="rounded-lg bg-accent-50 px-3 py-2 text-xs text-accent-700">Démo : <b>admin@rental.cd</b> / <b>rental2026</b> (après <code>php artisan db:seed</code>)</p>
            @endif
            <p class="pt-4 text-center text-xs text-gray-400">Rental © {{ date('Y') }}</p>
        </form>
    </section>
</div>
</body>
</html>
