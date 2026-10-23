<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\DepouillementEtape;
use App\Enum\DepouillementType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class DepouillementEtapeTest extends TestCase
{
    public function testLEnveloppeDeJoueurAHuitEtapes(): void
    {
        static::assertCount(8, DepouillementEtape::pourType(DepouillementType::JOUEUR));
        static::assertSame(1, DepouillementEtape::IDENTIFICATION->position(DepouillementType::JOUEUR));
        static::assertSame(5, DepouillementEtape::ACTIONS->position(DepouillementType::JOUEUR));
        static::assertSame(8, DepouillementEtape::RECAPITULATIF->position(DepouillementType::JOUEUR));
    }

    public function testLEnveloppeDeGroupeNAPasLesEtapesDesJoueurs(): void
    {
        $etapes = DepouillementEtape::pourType(DepouillementType::GROUPE);

        static::assertCount(6, $etapes);
        static::assertNotContains(DepouillementEtape::ACTIONS, $etapes);
        static::assertNotContains(DepouillementEtape::CONCEPTIONS, $etapes);
        static::assertSame(6, DepouillementEtape::RECAPITULATIF->position(DepouillementType::GROUPE));
        static::assertSame(0, DepouillementEtape::ACTIONS->position(DepouillementType::GROUPE));
    }

    public function testNavigationPrecedenteEtSuivantePourUnJoueur(): void
    {
        $joueur = DepouillementType::JOUEUR;

        static::assertNull(DepouillementEtape::IDENTIFICATION->precedente($joueur));
        static::assertSame(DepouillementEtape::PHOTOS, DepouillementEtape::IDENTIFICATION->suivante($joueur));
        static::assertSame(DepouillementEtape::CONTENU, DepouillementEtape::ACTIONS->precedente($joueur));
        static::assertSame(DepouillementEtape::CONCEPTIONS, DepouillementEtape::ACTIONS->suivante($joueur));
        static::assertSame(DepouillementEtape::CONCEPTIONS, DepouillementEtape::REMARQUES->precedente($joueur));
        static::assertNull(DepouillementEtape::RECAPITULATIF->suivante($joueur));
    }

    public function testNavigationSauteLesEtapesDesJoueursPourUnGroupe(): void
    {
        $groupe = DepouillementType::GROUPE;

        static::assertSame(DepouillementEtape::REMARQUES, DepouillementEtape::CONTENU->suivante($groupe));
        static::assertSame(DepouillementEtape::CONTENU, DepouillementEtape::REMARQUES->precedente($groupe));
    }
}
