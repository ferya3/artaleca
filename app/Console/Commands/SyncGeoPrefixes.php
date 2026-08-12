<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Geo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Fetch Iran's allocated address ranges from RIPE.
 *
 * The registry's own delegation file is the authority for who holds what, it is
 * published daily, and it is free with no key. Running this once after deploy —
 * and monthly from cron — is the whole maintenance story for IP-based language
 * detection.
 *
 * Deliberately a command rather than something that happens on a web request:
 * the file is around ten megabytes, and no visitor should ever be the one
 * waiting for it.
 */
class SyncGeoPrefixes extends Command
{
    protected $signature = 'geo:sync {--country=IR : Two-letter code to extract}';

    protected $description = "Sync a country's IP ranges from the RIPE delegation file";

    private const SOURCE = 'https://ftp.ripe.net/pub/stats/ripencc/delegated-ripencc-latest';

    public function handle(): int
    {
        $country = strtoupper((string) $this->option('country'));

        $this->info("Fetching the RIPE delegation file for {$country}…");

        try {
            $response = Http::timeout(120)->get(self::SOURCE);
        } catch (\Throwable $e) {
            $this->error('Could not reach RIPE: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('RIPE responded with '.$response->status().'.');

            return self::FAILURE;
        }

        $prefixes = $this->extract($response->body(), $country);

        if ($prefixes === []) {
            /*
             * Never write an empty file over a good one. An empty list means
             * "no country known", which would quietly turn the feature off
             * rather than leaving yesterday's perfectly usable answer in place.
             */
            $this->error("No ranges found for {$country}; the existing file is left alone.");

            return self::FAILURE;
        }

        $path = storage_path('app/'.Geo::PREFIX_FILE);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "# {$country} ranges from RIPE, synced ".now()->toDateString()."\n".implode("\n", $prefixes)."\n");

        Geo::forget();

        $this->info(count($prefixes).' ranges written to '.$path);

        return self::SUCCESS;
    }

    /**
     * Pull the country's rows out of the delegation file.
     *
     * The format is `registry|cc|type|start|value|date|status`. For IPv4 the
     * value is a *count of addresses*, not a prefix length — and it is not
     * always a power of two, so one allocation can need several CIDR blocks to
     * describe it exactly. For IPv6 the value is already a prefix length.
     *
     * @return list<string>
     */
    private function extract(string $body, string $country): array
    {
        $prefixes = [];

        foreach (explode("\n", $body) as $line) {
            $parts = explode('|', trim($line));

            if (count($parts) < 5 || ($parts[1] ?? '') !== $country) {
                continue;
            }

            [$type, $start, $value] = [$parts[2], $parts[3], $parts[4]];

            if ($type === 'ipv6' && ctype_digit($value)) {
                $prefixes[] = $start.'/'.$value;

                continue;
            }

            if ($type !== 'ipv4' || ! ctype_digit($value)) {
                continue;
            }

            $base = ip2long($start);

            if ($base === false) {
                continue;
            }

            $remaining = (int) $value;

            while ($remaining > 0) {
                // The largest block that both fits the remainder and is aligned
                // to the address it starts at.
                $size = 1;

                while ($size * 2 <= $remaining && $base % ($size * 2) === 0) {
                    $size *= 2;
                }

                $prefixes[] = long2ip($base).'/'.(32 - (int) log($size, 2));

                $base += $size;
                $remaining -= $size;
            }
        }

        return $prefixes;
    }
}
