<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\OrigineBonusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * L'origine d'un PJ est une nation ; les fiefs possédés par un groupe sont dessous (nation > région > fief).
 * Le bonus d'origine est acquis si le groupe possède au moins un territoire situé dans la nation d'origine.
 */
#[Group('unit')]
class OrigineBonusNationTest extends TestCase
{
    private OrigineBonusFixtures $f;

    private OrigineBonusService $service;

    protected function setUp(): void
    {
        $this->f = new OrigineBonusFixtures();
        $this->service = new OrigineBonusService($this->createStub(EntityManagerInterface::class));
    }

    public function testFiefSousUneRegionSousLaNationEstDansLOrigine(): void
    {
        $nation = $this->f->territoire(1);
        $fief = $this->f->sous($this->f->sous($nation, 2), 3);

        static::assertTrue($this->service->isEligible($this->f->personnage($nation, [$fief])));
    }

    public function testRegionPossedeeSousLaNationEstDansLOrigine(): void
    {
        $nation = $this->f->territoire(1);

        static::assertTrue($this->service->isEligible($this->f->personnage($nation, [$this->f->sous($nation, 2)])));
    }

    public function testFiefEgalALOrigineEstDansLOrigine(): void
    {
        $nation = $this->f->territoire(1);

        static::assertTrue($this->service->isEligible($this->f->personnage($nation, [$nation])));
    }

    public function testFiefDUneAutreNationNEstPasDansLOrigine(): void
    {
        $nation = $this->f->territoire(1);
        $autreNation = $this->f->territoire(9);
        $fief = $this->f->sous($this->f->sous($autreNation, 10), 11);

        static::assertFalse($this->service->isEligible($this->f->personnage($nation, [$fief])));
    }

    public function testUneNationNEstPasDansUnFiefQuiEnDepend(): void
    {
        // Origine = un fief précis ; le groupe ne possède que la nation (plus haut) : pas dans l'origine.
        $nation = $this->f->territoire(1);
        $fief = $this->f->sous($nation, 2);

        static::assertFalse($this->service->isEligible($this->f->personnage($fief, [$nation])));
    }

    public function testUnSeulFiefDansLaNationSuffitParmiPlusieurs(): void
    {
        $nation = $this->f->territoire(1);
        $dedans = $this->f->sous($this->f->sous($nation, 2), 3);
        $dehors = $this->f->sous($this->f->territoire(9), 10);

        static::assertTrue($this->service->isEligible($this->f->personnage($nation, [$dehors, $dedans])));
    }

    public function testUneParenteCirculaireNeBouclePas(): void
    {
        $a = $this->f->territoire(1);
        $b = $this->f->sous($a, 2);
        $a->setTerritoire($b);
        $autre = $this->f->territoire(9);

        static::assertFalse($this->service->isEligible($this->f->personnage($autre, [$b])));
    }
}
