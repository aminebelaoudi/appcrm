<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Créer l'utilisateur principal avec l'id_location du .env
        User::updateOrCreate(
            ['id_location' => env('GHL_LOCATION_ID')],
            [
                'name' => 'Client Principal',
            ]
        );

        echo "✅ Utilisateur créé avec id_location:  " . "\n";
    }
}
