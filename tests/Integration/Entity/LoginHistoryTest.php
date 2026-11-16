<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\LoginHistory;
use App\Enum\LoginMethod;
use App\Repository\LoginHistoryRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Persistance de l'historique de connexion : troncatures et conservation de la ligne à la suppression du compte.
 */
#[Group('integration')]
class LoginHistoryTest extends KernelTestCase
{
    public function testPersisteUneConnexionReussieEtTronqueLUserAgent(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = UserFactory::createOne();

        $entry = (new LoginHistory(LoginMethod::WEB, true))
            ->setUser($user)
            ->setIp('203.0.113.9')
            ->setUserAgent(str_repeat(string: 'x', times: 800))
            ->setFingerprint(hash(algo: 'sha256', data: 'test'));
        $em->persist($entry);
        $em->flush();
        $em->clear();

        $repo = static::getContainer()->get(LoginHistoryRepository::class);
        $latest = $repo->findLatestByUser($user, 5);

        static::assertCount(1, $latest);
        static::assertSame(LoginMethod::WEB, $latest[0]->getMethod());
        static::assertTrue($latest[0]->isSuccess());
        static::assertSame(LoginHistory::USER_AGENT_MAX_LENGTH, mb_strlen((string) $latest[0]->getUserAgent()));
        static::assertFalse($latest[0]->isIpAnonymized());
    }

    public function testLaLigneSurvitALaSuppressionDuCompte(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = UserFactory::createOne();

        $entry = (new LoginHistory(LoginMethod::API_JWT, false))
            ->setUser($user)
            ->setIdentifier('inconnu@example.test')
            ->setFailureReason('bad_credentials');
        $em->persist($entry);
        $em->flush();
        $entryId = $entry->getId();

        $em->remove($em->getReference($user::class, $user->getId()));
        $em->flush();
        $em->clear();

        $reloaded = $em->find(LoginHistory::class, $entryId);

        static::assertNotNull($reloaded);
        static::assertNull($reloaded->getUser());
        static::assertSame('inconnu@example.test', $reloaded->getIdentifier());
    }
}
