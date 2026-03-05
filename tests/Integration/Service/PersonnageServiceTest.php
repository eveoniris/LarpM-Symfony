<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Personnage;
use App\Service\PersonnageService;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for PersonnageService.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 *
 * @group integration
 */
class PersonnageServiceTest extends KernelTestCase
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
    // canCreatePersonnage
    // -------------------------------------------------------------------------

    public function testCanCreatePersonnageReturnsTrueWhenUserHasFewerThanMax(): void
    {
        $user = UserFactory::createOne();

        // 2 vivant personnages — below MAX_PER_USER (3)
        PersonnageFactory::createMany(2, ['user' => $user, 'vivant' => true]);

        self::assertTrue($this->personnageService->canCreatePersonnage($user));
    }

    public function testCanCreatePersonnageReturnsFalseWhenUserHasMaxPersonnages(): void
    {
        $user = UserFactory::createOne();

        // 3 vivant personnages — equal to MAX_PER_USER (3)
        PersonnageFactory::createMany(3, ['user' => $user, 'vivant' => true]);

        self::assertFalse($this->personnageService->canCreatePersonnage($user));
    }

    public function testCanCreatePersonnageIgnoresDeadPersonnages(): void
    {
        $user = UserFactory::createOne();

        // 3 dead personnages do NOT count toward the limit
        PersonnageFactory::createMany(3, ['user' => $user, 'vivant' => false]);

        self::assertTrue($this->personnageService->canCreatePersonnage($user));
    }

    public function testCanCreatePersonnageReturnsFalseForNullUser(): void
    {
        // No security context in integration test → null user → returns false
        self::assertFalse($this->personnageService->canCreatePersonnage(null));
    }

    // -------------------------------------------------------------------------
    // createNewPersonnage — XP from GN
    // -------------------------------------------------------------------------

    public function testCreateNewPersonnageSetXpFromGn(): void
    {
        $gn = GnFactory::createOne(['xp_creation' => 30]);
        $age = AgeFactory::createOne(['minimumValue' => 18]);
        $classe = ClasseFactory::createOne();
        $genre = GenreFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $territoire = TerritoireFactory::createOne();
        $user = UserFactory::createOne();

        $personnage = $this->personnageService->createNewPersonnage(
            null,
            $user,
            null,
            $gn,
            static function (Personnage $p) use ($age, $classe, $genre, $groupe, $territoire): void {
                $p->setNom('Test Personnage');
                $p->setVivant(true);
                $p->setBracelet(false);
                $p->setAge($age);
                $p->setClasse($classe);
                $p->setGenre($genre);
                $p->setGroupe($groupe);
                $p->setTerritoire($territoire);
            },
        );

        // Age.bonus is null by default → xp = gn.xpCreation only
        self::assertSame(30, $personnage->getXp());
    }

    public function testCreateNewPersonnageAddsAgeBonusXp(): void
    {
        $gn = GnFactory::createOne(['xp_creation' => 20]);
        $age = AgeFactory::createOne(['minimumValue' => 25, 'bonus' => 10]);
        $classe = ClasseFactory::createOne();
        $genre = GenreFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $territoire = TerritoireFactory::createOne();
        $user = UserFactory::createOne();

        $personnage = $this->personnageService->createNewPersonnage(
            null,
            $user,
            null,
            $gn,
            static function (Personnage $p) use ($age, $classe, $genre, $groupe, $territoire): void {
                $p->setNom('Aged Personnage');
                $p->setVivant(true);
                $p->setBracelet(false);
                $p->setAge($age);
                $p->setClasse($classe);
                $p->setGenre($genre);
                $p->setGroupe($groupe);
                $p->setTerritoire($territoire);
            },
        );

        // xp = gn.xpCreation (20) + age.bonus (10) = 30
        self::assertSame(30, $personnage->getXp());
    }

    public function testCreateNewPersonnagePersistsToDatabase(): void
    {
        $gn = GnFactory::createOne(['xp_creation' => 15]);
        $age = AgeFactory::createOne(['minimumValue' => 18]);
        $classe = ClasseFactory::createOne();
        $genre = GenreFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $territoire = TerritoireFactory::createOne();
        $user = UserFactory::createOne();

        $personnage = $this->personnageService->createNewPersonnage(
            null,
            $user,
            null,
            $gn,
            static function (Personnage $p) use ($age, $classe, $genre, $groupe, $territoire): void {
                $p->setNom('Persisted Personnage');
                $p->setVivant(true);
                $p->setBracelet(false);
                $p->setAge($age);
                $p->setClasse($classe);
                $p->setGenre($genre);
                $p->setGroupe($groupe);
                $p->setTerritoire($territoire);
            },
        );

        self::assertNotNull($personnage->getId(), 'Personnage should have been persisted with an ID');
    }

    // -------------------------------------------------------------------------
    // canTeachCompetence
    // -------------------------------------------------------------------------

    public function testCanTeachCompetenceReturnsFalseWhenPersonnageDoesNotKnowCompetence(): void
    {
        $level = LevelFactory::createOne(['index' => 3]);
        $family = CompetenceFamilyFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['xp' => 100]);

        // Personnage does NOT have the competence
        self::assertFalse($this->personnageService->canTeachCompetence($personnage, $competence));
    }

    public function testCanTeachCompetenceReturnsFalseWhenCompetenceFamilyHasNoType(): void
    {
        // A CompetenceFamily with no matching type label → getCompetenceFamilyType() returns null
        // → hasCompetenceLevel(null, ...) → false
        $level = LevelFactory::createOne(['index' => 3]);
        $family = CompetenceFamilyFactory::createOne(['label' => 'Unknown Family Type XYZ']);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);

        $personnage = PersonnageFactory::createOne(['xp' => 100]);
        // Add the competence to the personnage (isKnownCompetence will return true)
        $personnage->addCompetence($competence);

        // hasCompetenceLevel(null, ...) → false even though personnage knows the competence
        self::assertFalse($this->personnageService->canTeachCompetence($personnage, $competence));
    }
}
