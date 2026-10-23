<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\Domaine;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Entity\Sphere;
use App\Enum\DepouillementType;
use App\Service\ContenuJoueur;
use App\Service\DepouillementApprentissageService;
use App\Service\DepouillementContenuService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Sorts, prières et potions appris en jeu : ajout direct à la validation, doublons ignorés, incohérences signalées,
 * simulation sans effet sur la fiche.
 */
#[Group('integration')]
class DepouillementApprentissageServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementApprentissageService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementApprentissageService::class);
    }

    private function sort(string $label, int $niveau = 1): Sort
    {
        $domaine = (new Domaine())
            ->setLabel('Domaine ' . $label)
            ->setDescription('Test');
        $sort = (new Sort())
            ->setLabel($label)
            ->setNiveau($niveau)
            ->setDomaine($domaine)
            ->setSecret(false);
        $this->em->persist($domaine);
        $this->em->persist($sort);

        return $sort;
    }

    private function priere(string $label, int $niveau = 1): Priere
    {
        $sphere = (new Sphere())->setLabel('Sphère ' . $label);
        $priere = (new Priere())
            ->setLabel($label)
            ->setNiveau($niveau)
            ->setSphere($sphere);
        $this->em->persist($sphere);
        $this->em->persist($priere);

        return $priere;
    }

    private function potion(string $numero): Potion
    {
        $potion = (new Potion())
            ->setLabel('Philtre ' . $numero)
            ->setNiveau(1)
            ->setNumero($numero)
            ->setSecret(false);
        $this->em->persist($potion);

        return $potion;
    }

    /**
     * @return array{0: Depouillement, 1: Personnage}
     */
    private function enveloppe(ContenuJoueur $contenu, bool $simulation): array
    {
        $gn = GnFactory::createOne();
        $personnage = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        $participant = $this->em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($participant)
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();
        static::getContainer()->get(DepouillementContenuService::class)->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());

        return [$depouillement, $personnage];
    }

    public function testAjouteSortsPrieresEtPotionsALaFiche(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->sorts = [$this->sort('Flamme')];
        $contenu->prieres = [$this->priere('Bénédiction')];
        $contenu->potions = [$this->potion('P1')];
        [$depouillement, $personnage] = $this->enveloppe($contenu, false);

        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();
        $this->em->clear();

        static::assertTrue($resultat->applique);
        static::assertCount(3, $resultat->ajoutes);
        static::assertSame([], $resultat->ignores);
        $relu = $this->em->find(Personnage::class, $personnage->getId());
        static::assertCount(1, $relu->getSorts());
        static::assertCount(1, $relu->getPrieres());
        static::assertCount(1, $relu->getPotions());
    }

    public function testUnElementDejaConnuEstIgnoreSansDoublon(): void
    {
        $contenu = new ContenuJoueur();
        $sort = $this->sort('Flamme');
        $potion = $this->potion('P1');
        $contenu->sorts = [$sort];
        $contenu->potions = [$potion];
        [$depouillement, $personnage] = $this->enveloppe($contenu, false);
        $personnage->addSort($sort);
        $personnage->addPotion($potion);
        $this->em->flush();

        $resultat = $this->service->appliquer($depouillement);

        static::assertSame([], $resultat->ajoutes);
        static::assertCount(2, $resultat->ignores);
        static::assertCount(1, $personnage->getSorts());
        static::assertCount(1, $personnage->getPotions());
    }

    public function testNiveauEtReligionIncoherentsSontSignalesSansBloquer(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->sorts = [$this->sort('Foudre', 3)];
        $contenu->prieres = [$this->priere('Colère', 2)];
        [$depouillement, $personnage] = $this->enveloppe($contenu, false);

        $resultat = $this->service->appliquer($depouillement);

        static::assertCount(2, $resultat->ajoutes);
        static::assertCount(1, $personnage->getSorts());
        $anomalies = implode(' | ', $resultat->anomalies);
        static::assertStringContainsString('au-dessus de la compétence Magie', $anomalies);
        static::assertStringContainsString('n\'a pas de religion', $anomalies);
        static::assertStringContainsString('au-dessus de la prêtrise', $anomalies);
    }

    public function testUneSimulationNEcritRienSurLaFiche(): void
    {
        $contenu = new ContenuJoueur();
        $contenu->sorts = [$this->sort('Flamme')];
        $contenu->potions = [$this->potion('P1')];
        [$depouillement, $personnage] = $this->enveloppe($contenu, true);

        $resultat = $this->service->appliquer($depouillement);

        static::assertFalse($resultat->applique);
        static::assertCount(2, $resultat->ajoutes);
        static::assertCount(0, $personnage->getSorts());
        static::assertCount(0, $personnage->getPotions());
        static::assertStringContainsString('simulation', $resultat->resume());
    }

    public function testUneEnveloppeSansApprentissageNeFaitRien(): void
    {
        [$depouillement] = $this->enveloppe(new ContenuJoueur(), false);

        $resultat = $this->service->appliquer($depouillement);

        static::assertTrue($resultat->estVide());
        static::assertSame('Aucun sort, prière ni potion à appliquer.', $resultat->resume());
    }
}
