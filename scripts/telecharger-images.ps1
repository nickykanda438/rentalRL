# Télécharge les photos de véhicules dans public/images (fonctionnement hors ligne)
# Utilisation : depuis C:\wamp64\www\rental  ->  powershell -ExecutionPolicy Bypass -File scripts\telecharger-images.ps1
$ErrorActionPreference = 'Stop'
$dest = Join-Path $PSScriptRoot '..\public\images'
New-Item -ItemType Directory -Force -Path $dest | Out-Null

$photos = @{
    'suv-nuit.jpg'            = '1629377856766-72c3bb4c86c6'
    'land-cruiser-desert.jpg' = '1565081188891-72179dbf09c7'
    'land-cruiser-plage.jpg'  = '1709620435533-56483034bdd4'
    'toyota-corolla.jpg'      = '1638618164682-12b986ec2a75'
    'suv-noir.jpg'            = '1613520747840-26d4e09b7029'
    'land-cruiser-70.jpg'     = '1770096145024-eefc88311851'
}

foreach ($p in $photos.GetEnumerator()) {
    $url = "https://images.unsplash.com/photo-$($p.Value)?auto=format&fit=crop&w=1600&q=75&fm=jpg"
    $out = Join-Path $dest $p.Key
    Write-Host "Téléchargement de $($p.Key)..."
    Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing
}
Write-Host "Terminé : $($photos.Count) photos dans public\images" -ForegroundColor Green
