<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Anonymisation d'une adresse IP pour la conservation longue de l'historique de connexion.
 *
 * IPv4 : dernier octet mis à 0 (203.0.113.9 devient 203.0.113.0).
 * IPv6 : seuls les 48 premiers bits sont conservés (préfixe attribué à un site).
 */
final class IpAnonymizer
{
    private const int IPV4_KEPT_BYTES = 3;
    private const int IPV6_KEPT_BYTES = 6;
    private const string IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /** Null si la chaîne n'est pas une adresse IP valide. */
    public static function anonymize(string $ip): ?string
    {
        $packed = inet_pton($ip);
        if (false === $packed) {
            return null;
        }

        // IPv4 portée par une socket double pile (::ffff:a.b.c.d) : traitée comme une IPv4
        if (16 === strlen($packed) && str_starts_with($packed, self::IPV4_MAPPED_PREFIX)) {
            $packed = substr($packed, offset: 12);
        }

        $kept = 4 === strlen($packed) ? self::IPV4_KEPT_BYTES : self::IPV6_KEPT_BYTES;
        $masked = substr($packed, offset: 0, length: $kept) . str_repeat("\0", times: strlen($packed) - $kept);

        $result = inet_ntop($masked);

        return false === $result ? null : $result;
    }
}
