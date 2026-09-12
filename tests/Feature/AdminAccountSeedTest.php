<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSeedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The failure this guards against left no trace at all.
     *
     * `php artisan optimize` caches the config, and a cached config makes
     * Laravel skip loading `.env` entirely. `env('ADMIN_PASSWORD')` from inside
     * the seeder then returned null on every deployed server, the seeder took
     * its "no password configured" branch, and re-seeding replaced a working
     * password with a random one — reporting success while locking the
     * operator out of the panel.
     *
     * Reading through config is the fix, because config files are evaluated
     * before the cache is written.
     */
    public function test_the_seeder_reads_no_environment_variable_directly(): void
    {
        // Comments stripped, because this file explains the trap in prose and
        // the explanation would match the pattern it is warning about.
        $source = php_strip_whitespace(database_path('seeders/UserSeeder.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/(?<![a-zA-Z_])env\s*\(/',
            $source,
            'env() inside a seeder returns null once the config is cached, which is '
            .'the state every deployed server is in.',
        );
    }

    public function test_the_configured_password_is_the_one_that_is_set(): void
    {
        config(['site.admin.password' => 'a-chosen-password', 'site.admin.email' => 'admin@artaleca.com']);

        $this->seed(UserSeeder::class);

        $user = User::where('email', 'admin@artaleca.com')->firstOrFail();

        $this->assertTrue(Hash::check('a-chosen-password', $user->password));
        $this->assertSame(User::ROLE_ADMIN, $user->role);
    }

    /**
     * Re-running the seeder is the documented way to reset a forgotten
     * password, so it has to be an update rather than a second account — and
     * it must not quietly reset the password when one is configured.
     */
    public function test_reseeding_updates_the_same_account(): void
    {
        config(['site.admin.password' => 'first-password', 'site.admin.email' => 'admin@artaleca.com']);
        $this->seed(UserSeeder::class);

        config(['site.admin.password' => 'second-password']);
        $this->seed(UserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@artaleca.com')->count());
        $this->assertTrue(Hash::check(
            'second-password',
            User::where('email', 'admin@artaleca.com')->value('password'),
        ));
    }

    /**
     * With nothing configured on a reachable server the account still gets a
     * password, but a random one — never a known default.
     */
    public function test_an_unconfigured_password_is_random_outside_local(): void
    {
        config(['site.admin.password' => null, 'site.admin.email' => 'admin@artaleca.com']);
        $this->app['env'] = 'production';

        // Run directly rather than through $this->seed(): db:seed is
        // confirmable, and in "production" it stops to ask.
        (new UserSeeder)->run();

        $hash = User::where('email', 'admin@artaleca.com')->value('password');

        foreach (['password', 'secret', 'admin', '123456'] as $guess) {
            $this->assertFalse(Hash::check($guess, $hash), "Seeded with the known password \"{$guess}\".");
        }
    }
}
