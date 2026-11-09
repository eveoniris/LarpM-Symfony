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
    public function testLEnveloppeDeJoueurANeufEtapes(): void
    {
        static::assertCount(9, DepouillementEtape::pourType(DepouillementType::JOUEUR));
        static::assertSame(1, DepouillementEtape::IDENTIFICATION->position(DepouillementType::JOUEUR));
        static::assertSame(5, DepouillementEtape::ACTIONS->position(DepouillementType::JOUEUR));
        static::assertSame(6, DepouillementEtape::DECLARATION->position(DepouillementType::JOUEUR));
        static::assertSame(9, DepouillementEtape::RECAPITULATIF->position(DepouillementType::JOUEUR));
    }

    public function testLEnveloppeDeGroupeNAPasLesEtapesDesJoueurs(): void
    {
        $etapes = DepouillementEtape::pourType(DepouillementType::GROUPE);

        static::assertCount(6, $etapes);
        static::assertNotContains(DepouillementEtape::ACTIONS, $etapes);
        static::assertNotContains(DepouillementEtape::CONCEPTIONS, $etapes);
        static::assertNotContains(DepouillementEtape::DECLARATION, $etapes);
        static::assertSame(6, DepouillementEtape::RECAPITULATIF->position(DepouillementType::GROUPE));
        static::assertSame(0, DepouillementEtape::ACTIONS->position(DepouillementType::GROUPE));
    }

    public function testNavigationPrecedenteEtSuivantePourUnJoueur(): void
    {
        $joueur = DepouillementType::JOUEUR;

        static::assertNull(DepouillementEtape::IDENTIFICATION->precedente($joueur));
        static::assertSame(DepouillementEtape::PHOTOS, DepouillementEtape::IDENTIFICATION->suivante($joueur));
        static::assertSame(DepouillementEtape::CONTENU, DepouillementEtape::ACTIONS->precedente($joueur));
        static::assertSame(DepouillementEtape::DECLARATION, DepouillementEtape::ACTIONS->suivante($joueur));
        static::assertSame(DepouillementEtape::DECLARATION, DepouillementEtape::CONCEPTIONS->precedente($joueur));
        static::assertSame(DepouillementEtape::CONCEPTIONS, DepouillementEtape::REMARQUES->precedente($joueur));
        static::assertNull(DepouillementEtape::RECAPITULATIF->suivante($joueur));
    }

    public function testLeDeclarantNeVoitQueSesEtapes(): void
    {
        $joueur = DepouillementType::JOUEUR;
        $etapes = DepouillementEtape::pourType($joueur, declarant: true);

        static::assertSame([DepouillementEtape::DECLARATION, DepouillementEtape::CONCEPTIONS, DepouillementEtape::REMARQUES, DepouillementEtape::RECAPITULATIF], $etapes);
        static::assertSame(1, DepouillementEtape::DECLARATION->position($joueur, declarant: true));
        static::assertSame(0, DepouillementEtape::CONTENU->position($joueur, declarant: true));
        static::assertNull(DepouillementEtape::DECLARATION->precedente($joueur, declarant: true));
        static::assertSame(DepouillementEtape::CONCEPTIONS, DepouillementEtape::DECLARATION->suivante($joueur, declarant: true));
    }

    public function testNavigationSauteLesEtapesDesJoueursPourUnGroupe(): void
    {
        $groupe = DepouillementType::GROUPE;

        static::assertSame(DepouillementEtape::REMARQUES, DepouillementEtape::CONTENU->suivante($groupe));
        static::assertSame(DepouillementEtape::CONTENU, DepouillementEtape::REMARQUES->precedente($groupe));
    }
}
