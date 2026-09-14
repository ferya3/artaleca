<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Is this address somewhere on the internet, or somewhere inside the server?
 *
 * The SMS panel is configuration: an editor picks a provider and, for a
 * reseller panel or a provider this code does not know, types the send URL
 * themselves. That makes an admin-supplied hostname into an outbound request
 * the server will make with its own network position — which is the shape of
 * every SSRF. `http://169.254.169.254/…` is the cloud metadata service and
 * `http://127.0.0.1:6379` is the Redis nobody put a password on.
 *
 * So the host is resolved *before* the connection and every address it answers
 * with is checked against the full reserved list for both families. Ported from
 * the nobatdehi project, where the same panel settings live.
 */
final class Ip
{
    /** Everything IANA has reserved, not only the three private ranges. */
    private const V4 = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16',
        '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16',
        '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
        '255.255.255.255/32',
    ];

    private const V6 = [
        '::1/128', '::/128', '::ffff:0:0/96', 'fc00::/7', 'fe80::/10', 'fec0::/10',
        'ff00::/8', '2001:db8::/32', '64:ff9b::/96', '100::/64',
    ];

    /** Prefix comparison on the packed bytes, so one routine covers v4 and v6. */
    public static function cidrMatch(string $ip, string $cidr): bool
    {
        [$net, $bits] = array_pad(explode('/', $cidr, 2), 2, null);

        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton((string) $net);

        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $bits = $bits === null ? strlen($ipBin) * 8 : (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && strncmp($ipBin, $netBin, $bytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($netBin[$bytes]) & ord($mask));
    }

    public static function isPrivate(string $ip): bool
    {
        $binary = @inet_pton($ip);

        // Unparseable is blocked rather than allowed: a guard that fails open
        // is not a guard.
        if ($binary === false) {
            return true;
        }

        /*
         * `::ffff:127.0.0.1` is loopback wearing a v6 costume, and a v6 deny
         * list does not catch it. Unwrap the embedded v4 address and check that
         * instead.
         */
        if (strlen($binary) === 16 && strncmp($binary, str_repeat("\x00", 10), 10) === 0) {
            $marker = substr($binary, 10, 2);

            if ($marker === "\xff\xff" || $marker === "\x00\x00") {
                $v4 = @inet_ntop(substr($binary, 12));

                if ($v4 !== false && str_contains($v4, '.')) {
                    return self::isPrivate($v4);
                }
            }
        }

        foreach (strlen($binary) === 4 ? self::V4 : self::V6 as $cidr) {
            if (self::cidrMatch($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a host and refuse it if any answer points inside.
     *
     * Returns the addresses it resolved to, which the caller pins the
     * connection to. Without that pin the check is decorative: a hostname can
     * answer with a public address here and a private one a millisecond later
     * when cURL resolves it again, which is DNS rebinding. An IP literal needs
     * no pin and comes back as an empty list.
     *
     * @return list<string>
     */
    public static function assertHostPublic(string $host): array
    {
        $host = trim($host, '[]');

        if ($host === '') {
            throw new RuntimeException('The host is empty.');
        }

        $isLiteral = (bool) filter_var($host, FILTER_VALIDATE_IP);
        $addresses = [];

        if ($isLiteral) {
            $addresses[] = $host;
        } else {
            $v4 = @gethostbynamel($host);

            if (is_array($v4)) {
                $addresses = $v4;
            }

            $v6 = @dns_get_record($host, DNS_AAAA);

            if (is_array($v6)) {
                foreach ($v6 as $record) {
                    if (! empty($record['ipv6'])) {
                        $addresses[] = $record['ipv6'];
                    }
                }
            }
        }

        foreach ($addresses as $address) {
            if (self::isPrivate($address)) {
                throw new RuntimeException('That address is inside the server, not on the internet.');
            }
        }

        return $isLiteral ? [] : array_values($addresses);
    }
}
