<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Conception;
use App\Entity\ExperienceGain;
use App\Entity\Genre;
use App\Entity\Gn;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageLignee;
use App\Enum\ConceptionIssue;
use App\Enum\ConceptionStatut;
use App\Enum\LangueSourceType;
use App\Exception\ConceptionInvalideException;
use App\Service\ConceptionRegles;
use App\Service\HeritierSaisie;
use App\Service\HeritierService;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\LangueFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Décisions du scénariste sur une conception : création des héritiers (âge, classe secrète, XP, lignée, langues), plus tard, clôture.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class HeritierServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private HeritierService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(HeritierService::class);
        if (null === $this->em->getRepository(\App\Entity\Age::class)->findOneBy(['label' => ConceptionRegles::AGE_HERITIER])) {
            AgeFactory::createOne(['label' => ConceptionRegles::AGE_HERITIER, 'enableCreation' => false]);
        }

        if (null === $this->em->getRepository(\App\Entity\Classe::class)->findOneBy(['secrete' => true])) {
            ClasseFactory::createOne(['secrete' => true]);
        }
    }

    private function conception(bool $simulation = false, ?Gn $gn = null): Conception
    {
        $gn ??= GnFactory::createOne(['xp_creation' => 12, 'date_jeu' => 1300]);
        $pere = PersonnageFactory::createOne(['genre' => $this->genre(1)]);
        $mere = PersonnageFactory::createOne(['genre' => $this->genre(2)]);
        // Parents enregistrés dans l'ordre inverse du lignage : la mère en premier.
        $conception = (new Conception())
            ->setGn($gn)
            ->setParent1($mere)
            ->setParent2($pere)
            ->setSimulation($simulation)
            ->setTirage(12)
            ->setIssue(ConceptionIssue::GARCON);
        $this->em->persist($conception);
        $this->em->flush();

        return $conception;
    }

    private function genre(int $id): Genre
    {
        $genre = $this->em->getRepository(Genre::class)->find($id);

        return $genre ?? GenreFactory::createOne();
    }

    private function saisie(string $nom = 'Petit Conan', ?Langue $langueParents = null): HeritierSaisie
    {
        $saisie = new HeritierSaisie();
        $saisie->nom = $nom;
        $saisie->genre = $this->genre(1);
        $saisie->territoire = TerritoireFactory::createOne();
        $saisie->langueParents = $langueParents;

        return $saisie;
    }

    public function testCreeUnHeritierSansJoueurALAgeZeroEtClasseSecrete(): void
    {
        $conception = $this->conception();

        $heritiers = $this->service->creer($conception, [$this->saisie()], UserFactory::createOne());

        static::assertCount(1, $heritiers);
        $heritier = $heritiers[0];
        static::assertNull($heritier->getUser());
        static::assertSame(0, $heritier->getAgeReel());
        static::assertSame(ConceptionRegles::AGE_HERITIER, $heritier->getAge()->getLabel());
        static::assertTrue($heritier->getClasse()->isSecrete());
        static::assertSame(12, $heritier->getXp());
        static::assertSame([$heritier->getId()], $conception->getHeritiers());
        static::assertSame(ConceptionStatut::HERITIERS_CREES, $conception->getStatut());
        static::assertNotNull($conception->getDecideAt());
    }

    public function testNaissanceXpEtLigneeSontEnregistres(): void
    {
        $conception = $this->conception();
        $heritier = $this->service->creer($conception, [$this->saisie()], UserFactory::createOne())[0];
        $this->em->clear();
        $genresDeReference = null !== $this->em->getRepository(Genre::class)->find(1) && null !== $this->em->getRepository(Genre::class)->find(2);

        $naissance = $this->em->getRepository(PersonnageChronologie::class)->findOneBy(['personnage' => $heritier->getId()]);
        static::assertSame('Naissance', $naissance?->getEvenement());
        static::assertSame(1300, $naissance->getAnnee());
        static::assertCount(1, $this->em->getRepository(ExperienceGain::class)->findBy(['personnage' => $heritier->getId()]));

        $lignee = $this->em->getRepository(PersonnageLignee::class)->findOneBy(['personnage' => $heritier->getId()]);
        static::assertNotNull($lignee->getParent1());
        static::assertNotNull($lignee->getParent2());
        if ($genresDeReference) {
            // Le père (genre 1) est remis en premier parent.
            static::assertSame(1, $lignee->getParent1()->getGenre()->getId());
            static::assertSame(2, $lignee->getParent2()->getGenre()->getId());
        }
    }

    public function testLaLangueDesParentsEstTransmiseEnSourceParents(): void
    {
        $langue = LangueFactory::createOne();
        $heritier = $this->service->creer($this->conception(), [$this->saisie('Héritier', $langue)], UserFactory::createOne())[0];

        $sources = array_map(static fn ($l): string => $l->getSource(), $heritier->getPersonnageLangues()->toArray());
        static::assertContains(LangueSourceType::PARENTS->value, $sources);
    }

    public function testDesJumeauxCreentDeuxHeritiers(): void
    {
        $conception = $this->conception();

        $heritiers = $this->service->creer($conception, [$this->saisie('Fille'), $this->saisie('Garçon')], UserFactory::createOne());

        static::assertCount(2, $heritiers);
        static::assertCount(2, $conception->getHeritiers());
    }

    public function testUneSimulationNeCreeJamaisDHeritier(): void
    {
        $nombre = \count($this->em->getRepository(Personnage::class)->findAll());

        try {
            $this->service->creer($this->conception(true), [$this->saisie()], UserFactory::createOne());
            static::fail('Une simulation ne doit pas créer d\'héritier.');
        } catch (ConceptionInvalideException $exception) {
            static::assertStringContainsString('simulation', $exception->getMessage());
        }

        // Seuls les deux parents de la conception existent.
        static::assertCount($nombre + 2, $this->em->getRepository(Personnage::class)->findAll());
    }

    public function testUneConceptionDejaTraiteeNePeutPasEtreRecreee(): void
    {
        $conception = $this->conception();
        $this->service->creer($conception, [$this->saisie()], UserFactory::createOne());

        $this->expectException(ConceptionInvalideException::class);
        $this->service->creer($conception, [$this->saisie()], UserFactory::createOne());
    }

    public function testLeNomLeGenreEtLOrigineSontObligatoires(): void
    {
        $saisie = $this->saisie('  ');

        $this->expectException(ConceptionInvalideException::class);
        $this->service->creer($this->conception(), [$saisie], UserFactory::createOne());
    }

    public function testPasPlusDeDeuxHeritiers(): void
    {
        $this->expectException(ConceptionInvalideException::class);
        $this->service->creer($this->conception(), [$this->saisie('A'), $this->saisie('B'), $this->saisie('C')], UserFactory::createOne());
    }

    public function testGererPlusTardExigeUneRemarqueEtEstConsigne(): void
    {
        $conception = $this->conception();

        try {
            $this->service->gererPlusTard($conception, '   ', UserFactory::createOne());
            static::fail('La remarque est obligatoire.');
        } catch (ConceptionInvalideException) {
            static::assertSame(ConceptionStatut::EN_ATTENTE, $conception->getStatut());
        }

        $this->service->gererPlusTard($conception, 'Naissance en cours de jeu', UserFactory::createOne());

        static::assertSame(ConceptionStatut::A_GERER_PLUS_TARD, $conception->getStatut());
        static::assertSame('Naissance en cours de jeu', $conception->getRemarque());
        static::assertInstanceOf(DateTime::class, $conception->getDecideAt());
    }

    public function testCloturerSansHeritier(): void
    {
        $conception = $this->conception();

        $this->service->cloturer($conception, 'Échec, rien de plus', UserFactory::createOne());

        static::assertSame(ConceptionStatut::CLOTUREE, $conception->getStatut());
        static::assertSame([], $conception->getHeritiers());
    }
}
