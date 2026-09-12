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
         * The admin password comes from the environment, by way of config —
         * never `env()` directly. Once `config:cache` has run, and `optimize`
         * runs it on every deploy, Laravel stops loading `.env` at all and an
         * `env()` call outside a config file returns null without complaint.
         * Re-seeding on a deployed server therefore took the branch below and
         * set a random password over a working one.
         *
         * In a non-local environment with no password configured, a random one
         * is still generated and printed — seeding must never silently create
         * a known-credential account on a reachable server.
         */
        $password = config('site.admin.password');

        if (blank($password)) {
            $password = app()->environment('local') ? 'password' : Str::password(20);

            if (! app()->environment('local')) {
                $this->command?->warn("Generated admin password: {$password}");
                $this->command?->warn('Nothing else records it. Set ADMIN_PASSWORD in .env to choose your own.');
            }
        }

        User::updateOrCreate(
            ['email' => config('site.admin.email')],
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
