<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Age;
use App\Entity\Personnage;
use App\Repository\PersonnageRepository;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\PersonnageFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Un héritier né en jeu (0 an) vieillit comme les autres. Cela suppose une tranche d'âge qui commence à 0 : sans elle le
 * nouveau-né n'est retrouvé par aucune tranche (les tranches de départ commencent à 15 ans) et n'est pas mis à jour.
 *
 * La tranche « Enfant héritier » est ajoutée en base par la migration Version20261030100000. Ce test crée ses propres tranches.
 */
#[Group('integration')]
class VieillissementHeritierTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private PersonnageRepository $repository;

    private Age $heritier;

    private Age $jeune;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Personnage::class);
        $this->heritier = AgeFactory::createOne(['label' => 'Enfant héritier', 'minimumValue' => 0, 'enableCreation' => false]);
        $this->jeune = AgeFactory::createOne(['label' => 'Jeune', 'minimumValue' => 15, 'enableCreation' => true]);
    }

    private function relire(int $id): Personnage
    {
        $this->em->clear();

        return $this->em->find(Personnage::class, $id);
    }

    public function testUnHeritierDeZeroAnVieillitEtResteDansLaTrancheEnfantHeritier(): void
    {
        $heritier = PersonnageFactory::createOne(['age' => $this->heritier, 'ageReel' => 0]);

        $this->repository->vieillirAll(5);

        $apres = $this->relire($heritier->getId());
        static::assertSame(5, $apres->getAgeReel());
        static::assertSame($this->heritier->getId(), $apres->getAge()->getId());
    }

    public function testApresTroisGnIlAtteintQuinzeAnsEtPasseEnJeune(): void
    {
        $heritier = PersonnageFactory::createOne(['age' => $this->heritier, 'ageReel' => 0]);

        foreach ([1, 2, 3] as $gn) {
            $this->repository->vieillirAll(5);
            $this->em->clear();
        }

        $apres = $this->relire($heritier->getId());
        static::assertSame(15, $apres->getAgeReel());
        static::assertSame($this->jeune->getId(), $apres->getAge()->getId());
    }

    public function testSansTrancheDeZeroAnUnNouveauNeNEstPasMisAJour(): void
    {
        // Documente le défaut corrigé par la migration : avec les seules tranches de départ (dès 15 ans), un personnage de 0 an
        // n'est retrouvé par aucune tranche et son âge ne bouge pas.
        $this->em->remove($this->heritier);
        $this->em->flush();
        $nouveauNe = PersonnageFactory::createOne(['age' => $this->jeune, 'ageReel' => 0]);

        $this->repository->vieillirAll(5);

        static::assertSame(0, $this->relire($nouveauNe->getId())->getAgeReel());
    }
}
