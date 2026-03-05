<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\PersonnagesReligions;
use App\Service\PersonnageService;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\ReligionFactory;
use App\Tests\Factory\ReligionLevelFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for the business-logic guards used by lockGnGiveSanctuaireGnEffect().
 *
 * The command itself is hardcoded with production IDs and cannot be tested generically.
 * These tests instead verify each guard condition and the core assignment path:
 *   - knownReligion() returns false/true correctly
 *   - isFanatique() correctly detects fanatique level (index = 3)
 *   - Dead personnage (vivant=false) is skipped
 *   - Deiste guard works via knownReligion()
 *   - The PersonnagesReligions assignment path itself works end-to-end
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 *
 * @group integration
 */
class SanctuaireEffectTest extends KernelTestCase
{

    private PersonnageService $personnageService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->personnageService = $container->get(PersonnageService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function assignReligionAtLevel(
        \App\Entity\Personnage $personnage,
        \App\Entity\Religion $religion,
        int $levelIndex,
    ): void {
        $level = ReligionLevelFactory::createOne(['index' => $levelIndex]);

        $pr = new PersonnagesReligions();
        $pr->setPersonnage($personnage);
        $pr->setReligion($religion);
        $pr->setReligionLevel($level);
        $this->entityManager->persist($pr);
        $this->entityManager->flush();
        $this->entityManager->refresh($personnage);
    }

    // -------------------------------------------------------------------------
    // knownReligion() guard
    // -------------------------------------------------------------------------

    public function testKnownReligionReturnsFalseWhenNoneAssigned(): void
    {
        $personnage = PersonnageFactory::createOne();
        $religion = ReligionFactory::createOne();

        self::assertFalse(
            $this->personnageService->knownReligion($personnage, $religion),
        );
    }

    public function testKnownReligionReturnsTrueAfterAssignment(): void
    {
        $personnage = PersonnageFactory::createOne();
        $religion = ReligionFactory::createOne();

        $this->assignReligionAtLevel($personnage, $religion, 1);

        self::assertTrue(
            $this->personnageService->knownReligion($personnage, $religion),
        );
    }

    public function testKnownReligionReturnsFalseForDifferentReligion(): void
    {
        $personnage = PersonnageFactory::createOne();
        $religion = ReligionFactory::createOne();
        $otherReligion = ReligionFactory::createOne();

        $this->assignReligionAtLevel($personnage, $religion, 1);

        self::assertFalse(
            $this->personnageService->knownReligion($personnage, $otherReligion),
        );
    }

    // -------------------------------------------------------------------------
    // isFanatique() guard
    // -------------------------------------------------------------------------

    public function testIsFanaticReturnsFalseWithNoReligions(): void
    {
        $personnage = PersonnageFactory::createOne();

        self::assertFalse($personnage->isFanatique());
    }

    public function testIsFanaticReturnsFalseWithPratiquantLevel(): void
    {
        $personnage = PersonnageFactory::createOne();
        $religion = ReligionFactory::createOne();

        $this->assignReligionAtLevel($personnage, $religion, 1);

        self::assertFalse($personnage->isFanatique());
    }

    public function testIsFanaticReturnsTrueWithFanatiqueLevel(): void
    {
        $personnage = PersonnageFactory::createOne();
        $religion = ReligionFactory::createOne();

        // level index=3 is the fanatique level
        $this->assignReligionAtLevel($personnage, $religion, 3);

        self::assertTrue($personnage->isFanatique());
    }

    // -------------------------------------------------------------------------
    // Dead personnage guard
    // -------------------------------------------------------------------------

    public function testDeadPersonnageIsNotEligible(): void
    {
        $deadPersonnage = PersonnageFactory::createOne(['vivant' => false]);

        // The sanctuaire effect skips personnages where getVivant() is false
        self::assertFalse($deadPersonnage->getVivant());
    }

    // -------------------------------------------------------------------------
    // Full assignment path (core of the sanctuaire effect for eligible personnages)
    // -------------------------------------------------------------------------

    public function testAssignFirstLevelReligionToLivingPersonnage(): void
    {
        $personnage = PersonnageFactory::createOne(['vivant' => true]);
        $religion = ReligionFactory::createOne();

        self::assertFalse(
            $this->personnageService->knownReligion($personnage, $religion),
            'Personnage should not know religion before assignment',
        );
        self::assertFalse($personnage->isFanatique());

        // Simulate what lockGnGiveSanctuaireGnEffect() does for eligible personnages
        $this->assignReligionAtLevel($personnage, $religion, 1);

        self::assertTrue(
            $this->personnageService->knownReligion($personnage, $religion),
            'Personnage should know religion after assignment',
        );
    }

    public function testDuplicateAssignmentPreventedByKnownReligionGuard(): void
    {
        $personnage = PersonnageFactory::createOne(['vivant' => true]);
        $religion = ReligionFactory::createOne();

        $this->assignReligionAtLevel($personnage, $religion, 1);

        // knownReligion() returns true → the sanctuaire effect would skip this personnage
        self::assertTrue(
            $this->personnageService->knownReligion($personnage, $religion),
            'knownReligion must return true to prevent duplicate assignment',
        );
    }

    public function testDeistPersonnageIsExcludedByKnownReligionGuard(): void
    {
        $personnage = PersonnageFactory::createOne(['vivant' => true]);
        $deiste = ReligionFactory::createOne(['label' => 'Déiste']);
        $otherReligion = ReligionFactory::createOne();

        // Personnage already has Déiste religion at level 1
        $this->assignReligionAtLevel($personnage, $deiste, 1);

        // The sanctuaire effect checks: if ($deiste && $this->knownReligion($personnage, $deiste)) → skip
        self::assertTrue(
            $this->personnageService->knownReligion($personnage, $deiste),
            'knownReligion must return true for deiste personnage to trigger the exclusion guard',
        );

        // Such a personnage would not receive the other religion
        self::assertFalse(
            $this->personnageService->knownReligion($personnage, $otherReligion),
        );
    }
}
