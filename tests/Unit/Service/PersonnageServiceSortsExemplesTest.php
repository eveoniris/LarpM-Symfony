<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Domaine;
use App\Entity\Personnage;
use App\Entity\Sort;
use App\Service\PersonnageService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * SEC-01 : un sort secret n'apparaît pas dans les sorts d'exemple d'un domaine,
 * sauf si le personnage le possède déjà.
 */
#[Group('SEC-01')]
class PersonnageServiceSortsExemplesTest extends TestCase
{
    public function testSortSecretNonPossedeEstMasque(): void
    {
        $public = $this->sort('Flamme', 1, false);
        $secret = $this->sort('Pacte noir', 1, true);
        $domaine = $this->domaine($public, $secret);

        $exemples = $this->service()->getSortsExemples($domaine, $this->personnage(1, []));

        static::assertSame([$public], $exemples);
    }

    public function testSortSecretDejaPossedeResteVisible(): void
    {
        $public = $this->sort('Flamme', 1, false);
        $secret = $this->sort('Pacte noir', 1, true);
        $domaine = $this->domaine($public, $secret);

        $exemples = $this->service()->getSortsExemples($domaine, $this->personnage(1, [$secret]));

        static::assertSame([$public, $secret], $exemples);
    }

    public function testSortAuDessusDuNiveauEstMasque(): void
    {
        $niveau1 = $this->sort('Flamme', 1, false);
        $niveau3 = $this->sort('Brasier', 3, false);
        $domaine = $this->domaine($niveau1, $niveau3);

        $exemples = $this->service()->getSortsExemples($domaine, $this->personnage(1, []));

        static::assertSame([$niveau1], $exemples);
    }

    private function service(): PersonnageService
    {
        // getSortsExemples n'utilise aucune dépendance du service
        return new ReflectionClass(PersonnageService::class)->newInstanceWithoutConstructor();
    }

    private function sort(string $label, int $niveau, bool $secret): Sort
    {
        $sort = new Sort();
        $sort->setLabel($label);
        $sort->setNiveau($niveau);
        $sort->setSecret($secret);

        return $sort;
    }

    private function domaine(Sort ...$sorts): Domaine
    {
        $domaine = new Domaine();
        foreach ($sorts as $sort) {
            $domaine->addSort($sort);
        }

        return $domaine;
    }

    /**
     * @param list<Sort> $sortsPossedes
     */
    private function personnage(int $niveauMagie, array $sortsPossedes): Personnage
    {
        $personnage = $this->createMock(Personnage::class);
        $personnage->method('getCompetenceNiveau')->willReturn($niveauMagie);
        $personnage->method('getSorts')->willReturn(new \Doctrine\Common\Collections\ArrayCollection($sortsPossedes));

        return $personnage;
    }
}
