<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Enum\Status;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 * Le bonus d'origine est décidé au flush de la première participation, par tous les chemins applicatifs.
 */
#[Group('integration')]
class OrigineBonusListenerTest extends OrigineBonusIntegrationTestCase
{
    public function testPremiereParticipationAvecFiefCommunAcquiertLeBonus(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);

        $this->participant($personnage, $this->groupeGn([$origine]));

        $rows = $this->rows($personnage);
        static::assertCount(1, $rows);
        static::assertSame(Status::ACTIVE, $rows[0]->getStatus());
        static::assertSame($origine->getId(), $rows[0]->getTerritoire()?->getId());
    }

    public function testPremiereParticipationSansFiefCommunRefuseEtEnregistre(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);

        $this->participant($personnage, $this->groupeGn([TerritoireFactory::createOne()]));

        $rows = $this->rows($personnage);
        static::assertCount(1, $rows);
        static::assertSame(Status::DELETED, $rows[0]->getStatus());
    }

    public function testFiefSousUneRegionSousLaNationDOrigineAcquiertLeBonus(): void
    {
        $nation = $this->origineAvecBonus();
        $region = TerritoireFactory::createOne(['territoire' => $nation]);
        $fief = TerritoireFactory::createOne(['territoire' => $region]);
        $personnage = PersonnageFactory::createOne(['territoire' => $nation]);

        $this->participant($personnage, $this->groupeGn([$fief]));

        $rows = $this->rows($personnage);
        static::assertCount(1, $rows);
        static::assertSame(Status::ACTIVE, $rows[0]->getStatus());
        static::assertSame($nation->getId(), $rows[0]->getTerritoire()?->getId());
    }

    public function testFiefDUneAutreNationRefuseLeBonus(): void
    {
        $nation = $this->origineAvecBonus();
        $autreNation = TerritoireFactory::createOne();
        $fief = TerritoireFactory::createOne(['territoire' => TerritoireFactory::createOne(['territoire' => $autreNation])]);
        $personnage = PersonnageFactory::createOne(['territoire' => $nation]);

        $this->participant($personnage, $this->groupeGn([$fief]));

        static::assertSame(Status::DELETED, $this->rows($personnage)[0]->getStatus());
    }

    public function testSansGroupeRienPuisDecisionALAttributionDuGroupe(): void
    {
        $origine = $this->origineAvecBonus();
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $participant = $this->participant($personnage, null);
        static::assertSame([], $this->rows($personnage));

        $participant->setGroupeGn($this->groupeGn([$origine], null, $participant->getGn()));
        $this->em->flush();

        static::assertCount(1, $this->rows($personnage));
    }
}
