<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\LevelType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class LevelTypeTest extends TestCase
{
    public function testGetFromIndexReturnsCorrectValues(): void
    {
        static::assertSame(LevelType::APPRENTICE, LevelType::getFromIndex(1));
        static::assertSame(LevelType::INITIATED, LevelType::getFromIndex(2));
        static::assertSame(LevelType::EXPERT, LevelType::getFromIndex(3));
        static::assertSame(LevelType::MASTER, LevelType::getFromIndex(4));
        static::assertSame(LevelType::GRAND_MASTER, LevelType::getFromIndex(5));
    }

    public function testGetFromIndexReturnsNullForOutOfRange(): void
    {
        static::assertNull(LevelType::getFromIndex(0));
        static::assertNull(LevelType::getFromIndex(6));
        static::assertNull(LevelType::getFromIndex(-1));
    }

    /**
     * Documents that getId() and getIndex() return identical values for all cases.
     * This is intentional but potentially confusing - see CLAUDE.md known tech debt #9.
     */
    public function testGetIdAndGetIndexAreIdenticalForAllCases(): void
    {
        foreach (LevelType::cases() as $case) {
            static::assertSame($case->getId(), $case->getIndex(), "getId() and getIndex() differ for {$case->name}");
        }
    }

    public function testGetIndexValues(): void
    {
        static::assertSame(1, LevelType::APPRENTICE->getIndex());
        static::assertSame(2, LevelType::INITIATED->getIndex());
        static::assertSame(3, LevelType::EXPERT->getIndex());
        static::assertSame(4, LevelType::MASTER->getIndex());
        static::assertSame(5, LevelType::GRAND_MASTER->getIndex());
    }

    public function testTryFromOlderFrenchLabels(): void
    {
        static::assertSame(LevelType::APPRENTICE, LevelType::tryFromOlder('Apprenti'));
        static::assertSame(LevelType::INITIATED, LevelType::tryFromOlder('Initié'));
        static::assertSame(LevelType::EXPERT, LevelType::tryFromOlder('Expert'));
        static::assertSame(LevelType::MASTER, LevelType::tryFromOlder('Maitre'));
        static::assertSame(LevelType::MASTER, LevelType::tryFromOlder('Maître'));
        static::assertSame(LevelType::GRAND_MASTER, LevelType::tryFromOlder('Grand Maitre'));
        static::assertSame(LevelType::GRAND_MASTER, LevelType::tryFromOlder('Grand Maître'));
    }

    public function testTryFromOlderReturnsNullForUnknown(): void
    {
        static::assertNull(LevelType::tryFromOlder('unknown'));
        static::assertNull(LevelType::tryFromOlder(''));
        static::assertNull(LevelType::tryFromOlder('Level 1'));
    }

    public function testGetFromLabelResolvesEnglishValues(): void
    {
        static::assertSame(LevelType::APPRENTICE, LevelType::getFromLabel('Apprentice'));
        static::assertSame(LevelType::MASTER, LevelType::getFromLabel('Master'));
        static::assertSame(LevelType::GRAND_MASTER, LevelType::getFromLabel('Grand master'));
    }

    public function testGetFromLabelFallsBackToFrench(): void
    {
        static::assertSame(LevelType::APPRENTICE, LevelType::getFromLabel('Apprenti'));
        static::assertSame(LevelType::MASTER, LevelType::getFromLabel('Maître'));
    }
}
