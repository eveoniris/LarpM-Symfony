<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Enum\BonusPeriode;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use App\Service\OrigineBonusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Décision du bonus d'origine : acquis si le groupe de la première participation possède l'origine du personnage.
 */
#[Group('unit')]
class OrigineBonusServiceTest extends TestCase
{
    private OrigineBonusFixtures $f;

    private OrigineBonusService $service;

    protected function setUp(): void
    {
        $this->f = new OrigineBonusFixtures();
        $this->service = new OrigineBonusService($this->createStub(EntityManagerInterface::class));
    }

    public function testFiefDuGroupeEgalAOrigineBonusAcquis(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$this->f->territoire(5), $origine]);

        static::assertTrue($this->service->isEligible($personnage));
        static::assertCount(1, $this->service->grant($personnage));
        static::assertTrue($this->service->isGranted($personnage));
    }

    public function testGroupeSansFiefCommunBonusRefuseEtEnregistre(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$this->f->territoire(5)]);

        static::assertFalse($this->service->isEligible($personnage));
        $rows = $this->service->grant($personnage);

        static::assertCount(1, $rows);
        static::assertSame(Status::DELETED, $rows[0]->getStatus());
        static::assertTrue($this->service->hasDecision($personnage));
        static::assertFalse($this->service->isGranted($personnage));
    }

    public function testTerritoireDeReferenceDuGroupeSansFiefNeDonnePasLeBonus(): void
    {
        // Régression : seul groupe.territoire (langue) vaut l'origine, le groupe ne possède aucun fief.
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = (new Personnage())
            ->setTerritoire($origine)
            ->addParticipant($this->f->participant(1, '2020-01-01', $origine, []));

        static::assertFalse($this->service->isEligible($personnage));
        $this->service->grant($personnage);

        static::assertFalse($this->service->isGranted($personnage));
    }

    public function testSansGroupeLaDecisionEstDifferee(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnageSansGroupe($origine);

        static::assertNull($this->service->isEligible($personnage));
        static::assertSame([], $this->service->grant($personnage));
        static::assertFalse($this->service->hasDecision($personnage));
    }

    public function testSansParticipationLaDecisionEstDifferee(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = (new Personnage())->setTerritoire($origine);

        static::assertNull($this->service->isEligible($personnage));
        static::assertSame([], $this->service->grant($personnage));
    }

    public function testDecisionPriseQuandLeGroupeArriveEnsuite(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnageSansGroupe($origine);
        static::assertSame([], $this->service->grant($personnage));

        $participant = $personnage->getFirstParticipant();
        static::assertNotNull($participant);
        $groupe = (new Groupe())->setTerritoire($this->f->territoire(9));
        $groupe->addTerritoire($origine);
        $participant->setGroupeGn(
            (new GroupeGn())
                ->setGn($participant->getGn())
                ->setGroupe($groupe),
        );

        static::assertCount(1, $this->service->grant($personnage));
        static::assertTrue($this->service->isGranted($personnage));
    }

    public function testPlusieursBonusNativeUneLigneChacunEtLesAutresIgnores(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $this->f->addBonus($origine, $this->f->bonus());
        $this->f->addBonus($origine, $this->f->bonus(BonusPeriode::GN));
        $this->f->addBonus($origine, $this->f->bonus(), Status::DELETED);
        $personnage = $this->f->personnage($origine, [$origine]);

        $rows = $this->service->grant($personnage);

        static::assertCount(2, $rows);
        foreach ($rows as $row) {
            static::assertSame(PersonnageBonusSource::ORIGINE, $row->getSource());
            static::assertSame($origine, $row->getTerritoire());
            static::assertSame($personnage, $row->getPersonnage());
            static::assertSame(Status::ACTIVE, $row->getStatus());
        }
    }

    public function testUnMemeBonusPortePlusieursFoisNeCreeQuUneLigne(): void
    {
        $origine = $this->f->territoire(1);
        $bonus = $this->f->bonus();
        $this->f->addBonus($origine, $bonus);
        $this->f->addBonus($origine, $bonus);

        static::assertCount(1, $this->service->grant($this->f->personnage($origine, [$origine])));
    }
}
