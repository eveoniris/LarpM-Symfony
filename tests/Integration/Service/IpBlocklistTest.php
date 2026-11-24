<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\IpBloquee;
use App\Repository\IpBloqueeRepository;
use App\Security\IpBlocklist;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Blocage d'IP : normalisation, correspondance, expiration, garde-fous, invalidation du cache.
 */
#[Group('integration')]
class IpBlocklistTest extends KernelTestCase
{
    private function service(): IpBlocklist
    {
        self::bootKernel();
        $container = static::getContainer();

        return new IpBlocklist($container->get(IpBloqueeRepository::class), $container->get(EntityManagerInterface::class), new ArrayAdapter());
    }

    public function testBlocageEtDeblocageUneAdresse(): void
    {
        $service = $this->service();

        static::assertFalse($service->isBlocked('45.33.32.9'));

        $entry = $service->block('45.33.32.9', null, 'abus');

        static::assertSame('45.33.32.9/32', $entry->getCidr());
        static::assertTrue($service->isBlocked('45.33.32.9'));
        static::assertFalse($service->isBlocked('45.33.32.10'));

        $service->unblock($entry);

        static::assertFalse($service->isBlocked('45.33.32.9'));
    }

    public function testIpv6BloqueSonSlash64(): void
    {
        $service = $this->service();
        $entry = $service->block('2a01:e0a:1:2:aaaa:bbbb:cccc:dddd');

        static::assertSame('2a01:e0a:1:2::/64', $entry->getCidr());
        static::assertTrue($service->isBlocked('2a01:e0a:1:2:1::5'));
        static::assertFalse($service->isBlocked('2a01:e0a:1:3::1'));
    }

    public function testUnBlocageExpireNeBloquePlus(): void
    {
        $service = $this->service();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(new IpBloquee('93.184.216.7/32', new DateTimeImmutable('-1 hour')));
        $entityManager->flush();
        $service->invalidate();

        static::assertFalse($service->isBlocked('93.184.216.7'));
    }

    public function testGardeFous(): void
    {
        $service = $this->service();

        foreach (['10.0.0.5', '127.0.0.1', '192.168.1.1', 'pas-une-ip', '1.2.3.4/40'] as $invalide) {
            try {
                $service->block($invalide);
                static::fail($invalide . ' aurait dû être refusée');
            } catch (InvalidArgumentException) {
                static::addToAssertionCount(1);
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $service->block('45.33.32.0/24', null, null, 7, '45.33.32.50');
    }

    public function testDureeBornee(): void
    {
        $entry = $this->service()->block('45.33.32.77', null, null, 5000);

        static::assertLessThanOrEqual(new DateTimeImmutable('+366 days'), $entry->getExpiresAt());
    }
}
