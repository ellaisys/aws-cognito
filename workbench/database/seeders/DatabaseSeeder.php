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
        UserFactory::new()->create([
            'name' => 'Testbench Cognito Registered User',
            'email' => 'ellaisys+tb_register_0408V01@gmail.com',
            'sub' => '1183ed8a-70e1-709e-4151-eaaafa85b05f',
            'register_type' => 'register',
            'registered_at' => now(),
        ]);

        UserFactory::new()->create([
            'name' => 'Testbench Cognito Invited User',
            'email' => 'ellaisys+tb_invite_0408V02@gmail.com',
            'sub' => 'a1032d6a-80c1-70dd-876b-9265424509d0',
            'register_type' => 'invite',
            'registered_at' => now(),
        ]);
    }
}
