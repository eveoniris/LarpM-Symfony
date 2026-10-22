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
            $groupe->addTerritoire($this->fief());
        }

        return $groupe;
    }

    private function fief(): Territoire
    {
        $fief = new Territoire();
        $fief->setTerritoire(new Territoire());

        return $fief;
    }

    public function testSansFiefLeGroupeADesPlacesParDefaut(): void
    {
        self::assertSame(12, $this->groupeAvecFiefs(0)->getPlaceTotal());
    }

    public function testLePremierFiefDonneDejaDeuxPlaces(): void
    {
        self::assertSame(14, $this->groupeAvecFiefs(1)->getPlaceTotal());
    }

    public function testChaqueFiefAjouteDeuxPlaces(): void
    {
        self::assertSame(16, $this->groupeAvecFiefs(2)->getPlaceTotal());
        self::assertSame(20, $this->groupeAvecFiefs(4)->getPlaceTotal());
    }

    public function testLesPlacesParDefautVientDuGn(): void
    {
        $gn = (new Gn())->setPlacesParGroupe(10);

        self::assertSame(12, $this->groupeAvecFiefs(1)->getPlaceTotal($gn));
        self::assertSame(16, $this->groupeAvecFiefs(3)->getPlaceTotal($gn));
    }

    public function testLaLimiteManuelleDeLaSessionPrimeSurLeCalcul(): void
    {
        $gn = new Gn();
        $groupeGn = (new GroupeGn())
            ->setGroupe($this->groupeAvecFiefs(3))
            ->setGn($gn)
            ->setPlacesMax(30);
        $groupeGn->getGroupe()->addGroupeGn($groupeGn);

        self::assertSame(30, $groupeGn->getPlaceTotal());
        self::assertSame(18, $groupeGn->getGroupe()->getPlaceCalculee($groupeGn->getGn()));
        self::assertSame(30, $groupeGn->getGroupe()->getPlaceTotal($gn));
        self::assertSame(18, $groupeGn->getGroupe()->getPlaceTotal(new Gn()));
    }

    public function testUneValeurNulleOuNegativeRetablitLeCalculAutomatique(): void
    {
        $groupeGn = (new GroupeGn())
            ->setGroupe($this->groupeAvecFiefs(2))
            ->setGn(new Gn())
            ->setPlacesMax(0);

        self::assertNull($groupeGn->getPlacesMax());
        self::assertSame(16, $groupeGn->getPlaceTotal());

        $groupeGn->setPlacesMax(-3);
        self::assertNull($groupeGn->getPlacesMax());
    }

    public function testLaLimiteSousLeCalculSeDetecteParLaComparaisonDesPlaces(): void
    {
        $groupeGn = (new GroupeGn())
            ->setGroupe($this->groupeAvecFiefs(7))
            ->setGn(new Gn());
        $groupeGn->getGroupe()->addGroupeGn($groupeGn);

        self::assertSame($groupeGn->getGroupe()->getPlaceCalculee($groupeGn->getGn()), $groupeGn->getPlaceTotal());

        $groupeGn->setPlacesMax(10);
        self::assertLessThan($groupeGn->getGroupe()->getPlaceCalculee($groupeGn->getGn()), $groupeGn->getPlaceTotal());
        self::assertSame(26, $groupeGn->getGroupe()->getPlaceCalculee($groupeGn->getGn()));

        $groupeGn->setPlacesMax(null);
        self::assertSame(26, $groupeGn->getPlaceTotal());
    }

    public function testSeulsLesTerritoiresFinauxCompteCommeFiefs(): void
    {
        $nation = new Territoire();
        $region = (new Territoire())->setTerritoire($nation);
        $fiefDeRegion = (new Territoire())->setTerritoire($region);
        $fiefDeNation = (new Territoire())->setTerritoire($nation);
        $nation->addTerritoire($region);
        $nation->addTerritoire($fiefDeNation);
        $region->addTerritoire($fiefDeRegion);

        self::assertFalse($nation->isFief());
        self::assertFalse($region->isFief());
        self::assertTrue($fiefDeRegion->isFief());
        self::assertTrue($fiefDeNation->isFief());

        $groupe = new Groupe();
        foreach ([$nation, $region, $fiefDeRegion, $fiefDeNation] as $territoire) {
            $groupe->addTerritoire($territoire);
        }

        self::assertCount(2, $groupe->getFiefs());
        self::assertSame(16, $groupe->getPlaceCalculee());
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
