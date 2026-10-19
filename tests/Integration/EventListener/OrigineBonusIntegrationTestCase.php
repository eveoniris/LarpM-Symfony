<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\OrigineBonus;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Entity\Territoire;
use App\Enum\BonusPeriode;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use App\Tests\Factory\BonusFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\TerritoireFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Fabriques en base pour les tests du bonus d'origine (DAMA annule chaque test).
 */
abstract class OrigineBonusIntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param array<string, mixed> $bonusAttributes surcharge des attributs du bonus NATIVE porté par l'origine
     */
    protected function origineAvecBonus(array $bonusAttributes = []): Territoire
    {
        $origine = TerritoireFactory::createOne();
        $bonus = BonusFactory::createOne($bonusAttributes + ['periode' => BonusPeriode::NATIVE->value]);
        $this->em->persist(
            (new OrigineBonus())
                ->setTerritoire($origine)
                ->setBonus($bonus)
                ->setStatus(Status::ACTIVE),
        );
        $this->em->flush();
        $this->em->refresh($origine);

        return $origine;
    }

    /**
     * @param list<Territoire> $fiefs fiefs possédés par le groupe
     */
    protected function groupeGn(array $fiefs, ?DateTime $date = null, ?Gn $gnExistant = null): GroupeGn
    {
        $gn = $gnExistant ?? GnFactory::createOne(['dateDebut' => $date ?? new DateTime('2020-01-01')]);
        $groupe = GroupeFactory::createOne();
        foreach ($fiefs as $fief) {
            $fief->setGroupe($groupe);
            $groupe->addTerritoire($fief);
        }

        $groupeGn = (new GroupeGn())
            ->setGn($gn)
            ->setGroupe($groupe);
        $this->em->persist($groupeGn);
        $this->em->flush();

        return $groupeGn;
    }

    protected function participant(Personnage $personnage, ?GroupeGn $groupeGn, ?DateTime $date = null): Participant
    {
        $participant = (new Participant())
            ->setGn($groupeGn?->getGn() ?? GnFactory::createOne(['dateDebut' => $date ?? new DateTime('2020-01-01')]))
            ->setPersonnage($personnage)
            ->setUser($personnage->getUser());
        if ($groupeGn) {
            $participant->setGroupeGn($groupeGn);
        }
        $this->em->persist($participant);
        $this->em->flush();

        return $participant;
    }

    /** @return list<PersonnageBonus> */
    protected function rows(Personnage $personnage): array
    {
        return $this->em
            ->getRepository(PersonnageBonus::class)
            ->findBy([
                'personnage' => $personnage,
                'source' => PersonnageBonusSource::ORIGINE->value,
            ]);
    }
}
