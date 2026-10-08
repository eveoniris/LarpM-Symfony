<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Espece;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Enum\Status;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class EspeceBonusService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function applyEspeceBonuses(Personnage $personnage, Espece $espece): void
    {
        foreach ($espece->getEspeceBonus() as $especeBonus) {
            if (!$especeBonus->isValid()) {
                continue;
            }

            $personnageBonus = new PersonnageBonus();
            $personnageBonus
                ->setPersonnage($personnage)
                ->setBonus($especeBonus->getBonus())
                ->setEspece($espece)
                ->setStatus(Status::ACTIVE)
                ->setCreationDate(new DateTime());

            $this->entityManager->persist($personnageBonus);
        }
    }

    public function removeEspeceBonuses(Personnage $personnage, Espece $espece): void
    {
        foreach ($personnage->getPersonnageBonus() ?? [] as $personnageBonus) {
            if ($personnageBonus->getEspece() === $espece) {
                $this->entityManager->remove($personnageBonus);
            }
        }
    }
}
