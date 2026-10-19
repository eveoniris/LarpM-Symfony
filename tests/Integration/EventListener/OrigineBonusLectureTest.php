<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Competence;
use App\Entity\Personnage;
use App\Enum\BonusType;
use App\Service\PersonnageService;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 * Ce que le jeu lit (bonus du personnage, coût des compétences) suit la décision enregistrée, pas l'état des fiefs.
 */
#[Group('integration')]
class OrigineBonusLectureTest extends OrigineBonusIntegrationTestCase
{
    private function titres(Personnage $personnage): array
    {
        $service = static::getContainer()->get(PersonnageService::class);

        return array_map(static fn ($bonus): ?string => $bonus->getTitre(), $service->getAllBonus($personnage)->toArray());
    }

    private function cout(Personnage $personnage, Competence $competence): int
    {
        return static::getContainer()
            ->get(PersonnageService::class)
            ->getCompetenceHandler($personnage, $competence)
            ->getOrigineBonusCout();
    }

    public function testBonusAcquisListeDansLesBonusDuPersonnage(): void
    {
        $origine = $this->origineAvecBonus(['titre' => 'Bonus-Acquis', 'type' => BonusType::RENOMME->value]);
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($personnage, $this->groupeGn([$origine]));

        static::assertContains('Bonus-Acquis', $this->titres($personnage));
    }

    public function testBonusRefuseAbsentDesBonusMemeAvecLesInactifs(): void
    {
        $origine = $this->origineAvecBonus(['titre' => 'Bonus-Refuse', 'type' => BonusType::RENOMME->value]);
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $this->participant($personnage, $this->groupeGn([TerritoireFactory::createOne()]));

        static::assertNotContains('Bonus-Refuse', $this->titres($personnage));
        $avecInactifs = static::getContainer()->get(PersonnageService::class)->getAllBonus($personnage, null, true);
        static::assertNotContains('Bonus-Refuse', array_map(static fn ($bonus): ?string => $bonus->getTitre(), $avecInactifs->toArray()), 'un refus ne ressort pas comme bonus personnel');
    }

    public function testFiefPerduApresAcquisitionLeCoutDeCompetenceResteReduit(): void
    {
        $competence = CompetenceFactory::createOne();
        $origine = $this->origineAvecBonus([
            'type' => BonusType::XP->value,
            'valeur' => 3,
            'competence' => $competence,
        ]);
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $groupeGn = $this->groupeGn([$origine]);
        $this->participant($personnage, $groupeGn);
        static::assertSame(3, $this->cout($personnage, $competence));

        $origine->setGroupe(null);
        $groupeGn->getGroupe()->getTerritoires()->clear();
        $this->em->flush();
        $this->em->clear();

        $personnage = $this->recharger(Personnage::class, $personnage->getId());
        $competence = $this->recharger(Competence::class, $competence->getId());
        static::assertSame(3, $this->cout($personnage, $competence));
    }

    public function testRefusPuisFiefGagneLeCoutResteSansReduction(): void
    {
        $competence = CompetenceFactory::createOne();
        $origine = $this->origineAvecBonus([
            'type' => BonusType::XP->value,
            'valeur' => 3,
            'competence' => $competence,
        ]);
        $personnage = PersonnageFactory::createOne(['territoire' => $origine]);
        $groupeGn = $this->groupeGn([]);
        $this->participant($personnage, $groupeGn);
        static::assertSame(0, $this->cout($personnage, $competence));

        $origine->setGroupe($groupeGn->getGroupe());
        $groupeGn->getGroupe()->addTerritoire($origine);
        $this->em->flush();
        $this->em->clear();

        $personnage = $this->recharger(Personnage::class, $personnage->getId());
        $competence = $this->recharger(Competence::class, $competence->getId());
        static::assertSame(0, $this->cout($personnage, $competence));
    }
}
