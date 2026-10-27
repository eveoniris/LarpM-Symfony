<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\Domaine;
use App\Entity\Personnage;
use App\Entity\Sort;
use App\Service\DepouillementEnveloppeResolver;
use App\Service\PiecesAttendues;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\PersonnageFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Pièces d'enveloppe attendues selon le profil du personnage : magie, alchimie ou herboristerie, prêtrise.
 */
#[Group('integration')]
class PiecesAttenduesTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private PiecesAttendues $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = new PiecesAttendues(new DepouillementEnveloppeResolver($this->em->getRepository(Depouillement::class)));
    }

    private function personnageAvecFamille(string $famille): Personnage
    {
        $personnage = PersonnageFactory::createOne();
        $competence = CompetenceFactory::createOne(['competenceFamily' => CompetenceFamilyFactory::createOne(['label' => $famille])]);
        // La compétence est le côté propriétaire de la relation.
        $competence->addPersonnage($personnage);
        $personnage->addCompetence($competence);
        $this->em->flush();

        return $personnage;
    }

    public function testUnPersonnageSansCompetenceNAttendQueLesPiecesGenerales(): void
    {
        $profil = $this->service->pourPersonnage(PersonnageFactory::createOne());

        foreach (PiecesAttendues::POUR_TOUS as $piece) {
            static::assertTrue($profil['pieces'][$piece], $piece);
        }

        foreach (['ficheSort', 'corruptionOuvertes', 'corruptionNonOuvertes', 'copiesRecettesSorts', 'carteAlchimiste', 'fichePriere'] as $piece) {
            static::assertFalse($profil['pieces'][$piece], $piece);
        }

        static::assertStringContainsString('ni magie', $profil['profil']);
    }

    public function testUnMagicienAttendLaCorruptionLesFichesDeSortEtLesCopies(): void
    {
        $profil = $this->service->pourPersonnage($this->personnageAvecFamille('Magie'));

        static::assertTrue($profil['pieces']['corruptionOuvertes']);
        static::assertTrue($profil['pieces']['corruptionNonOuvertes']);
        static::assertTrue($profil['pieces']['ficheSort']);
        static::assertTrue($profil['pieces']['copiesRecettesSorts']);
        static::assertFalse($profil['pieces']['carteAlchimiste']);
        static::assertFalse($profil['pieces']['fichePriere']);
        static::assertSame('magie', $profil['profil']);
    }

    public function testUnAlchimisteOuUnHerboristeAttendLaCarte(): void
    {
        foreach (['Alchimie', 'Herboristerie'] as $famille) {
            $profil = $this->service->pourPersonnage($this->personnageAvecFamille($famille));

            static::assertTrue($profil['pieces']['carteAlchimiste'], $famille);
            static::assertTrue($profil['pieces']['copiesRecettesSorts'], $famille);
            static::assertFalse($profil['pieces']['corruptionOuvertes'], $famille);
        }
    }

    public function testUnPersonnageAvecLitteratureAttendLesCopiesDeRecettesEtDeSorts(): void
    {
        $profil = $this->service->pourPersonnage($this->personnageAvecFamille('Littérature'));

        static::assertTrue($profil['pieces']['copiesRecettesSorts']);
        static::assertFalse($profil['pieces']['corruptionOuvertes']);
        static::assertFalse($profil['pieces']['carteAlchimiste']);
        static::assertSame('littérature', $profil['profil']);
    }

    public function testUnPretreAttendLaFicheDePriere(): void
    {
        $profil = $this->service->pourPersonnage($this->personnageAvecFamille('Prêtrise'));

        static::assertTrue($profil['pieces']['fichePriere']);
        static::assertFalse($profil['pieces']['ficheSort']);
        static::assertSame('prêtrise', $profil['profil']);
    }

    public function testConnaitreUnSortSuffitPourEtreUnMagicien(): void
    {
        $domaine = (new Domaine())
            ->setLabel('Feu')
            ->setDescription('Test');
        $sort = (new Sort())
            ->setLabel('Flamme')
            ->setNiveau(1)
            ->setDomaine($domaine)
            ->setSecret(false);
        $this->em->persist($domaine);
        $this->em->persist($sort);
        $personnage = PersonnageFactory::createOne();
        $personnage->addSort($sort);
        $this->em->flush();

        static::assertTrue($this->service->pourPersonnage($personnage)['pieces']['corruptionOuvertes']);
    }

    public function testSansPersonnageToutEstAttendu(): void
    {
        $profil = $this->service->pourPersonnage(null);

        static::assertNotContains(false, $profil['pieces']);
    }
}
