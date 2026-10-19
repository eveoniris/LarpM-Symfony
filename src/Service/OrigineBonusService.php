<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Bonus;
use App\Entity\OrigineBonus;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Entity\Territoire;
use App\Enum\BonusPeriode;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Bonus d'origine : décidé une seule fois, à la première participation du personnage, puis figé dans personnage_bonus.
 *
 * Le bonus est acquis si le groupe de la première participation possède (fiefs) le territoire d'origine du personnage.
 * Sinon le personnage reçoit la langue du groupe et pas le bonus. Sans groupe, la décision est différée.
 * Une décision refusée est enregistrée (statut DELETED) pour ne jamais être réévaluée avec un état de fiefs ultérieur.
 */
class OrigineBonusService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * true : un fief du groupe de la première participation est l'origine du personnage ;
     * false : aucun ; null : décision impossible (pas de participation, pas de groupe ou pas d'origine).
     */
    public function isEligible(Personnage $personnage): ?bool
    {
        $origine = $personnage->getOrigine();
        $groupe = $personnage->getFirstParticipantGnGroupe();
        if (null === $origine || null === $groupe) {
            return null;
        }

        return $groupe->getTerritoires()->exists(static fn (int $key, Territoire $fief): bool => $fief->getId() === $origine->getId());
    }

    /**
     * Une décision a déjà été prise (acquise ou refusée).
     */
    public function hasDecision(Personnage $personnage): bool
    {
        return [] !== $this->getOrigineRows($personnage);
    }

    /**
     * Le bonus d'origine est acquis (décision prise et positive).
     */
    public function isGranted(Personnage $personnage): bool
    {
        return array_any($this->getOrigineRows($personnage), static fn (PersonnageBonus $row): bool => $row->isValid());
    }

    /**
     * Prend la décision et l'enregistre. Idempotent : sans effet si une décision existe déjà ou si elle est différée.
     *
     * @return list<PersonnageBonus> lignes créées
     */
    public function grant(Personnage $personnage): array
    {
        if ($this->hasDecision($personnage)) {
            return [];
        }

        $eligible = $this->isEligible($personnage);
        $origine = $personnage->getOrigine();
        if (null === $eligible || null === $origine) {
            return [];
        }

        $created = [];
        foreach ($this->getNativeBonus($origine) as $bonus) {
            $row = (new PersonnageBonus())
                ->setBonus($bonus)
                ->setTerritoire($origine)
                ->setSource(PersonnageBonusSource::ORIGINE)
                ->setStatus($eligible ? Status::ACTIVE : Status::DELETED)
                ->setCreationDate(new DateTime());
            $personnage->addPersonnageBonus($row);
            $this->entityManager->persist($row);
            $created[] = $row;
        }

        return $created;
    }

    /**
     * Efface la décision (ex. changement d'origine du personnage) pour qu'elle puisse être reprise.
     */
    public function revoke(Personnage $personnage): void
    {
        foreach ($this->getOrigineRows($personnage) as $row) {
            $personnage->removePersonnageBonus($row);
            $this->entityManager->remove($row);
        }
    }

    /**
     * Bonus NATIVE valides portés par l'origine, sans doublon.
     *
     * @return array<int, Bonus>
     */
    public function getNativeBonus(Territoire $origine): array
    {
        $valides = $origine->getOriginesBonus()->filter(static fn (OrigineBonus $origineBonus): bool => $origineBonus->isValid() && BonusPeriode::NATIVE === $origineBonus->getBonus()?->getPeriode());

        $bonuses = [];
        foreach ($valides as $origineBonus) {
            $bonuses[$origineBonus->getBonus()->getId()] = $origineBonus->getBonus();
        }

        return $bonuses;
    }

    /**
     * @return list<PersonnageBonus>
     */
    private function getOrigineRows(Personnage $personnage): array
    {
        return $personnage->getOrigineBonusDecisions();
    }
}
