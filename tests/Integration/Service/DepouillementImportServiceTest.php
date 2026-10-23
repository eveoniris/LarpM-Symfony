<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\EnveloppeLigne;
use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Potion;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\EnveloppeLigneNature;
use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Exception\ValidationInvalideException;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementImportService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Report vers l'enveloppe du GN suivant : étiquettes et objets, potions de départ de l'ivoire, attente d'import, simulation.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class DepouillementImportServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementImportService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementImportService::class);
    }

    private function gn(string $debut, string $fin, bool $actif = true): Gn
    {
        return GnFactory::createOne(['actif' => $actif, 'date_debut' => new DateTime($debut), 'date_fin' => new DateTime($fin)]);
    }

    private function etiquette(Depouillement $depouillement, EtiquetteDestination $destination, ?EtiquetteActionObjet $action = null): void
    {
        $etiquette = (new DepouillementEtiquette())
            ->setNature(EtiquetteNature::OBJET)
            ->setCouleur(EtiquetteCouleur::BLEUE)
            ->setNumero(12)
            ->setQualite('812')
            ->setQuantite(1)
            ->setSituation(EtiquetteSituation::AVEC_OBJET)
            ->setDestination($destination)
            ->setActionObjet($action)
            ->setSpeciale(true)
            ->setAPrevoirProchainOpus(true);
        $depouillement->addEtiquette($etiquette);
        $this->em->persist($etiquette);
    }

    /**
     * @return array{0: Depouillement, 1: Personnage}
     */
    private function validee(Gn $gn, bool $simulation = false, ?ContenuJoueur $contenu = null): array
    {
        $personnage = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($this->em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]))
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();
        if (null !== $contenu) {
            static::getContainer()->get(DepouillementContenuService::class)->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());
        }

        $this->etiquette($depouillement, EtiquetteDestination::ENVELOPPE);
        $this->etiquette($depouillement, EtiquetteDestination::AVEC_OBJET_AU_PC, EtiquetteActionObjet::REMETTRE_AU_PC);
        $depouillement->setStatut(DepouillementStatut::VALIDE);
        $this->em->flush();

        return [$depouillement, $personnage];
    }

    private function inscrire(Personnage $personnage, Gn $gn): Participant
    {
        return ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
    }

    public function testImporteLesLignesDansLEnveloppeDuGnSuivantEtTermine(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $suivant = $this->gn('+6 month', '+6 month +3 days');
        [$depouillement, $personnage] = $this->validee($gn);
        $this->inscrire($personnage, $suivant);

        $resultat = $this->service->importer($depouillement, UserFactory::createOne());

        static::assertTrue($resultat->applique);
        static::assertSame(DepouillementStatut::TERMINE, $depouillement->getStatut());
        $lignes = $this->em->getRepository(EnveloppeLigne::class)->findBy(['personnage' => $personnage], ['id' => 'ASC']);
        static::assertCount(3, $lignes);
        static::assertSame($suivant->getId(), $lignes[0]->getGn()->getId());
        static::assertSame(
            [EnveloppeLigneNature::ETIQUETTE_A_REINSERER, EnveloppeLigneNature::ETIQUETTE_A_REINSERER, EnveloppeLigneNature::OBJET_AU_PC],
            array_map(static fn (EnveloppeLigne $ligne): EnveloppeLigneNature => $ligne->getNature(), $lignes),
        );
        static::assertSame(12, $lignes[0]->getNumeroObjet());
    }

    public function testSansGnSuivantLeDepouillementAttendPuisSImporte(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        [$depouillement, $personnage] = $this->validee($gn);

        $resultat = $this->service->importer($depouillement, UserFactory::createOne());

        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $depouillement->getStatut());
        static::assertStringContainsString('Aucun GN actif', $resultat->anomalies[0]);
        static::assertCount(0, $this->em->getRepository(EnveloppeLigne::class)->findBy(['personnage' => $personnage]));

        // La session suivante s'ouvre et le personnage y est inscrit : l'import réussit.
        $suivant = $this->gn('+6 month', '+6 month +3 days');
        $this->inscrire($personnage, $suivant);
        $this->service->importer($depouillement, null);

        static::assertSame(DepouillementStatut::TERMINE, $depouillement->getStatut());
        static::assertCount(3, $this->em->getRepository(EnveloppeLigne::class)->findBy(['personnage' => $personnage]));
    }

    public function testUnPersonnageNonInscritAuGnSuivantAttend(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $this->gn('+6 month', '+6 month +3 days');
        [$depouillement] = $this->validee($gn);

        $resultat = $this->service->importer($depouillement, UserFactory::createOne());

        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $depouillement->getStatut());
        static::assertStringContainsString('pas encore inscrit', $resultat->anomalies[0]);
    }

    public function testUnGnInactifOuAnterieurNEstPasLeGnSuivant(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $this->gn('+6 month', '+6 month +3 days', actif: false);
        $this->gn('-6 month', '-6 month +3 days');
        [$depouillement] = $this->validee($gn);

        $this->service->importer($depouillement, UserFactory::createOne());

        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $depouillement->getStatut());
    }

    public function testLeGnSuivantEstLePlusProche(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $loin = $this->gn('+12 month', '+12 month +3 days');
        $proche = $this->gn('+6 month', '+6 month +3 days');
        [$depouillement, $personnage] = $this->validee($gn);
        $this->inscrire($personnage, $loin);
        $this->inscrire($personnage, $proche);

        $this->service->importer($depouillement, UserFactory::createOne());

        $ligne = $this->em->getRepository(EnveloppeLigne::class)->findOneBy(['personnage' => $personnage]);
        static::assertSame($proche->getId(), $ligne->getGn()->getId());
    }

    public function testLIvoireAcceptePorteSesPotionsDansLesPotionsDeDepart(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $suivant = $this->gn('+6 month', '+6 month +3 days');
        $potion = (new Potion())
            ->setLabel('Philtre')
            ->setNiveau(1)
            ->setNumero('P7')
            ->setSecret(false);
        $this->em->persist($potion);
        $contenu = new ContenuJoueur();
        $contenu->ivoireUtilise = true;
        $contenu->ivoireJointes = true;
        $contenu->ivoirePotions = [$potion];
        [$depouillement, $personnage] = $this->validee($gn, contenu: $contenu);
        $participant = $this->inscrire($personnage, $suivant);

        $resultat = $this->service->importer($depouillement, UserFactory::createOne());

        static::assertCount(1, $participant->getPotionsDepart());
        static::assertContains('potion de départ Philtre', $resultat->ajoutes);
    }

    public function testUneSimulationNImporteRienEtGardeSonStatut(): void
    {
        $gn = $this->gn('+1 month', '+1 month +3 days');
        $suivant = $this->gn('+6 month', '+6 month +3 days');
        [$depouillement, $personnage] = $this->validee($gn, simulation: true);
        $this->inscrire($personnage, $suivant);

        $resultat = $this->service->importer($depouillement, UserFactory::createOne());

        static::assertTrue($resultat->estVide());
        static::assertSame(DepouillementStatut::VALIDE, $depouillement->getStatut());
        static::assertCount(0, $this->em->getRepository(EnveloppeLigne::class)->findBy(['personnage' => $personnage]));
    }

    public function testSeulUnDepouillementValideOuEnAttenteSImporte(): void
    {
        [$depouillement] = $this->validee($this->gn('+1 month', '+1 month +3 days'));
        $depouillement->setStatut(DepouillementStatut::A_VALIDER);

        $this->expectException(ValidationInvalideException::class);
        $this->service->importer($depouillement, UserFactory::createOne());
    }
}
