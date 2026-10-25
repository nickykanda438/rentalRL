<?php

/*
| Photos des véhicules (licence Unsplash : utilisation libre, crédit apprécié).
| Si le fichier existe dans public/images, il est utilisé ; sinon la photo est
| chargée depuis Unsplash. Lancez scripts/telecharger-images.ps1 pour les copier
| localement (fonctionnement hors ligne).
*/

return [
    // Valeurs proposées par défaut dans le formulaire de location (modifiables à chaque saisie)
    'taux_change' => (float) env('RENTAL_TAUX_CHANGE', 2650),   // CDF pour 1 USD
    'commission' => (float) env('RENTAL_COMMISSION', 10),       // en %

    // Alerte flotte (RG-06) quand le % de véhicules disponibles passe sous ce seuil
    'seuil_disponibilite' => (float) env('RENTAL_SEUIL_DISPONIBILITE', 30),

    'photos' => [
        'hero' => ['file' => 'suv-nuit.jpg', 'id' => '1629377856766-72c3bb4c86c6', 'credit' => 'Stefan Briggs'],
        'land-cruiser' => ['file' => 'land-cruiser-desert.jpg', 'id' => '1565081188891-72179dbf09c7', 'credit' => 'Sarvaswa Tandon'],
        'land-cruiser-plage' => ['file' => 'land-cruiser-plage.jpg', 'id' => '1709620435533-56483034bdd4', 'credit' => 'Suryaprakash V'],
        'corolla' => ['file' => 'toyota-corolla.jpg', 'id' => '1638618164682-12b986ec2a75', 'credit' => 'Bestami Sarıkaya'],
        'prado' => ['file' => 'suv-noir.jpg', 'id' => '1613520747840-26d4e09b7029', 'credit' => 'Alex Simpson'],
        'land-cruiser-70' => ['file' => 'land-cruiser-70.jpg', 'id' => '1770096145024-eefc88311851', 'credit' => '4Wheelhouse'],
    ],
];
