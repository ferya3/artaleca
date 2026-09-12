<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@artaleca.com'): User
    {
        return User::create([
            'name' => 'Site Administrator',
            'email' => $email,
            'password' => 'the-old-password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_it_sets_the_password(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--password' => 'a-new-password'])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }

    /**
     * The reason this command exists rather than a line in `.env`: dotenv
     * treats `#` as the start of a comment in an unquoted value, so a password
     * written there arrives at the application already truncated. Nothing
     * parses it on this route.
     */
    public function test_awkward_characters_survive(): void
    {
        $user = $this->admin();

        foreach (['pass#word-1', 'two words here', 'dollar$sign$1', "quote\"and'quote"] as $password) {
            $this->artisan('admin:password', ['--password' => $password])->assertSuccessful();

            $this->assertTrue(
                Hash::check($password, $user->fresh()->password),
                "The password {$password} did not survive being set.",
            );
        }
    }

    /** A locked-out administrator is the usual reason to reach for this. */
    public function test_it_reactivates_the_account(): void
    {
        $user = $this->admin();
        $user->forceFill(['is_active' => false])->save();

        $this->artisan('admin:password', ['--password' => 'a-new-password'])->assertSuccessful();

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_it_refuses_a_short_password_without_changing_anything(): void
    {
        $user = $this->admin();

        $this->artisan('admin:password', ['--password' => 'short'])->assertFailed();

        $this->assertTrue(Hash::check('the-old-password', $user->fresh()->password));
    }

    public function test_it_names_the_accounts_that_exist_when_the_address_is_wrong(): void
    {
        $this->admin('someone@artaleca.com');

        $this->artisan('admin:password', ['--email' => 'nobody@artaleca.com', '--password' => 'a-new-password'])
            ->expectsOutputToContain('someone@artaleca.com')
            ->assertFailed();
    }
}
