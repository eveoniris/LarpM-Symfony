<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\Participant;
use App\Entity\PersonnageSecondaire;
use App\Entity\QrCodeScanLog;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression test for bug #2: deleting a Participant with related qrCodeScanLogs
 * must not throw a FK constraint violation.
 *
 * Fix: added cascade: ['remove'] to BaseParticipant::$qrCodeScanLogs.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('integration')]
class ParticipantCascadeDeleteTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDeleteParticipantWithQrCodeScanLogDoesNotThrowFkViolation(): void
    {
        $user = UserFactory::createOne();
        $gn = GnFactory::createOne();
        $classe = ClasseFactory::createOne();

        $personnageSecondaire = new PersonnageSecondaire();
        $personnageSecondaire->setClasse($classe);
        $this->em->persist($personnageSecondaire);

        $participant = new Participant();
        $participant->setUser($user);
        $participant->setGn($gn);
        $participant->setPersonnageSecondaire($personnageSecondaire);
        $this->em->persist($participant);

        $log = new QrCodeScanLog();
        $log->setDate(new DateTime());
        $log->setUser($user);
        $log->setAllowed(true);
        $log->setParticipant($participant);
        $this->em->persist($log);

        $this->em->flush();

        $logId = $log->getId();
        $participantId = $participant->getId();

        // Clear the identity map so Doctrine lazy-loads collections from DB on next access
        $this->em->clear();
        $fresh = $this->em->find(Participant::class, $participantId);

        // Delete the participant - cascade must remove the log automatically
        $this->em->remove($fresh);
        $this->em->flush();

        // Participant is gone
        static::assertNull($this->em->find(Participant::class, $participantId));

        // QrCodeScanLog is gone via cascade
        static::assertNull($this->em->find(QrCodeScanLog::class, $logId));
    }
}
