<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Set the panel password for an administrator.
 *
 *     php artisan admin:password
 *
 * There was already a way to do this — put the password in `.env` and re-run
 * UserSeeder — and it went wrong twice in a row on a live server. Once because
 * `config:cache` makes Laravel skip `.env` entirely, so the seeder generated a
 * random password instead and reported success; and once because a password
 * containing `#` or a space means one thing in a file dotenv parses and
 * another to whoever typed it.
 *
 * This route has neither problem: the password is read from the terminal,
 * hashed, written to the one row it belongs to, and verified before the
 * command returns. Nothing is stored in plain text anywhere, and there is no
 * parser between the operator and the hash.
 */
class AdminPassword extends Command
{
    protected $signature = 'admin:password
                            {--email= : Which administrator; defaults to the configured one}
                            {--password= : Skip the prompt. Lands in shell history, so prefer the prompt}';

    protected $description = 'Set an administrator panel password';

    public function handle(): int
    {
        $email = $this->option('email') ?: config('site.admin.email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No account for {$email}.");

            $known = User::query()->orderBy('email')->pluck('email');

            if ($known->isNotEmpty()) {
                $this->line('Accounts that do exist: '.$known->implode(', '));
            }

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('New password');

        if (mb_strlen((string) $password) < 8) {
            $this->error('Too short — eight characters at the very least. Nothing was changed.');

            return self::FAILURE;
        }

        // `forceFill` because password is guarded; the `hashed` cast does the
        // hashing, so the plain string never reaches the database.
        $user->forceFill([
            'password' => $password,
            'is_active' => true,
            'role' => User::ROLE_ADMIN,
        ])->save();

        // Read it back rather than trusting the write: this command exists
        // because two earlier ways of doing this reported success and left the
        // operator locked out.
        if (! Hash::check($password, $user->fresh()->password)) {
            $this->error('The password did not take. Nothing else to try from here.');

            return self::FAILURE;
        }

        $this->info("Done. {$email} can sign in with that password now.");

        return self::SUCCESS;
    }
}
