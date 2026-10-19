<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Langue;
use App\Entity\Personnage;
use App\Enum\LangueSourceType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class PersonnageLangueDedoublonnageTest extends TestCase
{
    private function langue(int $id): Langue
    {
        return (new Langue())
            ->setId($id)
            ->setLabel('Langue ' . $id);
    }

    public function testPriorityOrder(): void
    {
        $order = ['ORIGINE', 'ORIGINE SECONDAIRE', 'GROUPE', 'LITTERATURE', 'Apprentissage maître', 'ADMIN'];
        $ranks = array_map(LangueSourceType::priorityOf(...), $order);

        static::assertSame($ranks, array_values(array_unique($ranks)));
        $sorted = $ranks;
        sort($sorted);
        static::assertSame($sorted, $ranks);
        static::assertSame(LangueSourceType::priorityOf('ORIGINE'), LangueSourceType::priorityOf('ORIGINE et GROUPE'));
    }

    public function testAddLangueSourceRefusesLowerOrEqualPriorityDuplicate(): void
    {
        $personnage = new Personnage();
        $langue = $this->langue(1);

        static::assertNotNull($personnage->addLangueSource($langue, 'ORIGINE'));
        static::assertNull($personnage->addLangueSource($langue, 'GROUPE'));
        static::assertNull($personnage->addLangueSource($langue, 'ORIGINE'));
        static::assertCount(1, $personnage->getPersonnageLangues());
    }

    public function testAddLangueSourceRaisesSourceOfExistingRow(): void
    {
        $personnage = new Personnage();
        $langue = $this->langue(1);

        $personnage->addLangueSource($langue, 'ADMIN');
        $row = $personnage->addLangueSource($langue, 'LITTERATURE', 'TAG');

        static::assertCount(1, $personnage->getPersonnageLangues());
        static::assertSame('LITTERATURE', $row->getSource());
        static::assertSame('TAG', $row->getTriggerTag());
    }

    public function testIsKnownLanguageComparesById(): void
    {
        $personnage = new Personnage();
        $personnage->addLangueSource($this->langue(5), 'ORIGINE');

        static::assertTrue($personnage->isKnownLanguage($this->langue(5)));
        static::assertFalse($personnage->isKnownLanguage($this->langue(6)));
        static::assertFalse($personnage->isKnownLanguage(null));
    }
}
