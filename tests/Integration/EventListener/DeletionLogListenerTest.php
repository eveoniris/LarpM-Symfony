<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Age;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\PersonnageApprentissage;
use App\Enum\LogActionType;
use App\Service\DeletionLogContext;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\PersonnageFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Chaque suppression ORM produit une entrée log_action unique (DeletionLogListener).
 *
 * DAMA bundle enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('integration')]
class DeletionLogListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRemoveWritesOneEntityDeleteLog(): void
    {
        $age = AgeFactory::createOne();
        $id = $age->getId();
        $before = $this->countLogs(Age::class);

        $this->em->remove($age);
        $this->em->flush();

        $logs = $this->logsFor(Age::class);
        static::assertCount($before + 1, $logs);

        $log = $this->lastLog(Age::class);
        static::assertSame(LogActionType::ENTITY_DELETE, $log->getType());
        static::assertSame($id, self::dataOf($log)['id']);
        static::assertSame(Age::class, self::dataOf($log)['class']);
        static::assertNull($log->getUser(), 'Sans utilisateur connecté (CLI/test), user reste null');
        static::assertArrayHasKey('snapshot', self::dataOf($log));
    }

    public function testAnnotationOverridesTypeAndAddsContext(): void
    {
        $age = AgeFactory::createOne();
        $before = $this->countLogs(Age::class);

        static::getContainer()->get(DeletionLogContext::class)->annotate($age, LogActionType::OTHER, ['raison' => 'test']);

        $this->em->remove($age);
        $this->em->flush();

        $logs = $this->logsFor(Age::class);
        static::assertCount($before + 1, $logs);

        $log = $this->lastLog(Age::class);
        static::assertSame(LogActionType::OTHER, $log->getType());
        static::assertSame(['raison' => 'test'], self::dataOf($log)['context']);
    }

    public function testSoftDeleteWritesLogWithSoftFlag(): void
    {
        $personnage = PersonnageFactory::createOne();
        $apprentissage = $this->createApprentissage($personnage);
        $this->em->flush();
        $id = $apprentissage->getId();
        $before = $this->countLogs(PersonnageApprentissage::class);

        $apprentissage->setDeletedAt(new DateTime());
        $this->em->flush();

        $logs = $this->logsFor(PersonnageApprentissage::class);
        static::assertCount($before + 1, $logs);

        $log = $this->lastLog(PersonnageApprentissage::class);
        static::assertSame(LogActionType::ENTITY_DELETE, $log->getType());
        static::assertTrue(self::dataOf($log)['soft']);
        static::assertSame($id, self::dataOf($log)['id']);
    }

    public function testCascadeChildIsCountedInRootLog(): void
    {
        $personnage = PersonnageFactory::createOne();
        $apprentissage = $this->createApprentissage($personnage);
        $this->em->flush();
        $beforeParent = $this->countLogs(Personnage::class);
        $beforeChild = $this->countLogs(PersonnageApprentissage::class);

        $this->em->remove($apprentissage);
        $this->em->remove($personnage);
        $this->em->flush();

        static::assertCount($beforeParent + 1, $this->logsFor(Personnage::class));
        static::assertCount($beforeChild, $this->logsFor(PersonnageApprentissage::class), "L'enfant supprimé avec son parent n'a pas d'entrée propre");

        $log = $this->lastLog(Personnage::class);
        static::assertSame([PersonnageApprentissage::class => 1], self::dataOf($log)['cascade']);
    }

    private function createApprentissage(Personnage $personnage): PersonnageApprentissage
    {
        $apprentissage = new PersonnageApprentissage();
        $apprentissage->setPersonnage($personnage);
        $apprentissage->setEnseignant($personnage);
        $apprentissage->setCompetence(CompetenceFactory::createOne());
        $this->em->persist($apprentissage);

        return $apprentissage;
    }

    /**
     * @return array<string, mixed>
     */
    private static function dataOf(LogAction $log): array
    {
        return $log->getData() ?? [];
    }

    private function lastLog(string $class): LogAction
    {
        $logs = $this->logsFor($class);
        $log = [] === $logs ? null : end($logs);
        static::assertInstanceOf(LogAction::class, $log);

        return $log;
    }

    /**
     * @return array<int, LogAction>
     */
    private function logsFor(string $class): array
    {
        return array_values(array_filter(
            $this->em->getRepository(LogAction::class)->findBy([], ['id' => 'ASC']),
            static fn (LogAction $log): bool => (self::dataOf($log)['class'] ?? null) === $class,
        ));
    }

    private function countLogs(string $class): int
    {
        return \count($this->logsFor($class));
    }
}
