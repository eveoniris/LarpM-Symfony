<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Enum\SyncConfiance;
use App\Service\HelloAsso\Attendee;
use App\Service\HelloAsso\AttendeeMatcher;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
class AttendeeMatcherTest extends KernelTestCase
{
    private function matcher(): AttendeeMatcher
    {
        self::bootKernel();

        return static::getContainer()->get(AttendeeMatcher::class);
    }

    public function testEmailDeclareDansLeFormulairePrimeSurEmailDAchat(): void
    {
        $matcher = $this->matcher();
        $gn = GnFactory::createOne();
        $compte = UserFactory::createOne(['email' => 'compte@example.org']);
        UserFactory::createOne(['email' => 'achat@example.org']);

        $match = $matcher->match($gn, Attendee::fromArray(['_id' => 'a1', 'email' => 'achat@example.org']), 'compte@example.org');

        self::assertSame(SyncConfiance::SUR, $match['confiance']);
        self::assertSame($compte->getId(), $match['user']?->getId());
    }

    public function testEmailDeclareInconnuRetombeSurEmailDAchat(): void
    {
        $matcher = $this->matcher();
        $gn = GnFactory::createOne();
        $achat = UserFactory::createOne(['email' => 'achat2@example.org']);

        $match = $matcher->match($gn, Attendee::fromArray(['_id' => 'a2', 'email' => 'achat2@example.org']), 'inconnu@example.org');

        self::assertSame(SyncConfiance::PROBABLE, $match['confiance']);
        self::assertSame($achat->getId(), $match['user']?->getId());
    }

    public function testSansAucunCompte(): void
    {
        $matcher = $this->matcher();
        $gn = GnFactory::createOne();

        $match = $matcher->match($gn, Attendee::fromArray(['_id' => 'a3', 'email' => 'personne@example.org']), null);

        self::assertSame(SyncConfiance::AUCUN, $match['confiance']);
        self::assertNull($match['user']);
    }
}
