<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Territoire;
use DateTime;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Le bonus d'origine n'est acquis que si le premier groupe de jeu (participation la plus ancienne)
 * a la même origine que le personnage.
 */
#[Group('unit')]
class PersonnageOrigineBonusTest extends TestCase
{
    private function territoire(int $id): Territoire
    {
        return (new Territoire())->setId($id);
    }

    private function participant(int $id, string $label, string $date, ?Territoire $groupeOrigine): Participant
    {
        $gn = (new Gn())
            ->setId($id)
            ->setLabel($label)
            ->setDateDebut(new DateTime($date));
        $participant = (new Participant())
            ->setId($id)
            ->setGn($gn);

        if ($groupeOrigine) {
            $groupe = (new Groupe())->setTerritoire($groupeOrigine);
            $groupeGn = (new GroupeGn())
                ->setGn($gn)
                ->setGroupe($groupe);
            $participant->setGroupeGn($groupeGn);
        }

        return $participant;
    }

    private function personnage(Territoire $origine): Personnage
    {
        return (new Personnage())->setTerritoire($origine);
    }

    public function testSansParticipationPasDeBonus(): void
    {
        self::assertFalse($this->personnage($this->territoire(1))->isOrigineBonusActive());
    }

    public function testPremierGroupeMemeOrigineBonusActif(): void
    {
        $origine = $this->territoire(1);
        $personnage = $this->personnage($origine)->addParticipant($this->participant(1, 'LH1', '2020-01-01', $origine));

        self::assertTrue($personnage->isOrigineBonusActive());
    }

    public function testPremierGroupeAutreOrigineBonusInactif(): void
    {
        $personnage = $this->personnage($this->territoire(1))
            ->addParticipant($this->participant(1, 'LH1', '2020-01-01', $this->territoire(2)));

        self::assertFalse($personnage->isOrigineBonusActive());
    }

    public function testSeulLePlusAncienGnCompteQuelqueSoitLOrdreDeLaCollection(): void
    {
        $origine = $this->territoire(1);
        // Le plus récent est ajouté en premier et porte une origine identique : il ne doit pas compter.
        $personnage = $this
            ->personnage($origine)
            ->addParticipant($this->participant(2, 'LH2', '2021-01-01', $origine))
            ->addParticipant($this->participant(1, 'LH1', '2020-01-01', $this->territoire(2)));

        self::assertSame(1, $personnage->getFirstParticipant()->getId());
        self::assertFalse($personnage->isOrigineBonusActive());
    }

    public function testPremiereParticipationSansGroupeConserveLeBonus(): void
    {
        $personnage = $this->personnage($this->territoire(1))
            ->addParticipant($this->participant(1, 'LH1', '2020-01-01', null));

        self::assertTrue($personnage->isOrigineBonusActive());
    }
}
