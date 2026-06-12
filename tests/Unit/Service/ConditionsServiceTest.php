<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Classe;
use App\Entity\CompetenceFamily;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Entity\Territoire;
use App\Enum\CompetenceFamilyType;
use App\Service\ConditionsService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class ConditionsServiceTest extends TestCase
{
    private ConditionsService $service;

    protected function setUp(): void
    {
        $this->service = new ConditionsService();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makePersonnageWithClasse(int $classeId): Personnage
    {
        $classe = $this->createStub(Classe::class);
        $classe->method('getId')->willReturn($classeId);

        $personnage = $this->createStub(Personnage::class);
        $personnage->method('getClasse')->willReturn($classe);

        return $personnage;
    }

    // ── Empty conditions ──────────────────────────────────────────────────────

    public function testEmptyConditionsAlwaysValid(): void
    {
        $personnage = $this->createStub(Personnage::class);

        static::assertTrue($this->service->isValidConditions($personnage, []));
    }

    public function testEmptyListIsAllValid(): void
    {
        $personnage = $this->createStub(Personnage::class);

        static::assertTrue($this->service->isAllConditionsValid($personnage, []));
    }

    // ── Single CLASSE condition ───────────────────────────────────────────────

    public function testClasseConditionMatchesCorrectId(): void
    {
        $personnage = $this->makePersonnageWithClasse(21);
        $conditions = [['TYPE' => 'CLASSE', 'VALUE' => 21]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testClasseConditionDoesNotMatchWrongId(): void
    {
        $personnage = $this->makePersonnageWithClasse(5);
        $conditions = [['TYPE' => 'CLASSE', 'VALUE' => 21]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Single RELIGION condition ─────────────────────────────────────────────

    public function testReligionConditionMatchesById(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasReligionId')->willReturn(true);

        $conditions = [['TYPE' => 'RELIGION', 'VALUE' => 12]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testReligionConditionDoesNotMatchWrongId(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasReligionId')->willReturn(false);

        $conditions = [['TYPE' => 'RELIGION', 'VALUE' => 12]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Single COMPETENCE condition ───────────────────────────────────────────

    public function testCompetenceConditionMatchesByNumericId(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(true);

        $conditions = [['TYPE' => 'COMPETENCE', 'VALUE' => 7]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testCompetenceConditionDoesNotMatchWhenNotOwned(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(false);

        $conditions = [['TYPE' => 'COMPETENCE', 'VALUE' => 7]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    public function testCompetenceConditionByFamilyTypeValueMatchesWhenOwned(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceLevel')->willReturn(true);

        // 'Alchemy' is the enum value - getFromLabel() resolves it correctly
        $conditions = [['TYPE' => 'COMPETENCE', 'VALUE' => CompetenceFamilyType::ALCHEMY->value]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testCompetenceConditionByFamilyTypeValueFailsWhenNotOwned(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceLevel')->willReturn(false);

        $conditions = [['TYPE' => 'COMPETENCE', 'VALUE' => CompetenceFamilyType::ALCHEMY->value]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    public function testCompetenceConditionByFrenchLabelMatchesWhenOwned(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceLevel')->willReturn(true);

        // French label via tryFromOlder()
        $conditions = [['TYPE' => 'COMPETENCE', 'VALUE' => 'Alchimie']];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Single ORIGINE condition ──────────────────────────────────────────────

    public function testOrigineConditionMatchesById(): void
    {
        $territoire = $this->createStub(Territoire::class);
        $territoire->method('getId')->willReturn(5);

        $personnage = $this->createStub(Personnage::class);
        $personnage->method('getOrigine')->willReturn($territoire);

        $conditions = [['TYPE' => 'ORIGINE', 'VALUE' => 5]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testOrigineConditionDoesNotMatchDifferentId(): void
    {
        $territoire = $this->createStub(Territoire::class);
        $territoire->method('getId')->willReturn(99);

        $personnage = $this->createStub(Personnage::class);
        $personnage->method('getOrigine')->willReturn($territoire);

        $conditions = [['TYPE' => 'ORIGINE', 'VALUE' => 5]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Single LANGUE condition ───────────────────────────────────────────────

    public function testLangueConditionMatchesWhenPersonnageKnowsIt(): void
    {
        $langue = $this->createStub(Langue::class);
        $langue->method('getId')->willReturn(3);

        $pl = new PersonnageLangues();
        $pl->setLangue($langue);
        $pl->setSource('ORIGINE');

        $personnage = new Personnage();
        $personnage->addPersonnageLangues($pl);

        $conditions = [['TYPE' => 'LANGUE', 'VALUE' => 3]];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testLangueConditionDoesNotMatchWhenPersonnageDoesNotKnowIt(): void
    {
        $langue = $this->createStub(Langue::class);
        $langue->method('getId')->willReturn(99);

        $pl = new PersonnageLangues();
        $pl->setLangue($langue);
        $pl->setSource('ORIGINE');

        $personnage = new Personnage();
        $personnage->addPersonnageLangues($pl);

        $conditions = [['TYPE' => 'LANGUE', 'VALUE' => 3]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── AND mode ──────────────────────────────────────────────────────────────

    public function testAndModeBothConditionsMustMatch(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(true);
        $personnage->method('hasReligionId')->willReturn(true);

        $conditions = [
            'AND',
            ['TYPE' => 'COMPETENCE', 'VALUE' => 1],
            ['TYPE' => 'RELIGION', 'VALUE' => 2],
        ];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testAndModeFailsIfOneConditionFails(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(true);
        $personnage->method('hasReligionId')->willReturn(false);

        $conditions = [
            'AND',
            ['TYPE' => 'COMPETENCE', 'VALUE' => 1],
            ['TYPE' => 'RELIGION', 'VALUE' => 2],
        ];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── OR mode ───────────────────────────────────────────────────────────────

    public function testOrModeSucceedsIfEitherConditionMatches(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(false);
        $personnage->method('hasReligionId')->willReturn(true);

        $conditions = [
            'OR',
            ['TYPE' => 'COMPETENCE', 'VALUE' => 1],
            ['TYPE' => 'RELIGION', 'VALUE' => 2],
        ];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    public function testOrModeWithAllConditionsFailingReturnsFalse(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $personnage->method('hasCompetenceId')->willReturn(false);
        $personnage->method('hasReligionId')->willReturn(false);

        $conditions = [
            'OR',
            ['TYPE' => 'COMPETENCE', 'VALUE' => 1],
            ['TYPE' => 'RELIGION', 'VALUE' => 2],
        ];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Nested conditions ─────────────────────────────────────────────────────

    public function testNestedAndInsideOrIsValid(): void
    {
        // OR(  AND(classe=21, religion=12), AND(classe=21, religion=5) )
        $personnage = $this->makePersonnageWithClasse(21);
        $personnage->method('hasReligionId')->willReturnCallback(static fn (int $id): bool => 5 === $id);

        $conditions = [
            'OR',
            [
                'AND',
                ['TYPE' => 'CLASSE', 'VALUE' => 21],
                ['TYPE' => 'RELIGION', 'VALUE' => 12],
            ],
            [
                'AND',
                ['TYPE' => 'CLASSE', 'VALUE' => 21],
                ['TYPE' => 'RELIGION', 'VALUE' => 5],
            ],
        ];

        static::assertTrue($this->service->isValidConditions($personnage, $conditions));
    }

    // ── Unknown TYPE ──────────────────────────────────────────────────────────

    public function testUnknownTypeReturnsFalse(): void
    {
        $personnage = $this->createStub(Personnage::class);
        $conditions = [['TYPE' => 'UNKNOWN_TYPE', 'VALUE' => 1]];

        static::assertFalse($this->service->isValidConditions($personnage, $conditions));
    }

    // ── CompetenceFamily entity ───────────────────────────────────────────────

    public function testCompetenceFamilyEntityConditionMatchesById(): void
    {
        $family = $this->createStub(CompetenceFamily::class);
        $family->method('getId')->willReturn(42);
        $family->method('getCompetenceFamilyType')->willReturn(null);

        $conditions = [['TYPE' => 'COMPETENCE_FAMILLE', 'VALUE' => 42]];

        static::assertTrue($this->service->isValidConditions($family, $conditions));
    }

    public function testCompetenceFamilyEntityConditionMatchesByTypeValue(): void
    {
        $family = $this->createStub(CompetenceFamily::class);
        $family->method('getId')->willReturn(99);
        $family->method('getCompetenceFamilyType')->willReturn(CompetenceFamilyType::ALCHEMY);

        $conditions = [['TYPE' => 'COMPETENCE_FAMILLE', 'VALUE' => CompetenceFamilyType::ALCHEMY->value]];

        static::assertTrue($this->service->isValidConditions($family, $conditions));
    }
}
