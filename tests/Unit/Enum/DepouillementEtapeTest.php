<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\DepouillementEtape;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class DepouillementEtapeTest extends TestCase
{
    public function testPositionsEtTotal(): void
    {
        static::assertSame(1, DepouillementEtape::IDENTIFICATION->position());
        static::assertSame(5, DepouillementEtape::RECAPITULATIF->position());
        static::assertSame(5, DepouillementEtape::total());
    }

    public function testNavigationPrecedenteEtSuivante(): void
    {
        static::assertNull(DepouillementEtape::IDENTIFICATION->precedente());
        static::assertSame(DepouillementEtape::PHOTOS, DepouillementEtape::IDENTIFICATION->suivante());
        static::assertSame(DepouillementEtape::CONTENU, DepouillementEtape::REMARQUES->precedente());
        static::assertNull(DepouillementEtape::RECAPITULATIF->suivante());
    }
}
