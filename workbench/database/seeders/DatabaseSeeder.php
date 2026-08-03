<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserFactory::new()->times(10)->create();

        UserFactory::new()->create([
            'name' => 'Testbench Cognito User',
            'email' => 'demo_cognito@vomoto.com',
            'sub' => 'ffe9c457-a220-49b2-9b2a-b7e10e8dde45',
            'register_type' => 'invite',
            'registered_at' => now(),
        ]);
    }
}
