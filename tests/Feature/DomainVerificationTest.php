<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Files that exist only because an outside verifier asks for them.
 *
 * eNamad, Search Console and the rest prove you own a domain by fetching a
 * file from its root. The file has no content and no purpose on the site — it
 * is a zero-byte marker with a random numeric name sitting in `public/`, which
 * is to say it looks exactly like something left behind by accident.
 *
 * It is not checked once. eNamad re-reads it periodically, and the seal is
 * revoked when it stops answering. So the file has to survive a deploy, a
 * server rebuild and — the likeliest way it goes — somebody tidying up a
 * directory and seeing an empty file named after nothing.
 *
 * This is that somebody's warning. Deleting one of these fails the suite.
 */
class DomainVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Each entry: the path under `public/`, who asks for it, and whether the
     * verifier requires the file to be empty.
     *
     * Adding another verifier is one line here and one file. Removing one is a
     * decision, which is the point.
     *
     * @return list<array{0: string, 1: string, 2: bool}>
     */
    public static function files(): array
    {
        return [
            ['54474714.txt', 'eNamad (نماد اعتماد الکترونیکی)', true],
        ];
    }

    #[DataProvider('files')]
    public function test_the_verification_file_is_still_there(string $path, string $owner, bool $mustBeEmpty): void
    {
        $full = dirname(__DIR__, 2).'/public/'.$path;

        $this->assertFileExists(
            $full,
            "public/{$path} is gone. {$owner} fetches it to prove the domain is ours, and "
            .'re-checks it on a schedule — without it the verification lapses.',
        );

        if ($mustBeEmpty) {
            $this->assertSame(
                0,
                (int) filesize($full),
                "public/{$path} has content. {$owner} asked for an empty file; anything in it may fail the check.",
            );
        }
    }

    /**
     * And plain HTTP has to answer for it.
     *
     * This is the failure that actually happened, and nothing else would have
     * caught it: the file was in place and `https://` returned 200, while
     * `http://` returned the site-wide 301 and eNamad — which requests over
     * HTTP and does not follow redirects — reported the file as never
     * uploaded. The nginx `deploy/https.sh` writes now serves a real .txt at
     * the document root over HTTP as well.
     *
     * `try_files $uri @https` rather than `=404` matters: `robots.txt` is a
     * route and not a file on disk, so it misses and falls through to the
     * redirect exactly as before.
     */
    public function test_plain_http_serves_a_root_text_file(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2).'/deploy/https.sh');

        // Asserted without the backslashes: the pattern is a regex inside a
        // printf format string, so it carries two layers of escaping that are
        // easy to get wrong in the assertion and prove nothing when you do.
        $this->assertStringContainsString(
            '.txt$ { try_files $uri @https; }',
            $script,
            'The port-80 block no longer serves root .txt files, so a verifier fetching '
            .'over HTTP gets a 301 and reports the file missing.',
        );

        $this->assertStringContainsString(
            'location @https {',
            $script,
            'The named fallback is gone, so a .txt that is a route rather than a file '
            .'(robots.txt) would 404 instead of redirecting.',
        );
    }

    /**
     * The meta-tag route to the same proof.
     *
     * eNamad offers three ways to verify and will ask for whichever it feels
     * like: a file at the root, the code in the page title, or this. The tag
     * is the only one of the three that is permanent and costs nothing — it
     * does not touch the title, which is the strongest brand signal the page
     * has, and it does not leave a numeric file lying in the document root.
     */
    public function test_the_enamad_tag_renders_when_it_is_set(): void
    {
        Setting::put('seo.enamad', '54474714', 'seo', false);

        $this->get('/fa')
            ->assertOk()
            ->assertSee('<meta name="enamad" content="54474714">', false);
    }

    /**
     * And is absent when it is not.
     *
     * `<meta name="enamad" content="">` is not a tag that says "not
     * configured" — to a verifier it is a tag that says the wrong thing.
     */
    public function test_an_unset_verification_tag_is_not_rendered(): void
    {
        config()->set('site.seo.enamad', '');

        $this->get('/fa')->assertOk()->assertDontSee('name="enamad"', false);
    }

    /**
     * The panel box has to be the one the page reads.
     *
     * It was not. The settings screen has offered a `google-site-verification`
     * field since it existed, and the template read
     * `config('site.seo.google_site_verification')` — a different key from a
     * different source — so anything typed into that box went nowhere and
     * nothing said so. Both now resolve through `Seo::verifications()`.
     */
    public function test_a_code_typed_into_the_panel_reaches_the_page(): void
    {
        Setting::put('seo.google_verification', 'typed-into-the-panel', 'seo', false);

        $this->assertSame(
            'typed-into-the-panel',
            Seo::verifications()['google-site-verification'] ?? null,
            'The panel field is not what the page renders.',
        );

        $this->get('/fa')->assertOk()->assertSee('content="typed-into-the-panel"', false);
    }

    /**
     * It has to be in the document root itself.
     *
     * nginx serves `public/` through `try_files $uri`, so a file there answers
     * at the site root before the application is involved — no locale prefix,
     * no router, no redirect. Anywhere else in the project and the verifier
     * gets a 404.
     */
    #[DataProvider('files')]
    public function test_the_file_is_in_the_document_root(string $path): void
    {
        $this->assertFileDoesNotExist(
            dirname(__DIR__, 2).'/'.$path,
            "{$path} is at the project root, where nothing serves it. It belongs in public/.",
        );

        $this->assertStringNotContainsString(
            '/',
            $path,
            'A verifier fetches this from the site root, so it cannot sit in a subdirectory.',
        );
    }
}
