<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Espece;
use App\Enum\EspeceType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Régression : `Espece::getType()` appelait `EspeceType::tryFrom($this->type)` sans
 * vérifier la nullité, alors que la colonne `type` est nullable et que `EspeceFactory`
 * (comme certaines espèces existantes) ne la renseigne pas — TypeError fatal (500) sur
 * la liste et le détail d'une espèce.
 */
#[Group('unit')]
class EspeceTest extends TestCase
{
    public function testGetTypeReturnsNullWhenTypeIsNull(): void
    {
        $espece = new Espece();

        static::assertNull($espece->getType());
    }

    public function testGetTypeReturnsEnumWhenTypeIsSet(): void
    {
        $espece = new Espece();
        $espece->setType(EspeceType::HUMANOID);

        static::assertSame(EspeceType::HUMANOID, $espece->getType());
    }

    public function testSetTypeAcceptsNull(): void
    {
        $espece = new Espece();
        $espece->setType(EspeceType::HUMANOID);
        $espece->setType(null);

        static::assertNull($espece->getType());
    }
}
