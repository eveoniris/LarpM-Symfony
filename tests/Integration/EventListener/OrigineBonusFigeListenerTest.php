<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Bonus;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use App\Tests\Factory\PersonnageFactory;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Group;

/**
 * Une fois décidé, le bonus d'origine ne bouge plus sauf correction d'origine, et rien ne supprime le personnage.
 */
#[Group('integration')]
class OrigineBonusFigeListenerTest extends OrigineBonusIntegrationTestCase
{
    public function testFiefPerduPuisSecondeParticipationLeBonusResteSansReevaluation(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $premier = $this->groupeGn([$origine]);
        $this->participant($personnage, $premier);

        $origine->setGroupe(null);
        $premier->getGroupe()->getTerritoires()->clear();
        $this->em->flush();
        $this->participant($personnage, $this->groupeGn([], new DateTime('2021-01-01')), new DateTime('2021-01-01'));

        $rows = $this->rows($personnage);
        static::assertCount(1, $rows);
        static::assertSame(Status::ACTIVE, $rows[0]->getStatus());
    }

    public function testChangementDOrigineReprendLaDecisionSansSupprimerLePersonnage(): void
    {
        $origine = $this->origineAvecBonus();
        $autre = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($personnage, $this->groupeGn([$origine]));
        $personnageId = $personnage->getId();
        $bonusId = $origine->getOriginesBonus()->first()->getBonus()->getId();

        $personnage->setTerritoire($autre);
        $this->em->flush();
        $this->em->clear();

        static::assertNotNull($this->em->find(Bonus::class, $bonusId));
        $personnage = $this->em->find(Personnage::class, $personnageId);
        static::assertNotNull($personnage, "le personnage n'est pas supprimé");
        $rows = $this->rows($personnage);
        static::assertCount(1, $rows);
        static::assertSame(Status::DELETED, $rows[0]->getStatus(), 'le groupe ne possède pas la nouvelle origine');
        static::assertSame($autre->getId(), $rows[0]->getTerritoire()->getId());
    }

    public function testLaContrainteUniqueEmpecheUneDeuxiemeLignePourLeMemeBonus(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($personnage, $this->groupeGn([$origine]));
        $existante = $this->rows($personnage)[0];

        $this->em->persist(
            (new PersonnageBonus())
                ->setPersonnage($personnage)
                ->setBonus($existante->getBonus())
                ->setTerritoire($origine)
                ->setSource(PersonnageBonusSource::ORIGINE)
                ->setStatus(Status::ACTIVE),
        );

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    public function testUnGroupeAttribueAUneParticipationPosterieureNeDecideRien(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($personnage, null);
        $plusTard = $this->participant($personnage, null, new DateTime('2021-01-01'));

        $plusTard->setGroupeGn($this->groupeGn([$origine], new DateTime('2021-01-01')));
        $this->em->flush();

        static::assertSame([], $this->rows($personnage));
    }
}
