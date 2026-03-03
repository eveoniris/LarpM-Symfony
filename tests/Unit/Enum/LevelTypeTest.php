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
        self::assertSame(LevelType::APPRENTICE, LevelType::getFromIndex(1));
        self::assertSame(LevelType::INITIATED, LevelType::getFromIndex(2));
        self::assertSame(LevelType::EXPERT, LevelType::getFromIndex(3));
        self::assertSame(LevelType::MASTER, LevelType::getFromIndex(4));
        self::assertSame(LevelType::GRAND_MASTER, LevelType::getFromIndex(5));
    }

    public function testGetFromIndexReturnsNullForOutOfRange(): void
    {
        self::assertNull(LevelType::getFromIndex(0));
        self::assertNull(LevelType::getFromIndex(6));
        self::assertNull(LevelType::getFromIndex(-1));
    }

    /**
     * Documents that getId() and getIndex() return identical values for all cases.
     * This is intentional but potentially confusing — see CLAUDE.md known tech debt #9.
     */
    public function testGetIdAndGetIndexAreIdenticalForAllCases(): void
    {
        foreach (LevelType::cases() as $case) {
            self::assertSame(
                $case->getId(),
                $case->getIndex(),
                "getId() and getIndex() differ for {$case->name}"
            );
        }
    }

    public function testGetIndexValues(): void
    {
        self::assertSame(1, LevelType::APPRENTICE->getIndex());
        self::assertSame(2, LevelType::INITIATED->getIndex());
        self::assertSame(3, LevelType::EXPERT->getIndex());
        self::assertSame(4, LevelType::MASTER->getIndex());
        self::assertSame(5, LevelType::GRAND_MASTER->getIndex());
    }

    public function testTryFromOlderFrenchLabels(): void
    {
        self::assertSame(LevelType::APPRENTICE, LevelType::tryFromOlder('Apprenti'));
        self::assertSame(LevelType::INITIATED, LevelType::tryFromOlder('Initié'));
        self::assertSame(LevelType::EXPERT, LevelType::tryFromOlder('Expert'));
        self::assertSame(LevelType::MASTER, LevelType::tryFromOlder('Maitre'));
        self::assertSame(LevelType::MASTER, LevelType::tryFromOlder('Maître'));
        self::assertSame(LevelType::GRAND_MASTER, LevelType::tryFromOlder('Grand Maitre'));
        self::assertSame(LevelType::GRAND_MASTER, LevelType::tryFromOlder('Grand Maître'));
    }

    public function testTryFromOlderReturnsNullForUnknown(): void
    {
        self::assertNull(LevelType::tryFromOlder('unknown'));
        self::assertNull(LevelType::tryFromOlder(''));
        self::assertNull(LevelType::tryFromOlder('Level 1'));
    }

    public function testGetFromLabelResolvesEnglishValues(): void
    {
        self::assertSame(LevelType::APPRENTICE, LevelType::getFromLabel('Apprentice'));
        self::assertSame(LevelType::MASTER, LevelType::getFromLabel('Master'));
        self::assertSame(LevelType::GRAND_MASTER, LevelType::getFromLabel('Grand master'));
    }

    public function testGetFromLabelFallsBackToFrench(): void
    {
        self::assertSame(LevelType::APPRENTICE, LevelType::getFromLabel('Apprenti'));
        self::assertSame(LevelType::MASTER, LevelType::getFromLabel('Maître'));
    }
}
