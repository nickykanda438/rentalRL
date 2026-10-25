@props(['title' => 'Tableau de bord'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Rental</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream font-sans text-gray-900 antialiased">
@php
    $menu = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home'],
        ['route' => 'locations.index', 'match' => 'locations.*', 'label' => 'Budget location', 'icon' => 'calc'],
        ['route' => 'revenus.index', 'match' => 'revenus.*', 'label' => 'Autres revenus', 'icon' => 'trend'],
        ['route' => 'depenses.index', 'match' => 'depenses.*', 'label' => 'Dépenses', 'icon' => 'receipt'],
        ['route' => 'besoins.index', 'match' => 'besoins.*', 'label' => 'Besoins', 'icon' => 'package'],
        ['route' => 'vehicules.index', 'match' => 'vehicules.*', 'label' => 'Flotte', 'icon' => 'car'],
        ['route' => 'employes.index', 'match' => 'employes.*', 'label' => 'Employés', 'icon' => 'users'],
        ['route' => 'paie.index', 'match' => 'paie.*', 'label' => 'Paie', 'icon' => 'money'],
        ['route' => 'rapports.index', 'match' => 'rapports.*', 'label' => 'Rapports', 'icon' => 'file'],
    ];
    $user = auth()->user();
@endphp

{{-- Barre latérale (tiroir Flowbite sur mobile) --}}
<aside id="sidebar" class="fixed left-0 top-0 z-40 flex h-screen w-64 -translate-x-full flex-col bg-gradient-to-b from-primary-800 to-primary-900 px-3.5 py-5 text-white transition-transform lg:translate-x-0" aria-label="Navigation principale">
    <a href="{{ route('dashboard') }}" class="mb-7 px-2"><x-logo variant="light" class="h-9" /></a>
    <nav class="flex flex-1 flex-col gap-0.5">
        @foreach ($menu as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-primary-500 text-white shadow-lg shadow-primary-950/30' : 'text-primary-100 hover:bg-white/10 hover:text-white' }}">
                <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />{{ $item['label'] }}
            </a>
        @endforeach
    </nav>
    <div class="mt-4 border-t border-white/10 pt-3">
        <a href="{{ route('parametres.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('parametres.*') ? 'bg-primary-500 text-white' : 'text-primary-100 hover:bg-white/10 hover:text-white' }}">
            <x-icon name="settings" class="h-[18px] w-[18px]" />Paramètres
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-primary-100 hover:bg-white/10 hover:text-white">
                <x-icon name="logout" class="h-[18px] w-[18px]" />Déconnexion
            </button>
        </form>
    </div>
</aside>

<div class="lg:pl-64">
    {{-- En-tête --}}
    <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
        <button type="button" data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 lg:hidden">
            <x-icon name="menu" /><span class="sr-only">Ouvrir le menu</span>
        </button>
        <div class="relative hidden max-w-md flex-1 sm:block">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche-globale" class="sr-only">Rechercher</label>
            <input id="recherche-globale" type="search" placeholder="Rechercher une fonctionnalité..." class="block w-full rounded-lg border-0 bg-gray-50 py-2.5 pl-9 text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600">
        </div>
        <div class="ml-auto flex items-center gap-3">
            <button type="button" data-dropdown-toggle="notifications" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                <x-icon name="bell" /><span class="sr-only">Notifications</span>
                <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-white bg-red-600 px-1 text-[10px] font-bold text-white">3</span>
            </button>
            <div id="notifications" class="z-50 hidden w-80 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white shadow-lg">
                <p class="px-4 py-3 text-sm font-bold">Notifications</p>
                <a href="{{ route('locations.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-50"><span class="font-semibold text-red-600">Retard</span> · Coaster CG 2244 AG attendu le 16/10</a>
                <a href="{{ route('paie.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-50"><span class="font-semibold text-accent-700">Paie</span> · 2 avances à approuver</a>
                <a href="{{ route('besoins.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-50"><span class="font-semibold text-primary-700">Besoins</span> · 650,00 $ en attente</a>
            </div>
            <button type="button" data-dropdown-toggle="menu-utilisateur" class="flex items-center gap-2.5 rounded-lg p-1 pr-2 hover:bg-gray-50">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-700">{{ mb_strtoupper(mb_substr($user?->name ?? 'A', 0, 2)) }}</span>
                <span class="hidden text-left sm:block"><span class="block text-sm font-semibold leading-tight">{{ $user?->name ?? 'Admin' }}</span><span class="block text-xs text-gray-500">Administrateur</span></span>
                <x-icon name="chevd" class="h-4 w-4 text-gray-500" />
            </button>
            <div id="menu-utilisateur" class="z-50 hidden w-52 rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                <p class="truncate px-4 py-2 text-xs text-gray-500">{{ $user?->email }}</p>
                <a href="{{ route('parametres.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">Paramètres</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Déconnexion</button></form>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-7">
        {{ $slot }}
    </main>
</div>

{{ $modals ?? '' }}
</body>
</html>
