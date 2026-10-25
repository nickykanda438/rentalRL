@props(['variant' => 'dark', 'mark' => false])
@php
    // Fichiers dans public/images : logo (horizontal, fond clair), logo-blanc (fond sombre),
    // logo-complet (avec slogan, pour les documents) et logo-mark (icône seule).
    // Un fichier .png déposé à côté remplace automatiquement le .svg provisoire.
    $base = $mark ? 'logo-mark' : match ($variant) { 'light' => 'logo-blanc', 'complet' => 'logo-complet', default => 'logo' };
    $file = file_exists(public_path("images/{$base}.png")) ? "{$base}.png" : "{$base}.svg";
@endphp
<img src="{{ asset('images/'.$file) }}" alt="Rental" {{ $attributes->merge(['class' => $mark ? 'h-10 w-10 object-contain' : 'h-9 w-auto']) }}>
