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
            'name' => 'Testbench Cognito Registered User',
            'email' => 'ellaisys+tb_register_0408V01@gmail.com',
            'sub' => 'ffe9c457-a220-49b2-9b2a-b7e10e8dde45',
            'register_type' => 'register',
            'registered_at' => now(),
        ]);

        UserFactory::new()->create([
            'name' => 'Testbench Cognito Invited User',
            'email' => 'ellaisys+tb_invite_0408V02@gmail.com',
            'sub' => 'ffe9c457-a220-49b2-9b2a-b7e10e8dde46',
            'register_type' => 'invite',
            'registered_at' => now(),
        ]);
    }
}
