<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Entity\Langue;
use App\Entity\Personnage;
use App\Enum\LangueSourceType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Source de langue « parents » : la langue transmise à un héritier né en jeu.
 */
#[Group('unit')]
class LangueSourceTypeTest extends TestCase
{
    public function testLaSourceParentsAUnLibelleEtUneValeurStable(): void
    {
        static::assertSame('PARENTS', LangueSourceType::PARENTS->value);
        static::assertSame('Parents', LangueSourceType::PARENTS->getLabel());
        static::assertArrayHasKey('PARENTS', LangueSourceType::getLabels());
    }

    public function testLaLangueDesParentsPrimeSurLeGroupeEtLaLitteratureSansDepasserLOrigine(): void
    {
        $parents = LangueSourceType::priorityOf('PARENTS');

        static::assertGreaterThan(LangueSourceType::priorityOf('ORIGINE'), $parents);
        static::assertLessThan(LangueSourceType::priorityOf('GROUPE'), $parents);
        static::assertLessThan(LangueSourceType::priorityOf('LITTERATURE'), $parents);
        static::assertSame(LangueSourceType::priorityOf('ORIGINE SECONDAIRE'), $parents);
    }

    public function testUneLangueDesParentsEstAjouteeUneSeuleFoisEtUneOrigineLaRemplace(): void
    {
        $personnage = new Personnage();
        $langue = (new Langue())
            ->setId(1)
            ->setLabel('Langue 1');

        static::assertNotNull($personnage->addLangueSource($langue, LangueSourceType::PARENTS));
        static::assertNull($personnage->addLangueSource($langue, LangueSourceType::GROUPE));
        static::assertNotNull($personnage->addLangueSource($langue, LangueSourceType::ORIGINE));
        static::assertCount(1, $personnage->getPersonnageLangues());
        static::assertSame('ORIGINE', $personnage->getPersonnageLangues()->first()->getSource());
    }
}
