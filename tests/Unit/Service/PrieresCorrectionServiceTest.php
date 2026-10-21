<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\AttributeType;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Entity\PersonnagesReligions;
use App\Entity\Priere;
use App\Entity\Religion;
use App\Entity\ReligionLevel;
use App\Entity\Sphere;
use App\Service\PrieresCorrectionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class PrieresCorrectionServiceTest extends TestCase
{
    private PrieresCorrectionService $service;

    protected function setUp(): void
    {
        $this->service = new PrieresCorrectionService();
    }

    /** @param list<int> $niveaux */
    private function sphere(array $niveaux): Sphere
    {
        $sphere = new Sphere();
        foreach ($niveaux as $niveau) {
            $priere = new Priere();
            $priere->setNiveau($niveau);
            $priere->setSphere($sphere);
            $sphere->addPriere($priere);
        }

        return $sphere;
    }

    private function religion(Sphere ...$spheres): Religion
    {
        $religion = new Religion();
        foreach ($spheres as $sphere) {
            $religion->addSphere($sphere);
        }

        return $religion;
    }

    private function rejoindre(Personnage $personnage, Religion $religion, int $niveau): void
    {
        $level = new ReligionLevel();
        $level->setIndex($niveau);
        $pr = new PersonnagesReligions();
        $pr->setReligion($religion);
        $pr->setReligionLevel($level);
        $pr->setPersonnage($personnage);
        $personnage->addPersonnagesReligions($pr);
    }

    /** @param array<string, int> $attributs */
    private function competence(int $niveau, array $attributs): Competence
    {
        $family = $this->createStub(CompetenceFamily::class);
        $family->method('getLabel')->willReturn('Prêtrise');
        $level = $this->createStub(Level::class);
        $level->method('getIndex')->willReturn($niveau);

        $competence = $this->createStub(Competence::class);
        $competence->method('getCompetenceFamily')->willReturn($family);
        $competence->method('getLevel')->willReturn($level);
        $competence->method('getAttributeValue')->willReturnCallback(static fn (string $cle): mixed => $attributs[$cle] ?? null);

        return $competence;
    }

    private function pretre(int $niveauMax = 1): Personnage
    {
        $personnage = new Personnage();
        for ($i = 0; $i < $niveauMax; ++$i) {
            $personnage->addCompetence($this->competence($i + 1, [AttributeType::$PRIERES[$i] => 2]));
        }

        return $personnage;
    }

    public function testAucuneActionQuandQuotaRespecte(): void
    {
        $sphere = $this->sphere([1, 1]);
        $personnage = $this->pretre();
        $this->rejoindre($personnage, $this->religion($sphere), 2);
        foreach ($sphere->getPrieres() as $priere) {
            $personnage->addPriere($priere);
        }

        static::assertSame([], $this->service->analyser($personnage));
    }

    public function testRetirePriereDeReligionPratiquantEnExces(): void
    {
        $set = $this->sphere([1, 1]);
        $ancetres = $this->sphere([1]);
        $personnage = $this->pretre();
        $this->rejoindre($personnage, $this->religion($set), 2);
        $this->rejoindre($personnage, $this->religion($ancetres), 1);
        foreach ([...$set->getPrieres(), ...$ancetres->getPrieres()] as $priere) {
            $personnage->addPriere($priere);
        }

        $actions = $this->service->analyser($personnage);

        static::assertCount(1, $actions);
        static::assertSame('retirer', $actions[0]['action']);
        static::assertSame($ancetres->getPrieres()->first(), $actions[0]['priere']);
        static::assertSame(PrieresCorrectionService::MOTIF_HORS_RELIGION, $actions[0]['motif']);
    }

    public function testPrieresOrphellesRetireesSansCompetence(): void
    {
        $sphere = $this->sphere([3]);
        $personnage = $this->pretre(); // quota de niveau 1 seulement
        $this->rejoindre($personnage, $this->religion($sphere), 3);
        $personnage->addPriere($sphere->getPrieres()->first());

        $actions = $this->service->analyser($personnage);

        static::assertCount(1, $actions);
        static::assertSame(PrieresCorrectionService::MOTIF_ORPHELINE, $actions[0]['motif']);
        static::assertSame([], $this->service->analyser($personnage, avecOrphelines: false));
    }

    public function testAjouteLesPrieresManquantesSiReligionFervente(): void
    {
        $sphere = $this->sphere([1, 1, 2]);
        $personnage = $this->pretre();
        $this->rejoindre($personnage, $this->religion($sphere), 2);

        $actions = $this->service->analyser($personnage);

        static::assertCount(2, $actions);
        static::assertSame(['ajouter', 'ajouter'], array_column($actions, 'action'));
        static::assertSame([1, 1], array_column($actions, 'niveau'));
    }

    public function testNAjouteRienSansReligionFerventeOuFanatique(): void
    {
        $sphere = $this->sphere([1, 1]);
        $sansReligion = $this->pretre();
        $pratiquant = $this->pretre();
        $this->rejoindre($pratiquant, $this->religion($sphere), 1);

        static::assertSame([], $this->service->analyser($sansReligion));
        static::assertSame([], $this->service->analyser($pratiquant));
    }

    public function testExcesSansPriereIdentifiableEstSignaleNonRetire(): void
    {
        $sphere = $this->sphere([1, 1, 1]);
        $personnage = $this->pretre();
        $this->rejoindre($personnage, $this->religion($sphere), 2);
        foreach ($sphere->getPrieres() as $priere) {
            $personnage->addPriere($priere);
        }

        $actions = $this->service->analyser($personnage);

        static::assertSame(['ambigu'], array_column($actions, 'action'));
        static::assertNull($actions[0]['priere']);
    }
}
