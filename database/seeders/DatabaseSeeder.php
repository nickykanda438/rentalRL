<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Compte administrateur de démonstration (à changer en production)
        User::updateOrCreate(
            ['email' => 'admin@rental.cd'],
            ['name' => 'Administrateur', 'password' => 'rental2026'],
        );
    }
}
