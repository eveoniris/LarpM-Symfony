<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Groupe;
use App\Entity\Territoire;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use PHPUnit\Framework\TestCase;

#[TestGroup('unit')]
class TerritoireGroupesPjTest extends TestCase
{
    public function testReturnsUniqueGroupNamesFromDescendants(): void
    {
        $root = new Territoire();
        $child = new Territoire();
        $root->addTerritoire($child);
        $root->setGroupe((new Groupe())->setNom('Groupe PJ'));
        $child->setGroupe((new Groupe())->setNom('Groupe PJ'));

        self::assertSame(['Groupe PJ'], $root->getGroupesPj());
    }

    public function testDoesNotRecurseForeverWhenTerritoryRelationsContainCycle(): void
    {
        $first = new Territoire();
        $second = new Territoire();
        $first->addTerritoire($second);
        $second->addTerritoire($first);

        self::assertSame([], $first->getGroupesPj());
    }
}
