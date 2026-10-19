<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Enum\PersonnageBonusSource;
use App\Enum\Status;
use App\Service\OrigineBonusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Une fois décidé, le bonus d'origine est figé : l'évolution ultérieure des fiefs ne le modifie pas.
 */
#[Group('unit')]
class OrigineBonusFigeTest extends TestCase
{
    private OrigineBonusFixtures $f;

    private OrigineBonusService $service;

    protected function setUp(): void
    {
        $this->f = new OrigineBonusFixtures();
        $this->service = new OrigineBonusService($this->createStub(EntityManagerInterface::class));
    }

    public function testIdempotentUneSecondeDecisionNeCreeRien(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$origine]);
        $this->service->grant($personnage);

        static::assertSame([], $this->service->grant($personnage));
        static::assertCount(1, $personnage->getPersonnageBonus());
    }

    public function testFiefGagneApresUnRefusNeChangeRien(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, []);
        $this->service->grant($personnage);

        $personnage->getFirstParticipantGnGroupe()->addTerritoire($origine);

        static::assertTrue($this->service->isEligible($personnage), "l'état courant est éligible");
        static::assertSame([], $this->service->grant($personnage));
        static::assertFalse($this->service->isGranted($personnage), 'la décision figée reste un refus');
    }

    public function testFiefPerduApresAcquisitionLeBonusReste(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$origine]);
        $this->service->grant($personnage);

        $personnage->getFirstParticipantGnGroupe()->getTerritoires()->clear();

        static::assertFalse($this->service->isEligible($personnage));
        static::assertTrue($this->service->isGranted($personnage));
    }

    public function testSeuleLaPlusAncienneParticipationCompte(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        // La plus récente (2021) possède le fief, la plus ancienne (2020) non : refus, quel que soit l'ordre d'ajout.
        $personnage = (new Personnage())
            ->setTerritoire($origine)
            ->addParticipant($this->f->participant(2, '2021-01-01', null, [$origine]))
            ->addParticipant($this->f->participant(1, '2020-01-01', null, []));

        static::assertFalse($this->service->isEligible($personnage));
    }

    public function testRevokeEffaceLaDecisionPourPermettreUneReprise(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$origine]);
        $this->service->grant($personnage);

        $this->service->revoke($personnage);

        static::assertFalse($this->service->hasDecision($personnage));
        static::assertCount(1, $this->service->grant($personnage));
    }

    public function testLesBonusDEspeceNeSontPasTouches(): void
    {
        $origine = $this->f->territoire(1);
        $this->f->addBonus($origine, $this->f->bonus());
        $personnage = $this->f->personnage($origine, [$origine]);
        $espece = (new PersonnageBonus())
            ->setBonus($this->f->bonus())
            ->setSource(PersonnageBonusSource::ESPECE)
            ->setStatus(Status::ACTIVE);
        $personnage->addPersonnageBonus($espece);

        static::assertFalse($this->service->hasDecision($personnage));
        $this->service->grant($personnage);
        $this->service->revoke($personnage);

        static::assertTrue($personnage->getPersonnageBonus()->contains($espece));
    }
}
