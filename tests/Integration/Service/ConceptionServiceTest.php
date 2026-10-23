<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\Personnage;
use App\Enum\ConceptionIssue;
use App\Enum\ConceptionStatut;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\ConceptionInvalideException;
use App\Service\ConceptionService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Enregistrement des conceptions d'héritier : parents contrôlés, tirage unique, anomalies d'âge, doublons, saisie verrouillée.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class ConceptionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private ConceptionService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(ConceptionService::class);
    }

    private function inscrit(Gn $gn, ?int $ageReel = null): Personnage
    {
        $personnage = PersonnageFactory::createOne();
        if (null !== $ageReel) {
            $personnage->setAgeReel($ageReel);
            $this->em->flush();
        }

        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);

        return $personnage;
    }

    private function enveloppe(Gn $gn, Personnage $personnage, bool $simulation = true): Depouillement
    {
        $participant = $this->em->getRepository(\App\Entity\Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($participant)
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    public function testEnregistreLaConceptionAvecLeTirageEtLIssue(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);
        $depouillement = $this->enveloppe($gn, $moi);

        $conception = $this->service->enregistrer($depouillement, (string) $autre->getId(), UserFactory::createOne(), static fn (): int => 20);

        static::assertSame(20, $conception->getTirage());
        static::assertSame(ConceptionIssue::JUMEAUX, $conception->getIssue());
        static::assertSame(ConceptionStatut::EN_ATTENTE, $conception->getStatut());
        static::assertSame($moi->getId(), $conception->getParent1()->getId());
        static::assertSame($autre->getId(), $conception->getParent2()->getId());
        static::assertTrue($conception->isSimulation());
        static::assertSame([], $conception->getAnomalies());
        static::assertCount(1, $depouillement->getConceptions());
        static::assertStringContainsString('Conception enregistrée', $depouillement->getHistories()->last()->getMotif());
    }

    public function testUneConceptionReelleNEstPasUneSimulation(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);

        $conception = $this->service->enregistrer($this->enveloppe($gn, $moi, false), (string) $autre->getId(), UserFactory::createOne(), static fn (): int => 7);

        static::assertFalse($conception->isSimulation());
        static::assertSame(ConceptionIssue::FILLE, $conception->getIssue());
    }

    public function testUnParentDe45AnsOuPlusNEstPasTire(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn, 45);
        $autre = $this->inscrit($gn, 30);

        $conception = $this->service->enregistrer($this->enveloppe($gn, $moi), (string) $autre->getId(), UserFactory::createOne());

        static::assertNull($conception->getTirage());
        static::assertNull($conception->getIssue());
        static::assertCount(1, $conception->getAnomalies());
        static::assertStringContainsString('45 ans', $conception->getAnomalies()[0]);
    }

    public function testLePartenaireTolereUnNumeroAvecDesZerosOuUnNom(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);

        $conception = $this->service->enregistrer($this->enveloppe($gn, $moi), '00' . $autre->getId() . ' Conan', UserFactory::createOne(), static fn (): int => 12);

        static::assertSame($autre->getId(), $conception->getParent2()->getId());
    }

    public function testLeMemeCoupleSignaleParLAutreJoueurEstRefuse(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);
        $this->service->enregistrer($this->enveloppe($gn, $moi), (string) $autre->getId(), UserFactory::createOne(), static fn (): int => 8);

        $this->expectException(ConceptionInvalideException::class);
        $this->expectExceptionMessage('déjà enregistrée');
        $this->service->enregistrer($this->enveloppe($gn, $autre), (string) $moi->getId(), UserFactory::createOne());
    }

    public function testLesDeuxParentsDoiventEtreDifferents(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);

        $this->expectException(ConceptionInvalideException::class);
        $this->expectExceptionMessage('deux personnages différents');
        $this->service->enregistrer($this->enveloppe($gn, $moi), (string) $moi->getId(), UserFactory::createOne());
    }

    public function testUnPartenaireNonInscritAuGnEstRefuse(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $etranger = PersonnageFactory::createOne();

        $this->expectException(ConceptionInvalideException::class);
        $this->expectExceptionMessage('Aucun personnage');
        $this->service->enregistrer($this->enveloppe($gn, $moi), (string) $etranger->getId(), UserFactory::createOne());
    }

    public function testUnNumeroSansChiffreEstRefuse(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);

        $this->expectException(ConceptionInvalideException::class);
        $this->service->enregistrer($this->enveloppe($gn, $moi), 'Conan', UserFactory::createOne());
    }

    public function testUneSaisieTermineeEstVerrouillee(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);
        $depouillement = $this->enveloppe($gn, $moi)->setStatut(DepouillementStatut::A_VALIDER);
        $this->em->flush();

        $this->expectException(ConceptionInvalideException::class);
        $this->expectExceptionMessage(Depouillement::MESSAGE_NON_MODIFIABLE);
        $this->service->enregistrer($depouillement, (string) $autre->getId(), UserFactory::createOne());
    }

    public function testUneEnveloppeDeGroupeNePeutPasSignalerDeConception(): void
    {
        $gn = GnFactory::createOne();
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::GROUPE)
            ->setNumero('1')
            ->setSimulation(true);
        $this->em->persist($depouillement);
        $this->em->flush();

        $this->expectException(ConceptionInvalideException::class);
        $this->service->enregistrer($depouillement, '1', UserFactory::createOne());
    }

    public function testRetirerUneConceptionEnAttente(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);
        $depouillement = $this->enveloppe($gn, $moi);
        $conception = $this->service->enregistrer($depouillement, (string) $autre->getId(), UserFactory::createOne(), static fn (): int => 8);

        $this->service->supprimer($conception, UserFactory::createOne());

        static::assertCount(0, $depouillement->getConceptions());
        static::assertSame([], $this->em->getRepository(Conception::class)->findAll());
    }

    public function testUneConceptionDejaDecideeNePeutPasEtreRetiree(): void
    {
        $gn = GnFactory::createOne();
        $moi = $this->inscrit($gn);
        $autre = $this->inscrit($gn);
        $conception = $this->service->enregistrer($this->enveloppe($gn, $moi), (string) $autre->getId(), UserFactory::createOne(), static fn (): int => 8);
        $conception->setStatut(ConceptionStatut::A_GERER_PLUS_TARD);

        $this->expectException(ConceptionInvalideException::class);
        $this->service->supprimer($conception, UserFactory::createOne());
    }
}
