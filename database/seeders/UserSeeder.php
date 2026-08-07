<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * The admin password comes from the environment. In a non-local
         * environment with no ADMIN_PASSWORD set, a random one is generated and
         * printed once — seeding must never silently create a known-credential
         * account on a reachable server.
         */
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            $password = app()->environment('local') ? 'password' : Str::password(20);

            if (! app()->environment('local')) {
                $this->command?->warn("Generated admin password: {$password}");
            }
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@artaleca.com')],
            [
                'name' => 'Site Administrator',
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'locale' => 'fa',
                'email_verified_at' => now(),
            ],
        );
    }
}
