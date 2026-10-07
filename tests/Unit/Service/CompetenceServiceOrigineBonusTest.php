<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bonus;
use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Level;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\OrigineBonus;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Enum\BonusType;
use App\Enum\Status;
use App\Service\CompetenceService;
use App\Service\ConditionsService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Le bonus d'origine XP sur une compétence ne s'applique que si le premier groupe de jeu a la même origine.
 */
#[Group('unit')]
class CompetenceServiceOrigineBonusTest extends TestCase
{
    private function service(): CompetenceService
    {
        return new CompetenceService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Security::class),
            $this->createStub(ConditionsService::class),
        );
    }

    private function competence(): Competence
    {
        return (new Competence())
            ->setId(10)
            ->setCompetenceFamily(new CompetenceFamily())
            ->setLevel(new Level());
    }

    /** Origine 1 portant un bonus XP de 2 et un bonus de compétence offerte sur $competence. */
    private function origineAvecBonus(Competence $competence): Territoire
    {
        $origine = (new Territoire())->setId(1);

        foreach ([BonusType::XP, BonusType::COMPETENCE] as $type) {
            $bonus = (new Bonus())
                ->setType($type)
                ->setValeur(2)
                ->setCompetence($competence);
            $origine->addOrigineBonus(
                (new OrigineBonus())
                    ->setBonus($bonus)
                    ->setTerritoire($origine)
                    ->setStatus(Status::ACTIVE),
            );
        }

        return $origine;
    }

    private function personnageAvecPremierGroupe(Territoire $origine, Territoire $origineGroupe): Personnage
    {
        $gn = (new Gn())
            ->setId(1)
            ->setLabel('LH1')
            ->setDateDebut(new DateTime('2020-01-01'));
        $groupeGn = (new GroupeGn())
            ->setGn($gn)
            ->setGroupe((new Groupe())->setTerritoire($origineGroupe));
        $participant = (new Participant())
            ->setId(1)
            ->setGn($gn)
            ->setGroupeGn($groupeGn);

        return (new Personnage())
            ->setClasse(new Classe())
            ->setTerritoire($origine)
            ->addParticipant($participant);
    }

    public function testBonusAppliqueQuandLePremierGroupeALaMemeOrigine(): void
    {
        $competence = $this->competence();
        $origine = $this->origineAvecBonus($competence);
        $personnage = $this->personnageAvecPremierGroupe($origine, $origine);

        $service = $this->service()->init($personnage, $competence);

        self::assertSame(2, $service->getOrigineBonusCout());
        self::assertCount(1, $service->getOrigineBonusCompetences($personnage));
    }

    public function testBonusIgnoreQuandLePremierGroupeAUneAutreOrigine(): void
    {
        $competence = $this->competence();
        $origine = $this->origineAvecBonus($competence);
        $personnage = $this->personnageAvecPremierGroupe($origine, (new Territoire())->setId(2));

        $service = $this->service()->init($personnage, $competence);

        self::assertSame(0, $service->getOrigineBonusCout());
        self::assertCount(0, $service->getOrigineBonusCompetences($personnage));
    }

    public function testBonusIgnoreSansAucuneParticipation(): void
    {
        $competence = $this->competence();
        $personnage = (new Personnage())
            ->setClasse(new Classe())
            ->setTerritoire($this->origineAvecBonus($competence));

        $service = $this->service()->init($personnage, $competence);

        self::assertSame(0, $service->getOrigineBonusCout());
    }
}
