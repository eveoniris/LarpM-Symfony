<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bonus;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\OrigineBonus;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\Status;
use DateTime;

/**
 * Fabriques en mémoire partagées par les tests du bonus d'origine.
 */
final class OrigineBonusFixtures
{
    private int $nextId = 100;

    public function territoire(int $id): Territoire
    {
        return (new Territoire())->setId($id);
    }

    public function bonus(BonusPeriode $periode = BonusPeriode::NATIVE): Bonus
    {
        return (new Bonus())
            ->setId($this->nextId++)
            ->setType(BonusType::XP)
            ->setValeur(2)
            ->setPeriode($periode);
    }

    public function addBonus(Territoire $origine, Bonus $bonus, Status $status = Status::ACTIVE): void
    {
        $origine->addOrigineBonus(
            (new OrigineBonus())
                ->setBonus($bonus)
                ->setTerritoire($origine)
                ->setStatus($status),
        );
    }

    /**
     * @param list<Territoire> $fiefs fiefs possédés par le groupe
     */
    public function participant(int $id, string $date, ?Territoire $territoireGroupe, array $fiefs): Participant
    {
        $gn = (new Gn())
            ->setId($id)
            ->setLabel('GN' . $id)
            ->setDateDebut(new DateTime($date));
        $groupe = (new Groupe())->setTerritoire($territoireGroupe);
        foreach ($fiefs as $fief) {
            $groupe->addTerritoire($fief);
            $fief->setGroupe($groupe);
        }

        return (new Participant())
            ->setId($id)
            ->setGn($gn)
            ->setGroupeGn(
                (new GroupeGn())
                    ->setGn($gn)
                    ->setGroupe($groupe),
            );
    }

    public function personnageSansGroupe(Territoire $origine): Personnage
    {
        $gn = (new Gn())
            ->setId(1)
            ->setLabel('GN1')
            ->setDateDebut(new DateTime('2020-01-01'));

        return (new Personnage())
            ->setTerritoire($origine)
            ->addParticipant(
                (new Participant())
                    ->setId(1)
                    ->setGn($gn),
            );
    }

    /**
     * Personnage d'origine $origine, dont la première participation (2020) a un groupe possédant $fiefs.
     *
     * @param list<Territoire> $fiefs
     */
    public function personnage(Territoire $origine, array $fiefs): Personnage
    {
        return (new Personnage())
            ->setTerritoire($origine)
            ->addParticipant($this->participant(1, '2020-01-01', null, $fiefs));
    }
}
