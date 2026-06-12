<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Construction;
use App\Entity\Territoire;
use App\Enum\TerritoireStatut;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class TerritoireWealthTest extends TestCase
{
    private function makeTerritoire(int $tresor, TerritoireStatut $statut = TerritoireStatut::STABLE): Territoire
    {
        $t = new Territoire();
        $t->setTresor($tresor);
        $t->setStatut($statut);

        return $t;
    }

    private function makeConstruction(int $id, int $defense = 0): Construction
    {
        $c = $this->createStub(Construction::class);
        $c->method('getId')->willReturn($id);
        $c->method('getDefense')->willReturn($defense);
        $c->method('addTerritoire')->willReturnSelf();

        return $c;
    }

    // ── getRichesse() ──────────────────────────────────────────────────────────

    public function testRichesseStableTerritoireReturnsTresor(): void
    {
        $t = $this->makeTerritoire(100, TerritoireStatut::STABLE);

        static::assertSame(100, $t->getRichesse());
    }

    public function testRichesseUnstableTerritoireIsHalved(): void
    {
        $t = $this->makeTerritoire(100, TerritoireStatut::INSTABLE);

        static::assertSame(50.0, $t->getRichesse());
    }

    public function testRichesseUnstableWithOddTresorCeilsUp(): void
    {
        $t = $this->makeTerritoire(101, TerritoireStatut::INSTABLE);

        // ceil(101 / 2) = 51
        static::assertSame(51.0, $t->getRichesse());
    }

    public function testRichesseUnstableWithConstructionRevenueBeforeHalving(): void
    {
        $t = $this->makeTerritoire(100, TerritoireStatut::INSTABLE);
        // Construction id=6 = Comptoir commercial (+5)
        $t->addConstruction($this->makeConstruction(6));

        // (100 + 5) / 2 = 52.5 → ceil = 53
        static::assertSame(53.0, $t->getRichesse());
    }

    public function testRichesseStableIgnoresConstructionRevenue(): void
    {
        $t = $this->makeTerritoire(100, TerritoireStatut::STABLE);
        $t->addConstruction($this->makeConstruction(6)); // Comptoir +5 (ignored for stable)

        // Stable returns $this->tresor directly, constructions NOT added
        static::assertSame(100, $t->getRichesse());
    }

    public function testRichesseUnstableAccumulatesMultipleConstructions(): void
    {
        $t = $this->makeTerritoire(50, TerritoireStatut::INSTABLE);
        $t->addConstruction($this->makeConstruction(6)); // +5
        $t->addConstruction($this->makeConstruction(23)); // +10
        $t->addConstruction($this->makeConstruction(10)); // +5

        // (50 + 5 + 10 + 5) / 2 = 35
        static::assertSame(35.0, $t->getRichesse());
    }

    // ── getDefense() ──────────────────────────────────────────────────────────

    public function testDefenseZeroWhenNoResistanceAndNoConstructions(): void
    {
        $t = $this->makeTerritoire(0);
        $t->setResistance(0);

        static::assertSame(0, $t->getDefense());
    }

    public function testDefenseIncludesResistance(): void
    {
        $t = $this->makeTerritoire(0);
        $t->setResistance(15);

        static::assertSame(15, $t->getDefense());
    }

    public function testDefenseIncludesConstructionDefense(): void
    {
        $t = $this->makeTerritoire(0);
        $t->setResistance(10);
        $t->addConstruction($this->makeConstruction(99, 5));
        $t->addConstruction($this->makeConstruction(100, 3));

        static::assertSame(18, $t->getDefense());
    }

    public function testDefenseZeroResistanceNotAddedWhenItIsZero(): void
    {
        $t = $this->makeTerritoire(0);
        $t->setResistance(0);
        $t->addConstruction($this->makeConstruction(99, 7));

        // 0 resistance is explicitly excluded (0 !== 0 is false → skipped)
        static::assertSame(7, $t->getDefense());
    }

    // ── isStable() ────────────────────────────────────────────────────────────

    public function testIsStableReturnsTrueForStableStatut(): void
    {
        $t = $this->makeTerritoire(0, TerritoireStatut::STABLE);

        static::assertTrue($t->isStable());
    }

    public function testIsStableReturnsFalseForInstableStatut(): void
    {
        $t = $this->makeTerritoire(0, TerritoireStatut::INSTABLE);

        static::assertFalse($t->isStable());
    }

    public function testIsStableTrueByDefaultWhenStatutIsNull(): void
    {
        // BaseTerritoire::getStatut() returns STABLE when $this->statut is falsy
        $t = new Territoire();
        $t->setTresor(0);
        // No setStatut() call

        static::assertTrue($t->isStable());
    }
}
