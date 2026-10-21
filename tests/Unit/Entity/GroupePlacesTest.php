<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Billet;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Territoire;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use PHPUnit\Framework\TestCase;

#[TestGroup('unit')]
class GroupePlacesTest extends TestCase
{
    private function groupeAvecFiefs(int $nombreFiefs): Groupe
    {
        $groupe = new Groupe();
        for ($i = 0; $i < $nombreFiefs; ++$i) {
            $groupe->addTerritoire(new Territoire());
        }

        return $groupe;
    }

    public function testSansFiefLeGroupeADesPlacesParDefaut(): void
    {
        self::assertSame(12, $this->groupeAvecFiefs(0)->getPlaceTotal());
    }

    public function testLePremierFiefNeDonnePasDePlaceSupplementaire(): void
    {
        self::assertSame(12, $this->groupeAvecFiefs(1)->getPlaceTotal());
    }

    public function testChaqueFiefAuDelaDuPremierAjouteDeuxPlaces(): void
    {
        self::assertSame(14, $this->groupeAvecFiefs(2)->getPlaceTotal());
        self::assertSame(18, $this->groupeAvecFiefs(4)->getPlaceTotal());
    }

    public function testLesPlacesParDefautVientDuGn(): void
    {
        $gn = (new Gn())->setPlacesParGroupe(10);

        self::assertSame(10, $this->groupeAvecFiefs(1)->getPlaceTotal($gn));
        self::assertSame(14, $this->groupeAvecFiefs(3)->getPlaceTotal($gn));
    }

    public function testLaValeurManuelleDuGroupePrimeSurLeCalcul(): void
    {
        $groupe = $this->groupeAvecFiefs(3)->setPlacesMax(30);

        self::assertSame(30, $groupe->getPlaceTotal());
        self::assertSame(16, $groupe->getPlaceCalculee());
    }

    public function testUneValeurNulleOuNegativeRetablitLeCalculAutomatique(): void
    {
        $groupe = $this->groupeAvecFiefs(2)->setPlacesMax(0);

        self::assertNull($groupe->getPlacesMax());
        self::assertSame(14, $groupe->getPlaceTotal());

        $groupe->setPlacesMax(-3);
        self::assertNull($groupe->getPlacesMax());
    }

    public function testLesAidesDeCampNeComptentPasDansLesMembres(): void
    {
        $groupeGn = (new GroupeGn())
            ->setGroupe($this->groupeAvecFiefs(0))
            ->setGn(new Gn());

        $billetJoueur = (new Billet())->setLabel('Inscription joueur');
        $billetAideDeCamp = (new Billet())
            ->setLabel('Inscription -Aide de Camp')
            ->setCompteDansPlaces(false);

        $groupeGn->addParticipant((new Participant())->setBillet($billetJoueur));
        $groupeGn->addParticipant((new Participant())->setBillet($billetJoueur));
        $groupeGn->addParticipant((new Participant())->setBillet($billetAideDeCamp));
        $groupeGn->addParticipant(new Participant());

        self::assertSame(3, $groupeGn->getNombreMembres());
        self::assertSame(1, $groupeGn->getNombreAidesDeCamp());
        self::assertSame(9, $groupeGn->getPlaceLibre());
    }
}
