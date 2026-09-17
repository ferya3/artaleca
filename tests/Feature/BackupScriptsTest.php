<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * The backup and restore scripts.
 *
 * Shell, so PHPUnit cannot run them meaningfully — what it can do is hold the
 * handful of properties whose loss would be silent and expensive. A backup is
 * only ever tested at the moment it is needed, which is the moment it is too
 * late to find out that something was left out of it.
 */
class BackupScriptsTest extends TestCase
{
    private function script(string $name): string
    {
        $path = dirname(__DIR__, 2).'/deploy/'.$name;

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }

    /**
     * The three things a `git clone` cannot give back.
     *
     * Anything else in the archive is a convenience; these are the backup.
     */
    public function test_the_backup_carries_the_database_the_uploads_and_the_env(): void
    {
        $backup = $this->script('backup.sh');

        $this->assertStringContainsString('database/database.sqlite', $backup);
        $this->assertStringContainsString('storage/app', $backup);
        $this->assertStringContainsString('.env', $backup);
    }

    /**
     * `.env` is the one somebody will remove on principle, because a secret in
     * an archive looks wrong until you know why it is there: the SMS panel's
     * password and API key are encrypted with `APP_KEY`, so a database
     * restored beside a freshly generated key comes back with credentials
     * nothing can decrypt — and it fails quietly, as an SMS that never
     * arrives. Both scripts have to say so where it will be read.
     */
    public function test_both_scripts_explain_why_the_app_key_is_in_the_archive(): void
    {
        foreach (['backup.sh', 'restore.sh'] as $name) {
            $this->assertStringContainsString(
                'APP_KEY',
                $this->script($name),
                "deploy/{$name} no longer explains why APP_KEY has to travel with the database.",
            );
        }
    }

    /**
     * Snapshotted, not copied. A `cp` of a SQLite file that is being written
     * to can capture a torn page, and the damage surfaces at the restore.
     */
    public function test_the_database_is_snapshotted_rather_than_copied(): void
    {
        $backup = $this->script('backup.sh');

        $this->assertStringContainsString('VACUUM INTO', $backup);
        $this->assertStringContainsString('integrity_check', $backup);
    }

    /**
     * An archive whose permissions are wide is a published `.env`. Mode 600 in
     * a mode 700 directory, both set by the script rather than left to umask.
     */
    public function test_the_archive_is_not_world_readable(): void
    {
        $backup = $this->script('backup.sh');

        $this->assertStringContainsString('chmod 600 "$ARCHIVE"', $backup);
        $this->assertStringContainsString('chmod 700 "$DEST"', $backup);
    }

    /**
     * The one thing a restore script must never do is overwrite a database
     * because nobody was there to say no.
     */
    public function test_the_restore_refuses_rather_than_assuming_yes(): void
    {
        $restore = $this->script('restore.sh');

        $this->assertStringContainsString('No terminal to ask on', $restore);
        $this->assertStringContainsString('FORCE', $restore);

        // And it keeps what it replaced, so a restore of the wrong archive is
        // itself recoverable.
        $this->assertStringContainsString('before-restore-', $restore);
    }

    /**
     * `migrate --force`, never `--seed`. The restored database already holds
     * the real catalogue; the seeders would put the demonstration one back
     * beside it.
     */
    public function test_the_restore_migrates_but_never_seeds(): void
    {
        $restore = $this->script('restore.sh');

        $this->assertStringContainsString('migrate --force', $restore);
        $this->assertStringNotContainsString('--seed', $restore);

        // `optimize` last, or a config cache from before the restore would
        // still name the old APP_KEY and the old mail settings.
        $this->assertGreaterThan(
            (int) strpos($restore, 'cp -a "$WORK/database.sqlite"'),
            (int) strpos($restore, 'artisan optimize'),
            'The caches are rebuilt before the database is in place.',
        );
    }

    /** Both are documented where somebody looking for them would look. */
    public function test_they_are_in_the_deployment_notes(): void
    {
        $notes = (string) file_get_contents(dirname(__DIR__, 2).'/docs/deploy-ubuntu.md');

        $this->assertStringContainsString('deploy/backup.sh', $notes);
        $this->assertStringContainsString('deploy/restore.sh', $notes);

        // Including the part that is easy to believe is handled and is not.
        $this->assertStringContainsString('same disk is not a backup', $notes);
    }
}
