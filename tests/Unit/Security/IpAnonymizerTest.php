<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\IpAnonymizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * IPv4 : dernier octet à 0. IPv6 : seuls les 48 premiers bits sont conservés.
 */
#[Group('unit')]
class IpAnonymizerTest extends TestCase
{
    /** @return iterable<string, array{string, string|null}> */
    public static function adresses(): iterable
    {
        yield 'ipv4' => ['203.0.113.9', '203.0.113.0'];
        yield 'ipv4 déjà anonymisée' => ['203.0.113.0', '203.0.113.0'];
        yield 'ipv6' => ['2001:db8:85a3:8d3:1319:8a2e:370:7348', '2001:db8:85a3::'];
        yield 'ipv6 abrégée' => ['2001:db8::1', '2001:db8::'];
        yield 'ipv6 mappée ipv4' => ['::ffff:203.0.113.9', '203.0.113.0'];
        yield 'chaîne invalide' => ['pas-une-ip', null];
        yield 'vide' => ['', null];
    }

    #[DataProvider('adresses')]
    public function testAnonymize(string $ip, ?string $attendu): void
    {
        static::assertSame($attendu, IpAnonymizer::anonymize($ip));
    }
}
