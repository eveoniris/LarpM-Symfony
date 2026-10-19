<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Bonus;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Entity\Territoire;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use DateTime;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Une décision enregistrée (personnage_bonus, source ORIGINE) l'emporte sur l'ancien calcul et reste figée.
 */
#[Group('unit')]
class PersonnageOrigineBonusDecisionTest extends TestCase
{
    private function bonus(int $id, BonusPeriode $periode = BonusPeriode::NATIVE): Bonus
    {
        return (new Bonus())
            ->setId($id)
            ->setType(BonusType::XP)
            ->setPeriode($periode);
    }

    private function row(Bonus $bonus, Status $status): PersonnageBonus
    {
        return (new PersonnageBonus())
            ->setBonus($bonus)
            ->setSource(PersonnageBonusSource::ORIGINE)
            ->setStatus($status);
    }

    /** Personnage d'origine 1 dont le premier groupe a l'origine $origineGroupe (ancien calcul). */
    private function personnage(int $origineGroupe): Personnage
    {
        $gn = (new Gn())
            ->setId(1)
            ->setLabel('GN1')
            ->setDateDebut(new DateTime('2020-01-01'));
        $groupeGn = (new GroupeGn())
            ->setGn($gn)
            ->setGroupe((new Groupe())->setTerritoire((new Territoire())->setId($origineGroupe)));

        return (new Personnage())
            ->setTerritoire((new Territoire())->setId(1))
            ->addParticipant(
                (new Participant())
                    ->setId(1)
                    ->setGn($gn)
                    ->setGroupeGn($groupeGn),
            );
    }

    public function testSansDecisionOnRetombeSurLAncienCalcul(): void
    {
        static::assertTrue($this->personnage(1)->isOrigineBonusActive());
        static::assertFalse($this->personnage(2)->isOrigineBonusActive());
    }

    public function testDecisionAcquiseEmporteSurLAncienCalcul(): void
    {
        $personnage = $this->personnage(2)->addPersonnageBonus($this->row($this->bonus(10), Status::ACTIVE));

        static::assertTrue($personnage->isOrigineBonusActive());
        static::assertTrue($personnage->isOrigineBonusActive($this->bonus(10)));
    }

    public function testDecisionRefuseeEmporteSurLAncienCalcul(): void
    {
        $personnage = $this->personnage(1)->addPersonnageBonus($this->row($this->bonus(10), Status::DELETED));

        static::assertFalse($personnage->isOrigineBonusActive());
        static::assertFalse($personnage->isOrigineBonusActive($this->bonus(10)));
    }

    public function testUnBonusNativeAjouteApresLaDecisionNEstPasAcquis(): void
    {
        $personnage = $this->personnage(1)->addPersonnageBonus($this->row($this->bonus(10), Status::ACTIVE));

        static::assertTrue($personnage->isOrigineBonusActive($this->bonus(10)));
        static::assertFalse($personnage->isOrigineBonusActive($this->bonus(11)));
    }

    public function testUnBonusNonNativeSuitLaDecisionGlobale(): void
    {
        $acquis = $this->personnage(1)->addPersonnageBonus($this->row($this->bonus(10), Status::ACTIVE));
        $refuse = $this->personnage(1)->addPersonnageBonus($this->row($this->bonus(10), Status::DELETED));
        $constant = $this->bonus(12, BonusPeriode::CONSTANT);

        static::assertTrue($acquis->isOrigineBonusActive($constant));
        static::assertFalse($refuse->isOrigineBonusActive($constant));
    }

    public function testLesBonusDEspeceNeSontPasUneDecisionDOrigine(): void
    {
        $espece = (new PersonnageBonus())
            ->setBonus($this->bonus(10))
            ->setSource(PersonnageBonusSource::ESPECE)
            ->setStatus(Status::ACTIVE);
        $personnage = $this->personnage(2)->addPersonnageBonus($espece);

        static::assertSame([], $personnage->getOrigineBonusDecisions());
        static::assertFalse($personnage->isOrigineBonusActive(), 'ancien calcul : le groupe a une autre origine');
    }
}
