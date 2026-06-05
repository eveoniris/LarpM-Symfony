<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Bonus;
use App\Entity\GroupeBonus;
use App\Entity\PersonnageBonus;
use App\Enum\BonusType;
use App\Service\PersonnageService;
use App\Tests\Factory\BonusFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\PersonnageFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for PersonnageService::isBonusEffective() and getEffectivePersonnagesForBonus().
 */
#[Group('integration')]
class PersonnageBonusEffectiveTest extends KernelTestCase
{
    private PersonnageService $personnageService;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->personnageService = $container->get(PersonnageService::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    // ── isBonusEffective ──────────────────────────────────────────────────────

    public function testBonusWithNoConditionsIsAlwaysEffective(): void
    {
        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => ['renomme' => 5],
        ]);
        $personnage = PersonnageFactory::createOne();

        self::assertTrue($this->personnageService->isBonusEffective($personnage, $bonus));
    }

    public function testBonusWithUnmetCompetenceConditionIsNotEffective(): void
    {
        $competence = CompetenceFactory::createOne();

        $bonus = BonusFactory::createOne([
            'type' => BonusType::LANGUE->value,
            'json_data' => [
                'langue' => ['id' => 99, 'condition' => ['type' => 'COMPETENCE', 'value' => $competence->getId()]],
            ],
        ]);
        $personnage = PersonnageFactory::createOne();

        self::assertFalse($this->personnageService->isBonusEffective($personnage, $bonus));
    }

    public function testBonusWithMetCompetenceConditionIsEffective(): void
    {
        $competence = CompetenceFactory::createOne();

        $bonus = BonusFactory::createOne([
            'type' => BonusType::LANGUE->value,
            'json_data' => [
                'langue' => ['id' => 99, 'condition' => ['type' => 'COMPETENCE', 'value' => $competence->getId()]],
            ],
        ]);
        $personnage = PersonnageFactory::createOne();
        $competence->addPersonnage($personnage);
        $this->em->flush();

        self::assertTrue($this->personnageService->isBonusEffective($personnage, $bonus));
    }

    public function testBonusWithRootConditionNotMetIsNotEffective(): void
    {
        $competence = CompetenceFactory::createOne();

        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => [
                'renomme' => 3,
                'condition' => ['type' => 'COMPETENCE', 'value' => $competence->getId()],
            ],
        ]);
        $personnage = PersonnageFactory::createOne();

        self::assertFalse($this->personnageService->isBonusEffective($personnage, $bonus));
    }

    public function testBonusWithRootConditionMetIsEffective(): void
    {
        $competence = CompetenceFactory::createOne();

        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => [
                'renomme' => 3,
                'condition' => ['type' => 'COMPETENCE', 'value' => $competence->getId()],
            ],
        ]);
        $personnage = PersonnageFactory::createOne();
        $competence->addPersonnage($personnage);
        $this->em->flush();

        self::assertTrue($this->personnageService->isBonusEffective($personnage, $bonus));
    }

    // ── getEffectivePersonnagesForBonus ───────────────────────────────────────

    public function testGroupeBonusWithNoConditionListsAllGroupePersonnages(): void
    {
        $groupe = GroupeFactory::createOne();
        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => ['renomme' => 2],
        ]);
        $bonusId = $bonus->getId();

        $gb = new GroupeBonus();
        $gb->setGroupe($groupe);
        $gb->setBonus($bonus);
        $gb->setCreationDate(new \DateTime());
        $gb->setStatus('actif');
        $this->em->persist($gb);

        $p1 = PersonnageFactory::createOne(['groupe' => $groupe]);
        $p2 = PersonnageFactory::createOne(['groupe' => $groupe]);
        $this->em->flush();

        // Reload fresh from DB so Doctrine collections reflect the persisted state
        $this->em->clear();
        $bonus = $this->em->find(Bonus::class, $bonusId);

        $result = $this->personnageService->getEffectivePersonnagesForBonus($bonus);
        $ids = array_map(fn ($p) => $p->getId(), $result);

        self::assertContains($p1->getId(), $ids);
        self::assertContains($p2->getId(), $ids);
    }

    public function testGroupeBonusWithUnmetConditionExcludesPersonnage(): void
    {
        $groupe = GroupeFactory::createOne();
        $competence = CompetenceFactory::createOne();

        $bonus = BonusFactory::createOne([
            'type' => BonusType::LANGUE->value,
            'json_data' => [
                'langue' => ['id' => 99, 'condition' => ['type' => 'COMPETENCE', 'value' => $competence->getId()]],
            ],
        ]);
        $bonusId = $bonus->getId();

        $gb = new GroupeBonus();
        $gb->setGroupe($groupe);
        $gb->setBonus($bonus);
        $gb->setCreationDate(new \DateTime());
        $gb->setStatus('actif');
        $this->em->persist($gb);

        $pWith = PersonnageFactory::createOne(['groupe' => $groupe]);
        $competence->addPersonnage($pWith);
        $pWithout = PersonnageFactory::createOne(['groupe' => $groupe]);
        $this->em->flush();

        $this->em->clear();
        $bonus = $this->em->find(Bonus::class, $bonusId);

        $result = $this->personnageService->getEffectivePersonnagesForBonus($bonus);
        $ids = array_map(fn ($p) => $p->getId(), $result);

        self::assertContains($pWith->getId(), $ids);
        self::assertNotContains($pWithout->getId(), $ids);
    }

    public function testDirectPersonnageBonusIsIncluded(): void
    {
        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => ['renomme' => 1],
        ]);
        $bonusId = $bonus->getId();

        $personnage = PersonnageFactory::createOne();

        $pb = new PersonnageBonus();
        $pb->setPersonnage($personnage);
        $pb->setBonus($bonus);
        $this->em->persist($pb);
        $this->em->flush();

        $this->em->clear();
        $bonus = $this->em->find(Bonus::class, $bonusId);

        $result = $this->personnageService->getEffectivePersonnagesForBonus($bonus);
        $ids = array_map(fn ($p) => $p->getId(), $result);

        self::assertContains($personnage->getId(), $ids);
    }

    public function testPersonnageIsNotDuplicatedWhenInMultipleSources(): void
    {
        $groupe = GroupeFactory::createOne();
        $bonus = BonusFactory::createOne([
            'type' => BonusType::RENOMME->value,
            'json_data' => ['renomme' => 1],
        ]);
        $bonusId = $bonus->getId();

        $gb = new GroupeBonus();
        $gb->setGroupe($groupe);
        $gb->setBonus($bonus);
        $gb->setCreationDate(new \DateTime());
        $gb->setStatus('actif');
        $this->em->persist($gb);

        $personnage = PersonnageFactory::createOne(['groupe' => $groupe]);

        $pb = new PersonnageBonus();
        $pb->setPersonnage($personnage);
        $pb->setBonus($bonus);
        $this->em->persist($pb);
        $this->em->flush();

        $this->em->clear();
        $bonus = $this->em->find(Bonus::class, $bonusId);

        $result = $this->personnageService->getEffectivePersonnagesForBonus($bonus);
        $ids = array_map(fn ($p) => $p->getId(), $result);
        $uniqueIds = array_unique($ids);

        self::assertSame(count($uniqueIds), count($ids), 'Personnage should not appear twice');
        self::assertContains($personnage->getId(), $ids);
    }
}
