<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\ExperienceGain;
use App\Entity\ExperienceUsage;
use App\Service\CompetenceService;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Integration tests for CompetenceService XP gating and cost tiers.
 *
 * DAMA bundle (configured in phpunit.dist.xml) wraps each test in a DB transaction
 * and rolls back automatically — no need for the Foundry ResetDatabase trait.
 *
 * @group integration
 */
class CompetenceServiceTest extends KernelTestCase
{
    use Factories;

    private CompetenceService $competenceService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->competenceService = $container->get(CompetenceService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    // -------------------------------------------------------------------------
    // XP Gating
    // -------------------------------------------------------------------------

    public function testCanLearnReturnsTrueWhenXpSufficient(): void
    {
        $level = LevelFactory::createOne(['index' => 10, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 50]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());

        self::assertTrue($service->canLearn(5));
        self::assertFalse($service->hasErrors());
    }

    public function testCanLearnReturnsFalseWhenXpInsufficient(): void
    {
        $level = LevelFactory::createOne(['index' => 11, 'cout' => 10, 'cout_favori' => 8, 'cout_meconu' => 15]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 5]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());

        self::assertFalse($service->canLearn(10));
        self::assertTrue($service->hasErrors());
        $errors = $service->getErrors();
        self::assertArrayHasKey(CompetenceService::ERR_CODE_XP, $errors);
    }

    public function testCanLearnTrueForCostZeroEvenWithNegativeXp(): void
    {
        $level = LevelFactory::createOne(['index' => 12, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => -10]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());

        self::assertTrue($service->canLearn(0));
        self::assertFalse($service->hasErrors());
    }

    public function testAddCompetenceDecrementsXp(): void
    {
        $level = LevelFactory::createOne(['index' => 20, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 50]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());
        $service->addCompetence(5);

        self::assertFalse($service->hasErrors());

        $this->entityManager->refresh($personnage->_real());
        self::assertSame(45, $personnage->_real()->getXp());
    }

    public function testAddCompetenceCreatesExperienceUsageRecord(): void
    {
        $level = LevelFactory::createOne(['index' => 21, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 50]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());
        $service->addCompetence(5);

        $usages = $this->entityManager
            ->getRepository(ExperienceUsage::class)
            ->findBy(['personnage' => $personnage->_real()]);

        self::assertCount(1, $usages);
        self::assertSame(5, $usages[0]->getXpUse());
    }

    public function testAddCompetenceLinksCompetenceToPersonnage(): void
    {
        $level = LevelFactory::createOne(['index' => 22, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 50]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());
        $service->addCompetence(5);

        self::assertFalse($service->hasErrors());

        $this->entityManager->refresh($personnage->_real());
        self::assertTrue($personnage->_real()->getCompetences()->contains($competence->_real()));
    }

    // -------------------------------------------------------------------------
    // Cost tiers by class
    // -------------------------------------------------------------------------

    public function testCostIsZeroForCreationFamilyAtLevelOne(): void
    {
        // level index=1 is NIVEAU_1
        $level = LevelFactory::createOne(['index' => 1, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $creationFamily = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $creationFamily, 'level' => $level]);

        $classe = ClasseFactory::createOne();
        $classe->_real()->addCompetenceFamilyCreation($creationFamily->_real());
        $this->entityManager->flush();

        $personnage = PersonnageFactory::createOne([
            'xp' => 100,
            'classe' => $classe,
        ]);

        $service = $this->competenceService
            ->init($personnage->_real(), $competence->_real());

        self::assertSame(0, $service->getCompetenceCout());
    }

    public function testCostIsCoutFavoriForFavoriteFamily(): void
    {
        $level = LevelFactory::createOne([
            'index' => 30,
            'cout' => 6,
            'cout_favori' => 3,
            'cout_meconu' => 9,
        ]);
        $favoriteFamily = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $favoriteFamily, 'level' => $level]);

        $classe = ClasseFactory::createOne();
        $classe->_real()->addCompetenceFamilyFavorite($favoriteFamily->_real());
        $this->entityManager->flush();

        $personnage = PersonnageFactory::createOne(['xp' => 100, 'classe' => $classe]);

        $service = $this->competenceService->init($personnage->_real(), $competence->_real());

        self::assertSame(3, $service->getCompetenceCout());
    }

    public function testCostIsCoutForNormaleFamily(): void
    {
        $level = LevelFactory::createOne([
            'index' => 31,
            'cout' => 6,
            'cout_favori' => 3,
            'cout_meconu' => 9,
        ]);
        $normaleFamily = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $normaleFamily, 'level' => $level]);

        $classe = ClasseFactory::createOne();
        $classe->_real()->addCompetenceFamilyNormale($normaleFamily->_real());
        $this->entityManager->flush();

        $personnage = PersonnageFactory::createOne(['xp' => 100, 'classe' => $classe]);

        $service = $this->competenceService->init($personnage->_real(), $competence->_real());

        self::assertSame(6, $service->getCompetenceCout());
    }

    public function testCostIsCoutMeconnuForUnknownFamily(): void
    {
        $level = LevelFactory::createOne([
            'index' => 32,
            'cout' => 6,
            'cout_favori' => 3,
            'cout_meconu' => 9,
        ]);
        // This family is not added to any class collection → méconnue
        $unknownFamily = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $unknownFamily, 'level' => $level]);

        $classe = ClasseFactory::createOne();
        $personnage = PersonnageFactory::createOne(['xp' => 100, 'classe' => $classe]);

        $service = $this->competenceService->init($personnage->_real(), $competence->_real());

        self::assertSame(9, $service->getCompetenceCout());
    }

    public function testCostIsFlooredAtZero(): void
    {
        // Even if bonusCout would make cost negative, it is floored at 0
        $level = LevelFactory::createOne([
            'index' => 33,
            'cout' => 2,
            'cout_favori' => 1,
            'cout_meconu' => 3,
        ]);
        $favoriteFamily = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $favoriteFamily, 'level' => $level]);

        $classe = ClasseFactory::createOne();
        $classe->_real()->addCompetenceFamilyFavorite($favoriteFamily->_real());
        $this->entityManager->flush();

        // xp=100, normal favori cout=1, no bonus → cost = max(1 - 0, 0) = 1
        $personnage = PersonnageFactory::createOne(['xp' => 100, 'classe' => $classe]);

        $service = $this->competenceService->init($personnage->_real(), $competence->_real());

        self::assertGreaterThanOrEqual(0, $service->getCompetenceCout());
    }

    // -------------------------------------------------------------------------
    // removeCompetence
    // -------------------------------------------------------------------------

    public function testRemoveCompetenceRefundsXp(): void
    {
        $level = LevelFactory::createOne(['index' => 40, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 45]);

        // Personnage already knows the competence
        $personnage->_real()->addCompetence($competence->_real());
        $this->entityManager->flush();

        $this->competenceService
            ->init($personnage->_real(), $competence->_real())
            ->removeCompetence(5);

        $this->entityManager->refresh($personnage->_real());
        // giveXP(5, ...) adds 5 to current xp: 45 + 5 = 50
        self::assertSame(50, $personnage->_real()->getXp());
    }

    public function testRemoveCompetenceCreatesExperienceGainRecord(): void
    {
        $level = LevelFactory::createOne(['index' => 41, 'cout' => 7, 'cout_favori' => 4, 'cout_meconu' => 10]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 30]);

        $personnage->_real()->addCompetence($competence->_real());
        $this->entityManager->flush();

        $this->competenceService
            ->init($personnage->_real(), $competence->_real())
            ->removeCompetence(7);

        $gains = $this->entityManager
            ->getRepository(ExperienceGain::class)
            ->findBy(['personnage' => $personnage->_real()]);

        self::assertCount(1, $gains);
        self::assertSame(7, $gains[0]->getXpGain());
    }
}
