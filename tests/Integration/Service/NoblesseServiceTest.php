<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Personnage;
use App\Service\CompetenceService;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression tests for NoblesseService give/remove renomme amounts.
 *
 * Bug history: NoblesseService used $level->getId() (DB primary key) instead of
 * $level->getIndex() (conceptual 1–5) for the valuesMap lookup - causing wrong
 * renomme amounts when level IDs don't match their indices.
 *
 * Note: Personnage::getRenomme() is history-based (sums RenommeHistory entries).
 * Both give() and remove() persist a RenommeHistory with the used amount.
 * These tests verify the correct amount (valuesMap[level.getIndex()]) is recorded
 * for each level - if the old bug were present, getId() would return a high DB
 * auto-increment ID not in the map, and the amount would default to 0.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('integration')]
class NoblesseServiceTest extends KernelTestCase
{
    private CompetenceService $competenceService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->competenceService = $container->get(CompetenceService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    /** @return array<string, array{int, int}> */
    public static function noblesseRenommeProvider(): array
    {
        return [
            'level 1 (apprenti)     → +2 renomme' => [1, 2],
            'level 2 (initié)       → +3 renomme' => [2, 3],
            'level 3 (expert)       → +2 renomme' => [3, 2],
            'level 4 (maître)       → +5 renomme' => [4, 5],
            'level 5 (grand-maître) → +6 renomme' => [5, 6],
        ];
    }

    #[DataProvider('noblesseRenommeProvider')]
    public function testGiveGrantsCorrectRenomme(int $levelIndex, int $expectedRenomme): void
    {
        // label 'Noblesse' maps to CompetenceFamilyType::NOBILITY via getFromLabel()
        $family = CompetenceFamilyFactory::createOne(['label' => 'Noblesse']);
        $level = LevelFactory::createOne(['index' => $levelIndex]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);

        $service = $this->competenceService->getCompetenceService($competence);
        $service->setPersonnage($personnage);
        $service->addCompetence(0); // cost=0 bypasses XP check

        $personnageId = $personnage->getId();
        $this->entityManager->clear();
        $fresh = $this->entityManager->find(Personnage::class, $personnageId);

        self::assertSame($expectedRenomme, $fresh->getRenomme());
    }

    #[DataProvider('noblesseRenommeProvider')]
    public function testRemoveUsesCorrectRenommeAmount(int $levelIndex, int $expectedRenomme): void
    {
        // Personnage::getRenomme() sums RenommeHistory entries (history-based).
        // removeBonus() persists one RenommeHistory with the applied amount.
        // We verify the correct amount for the level was used (not a wrong ID-based lookup).
        $family = CompetenceFamilyFactory::createOne(['label' => 'Noblesse']);
        $level = LevelFactory::createOne(['index' => $levelIndex]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);

        $service = $this->competenceService->getCompetenceService($competence);
        $service->setPersonnage($personnage);
        $service->removeBonus();
        $this->entityManager->flush();

        $personnageId = $personnage->getId();
        $this->entityManager->clear();
        $fresh = $this->entityManager->find(Personnage::class, $personnageId);

        // getRenomme() = sum of history entries; the single entry is the removed amount
        self::assertSame($expectedRenomme, $fresh->getRenomme());
    }
}
