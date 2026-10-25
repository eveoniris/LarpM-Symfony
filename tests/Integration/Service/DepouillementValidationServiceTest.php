<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\DepouillementHistory;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Entity\Domaine;
use App\Entity\Participant;
use App\Entity\Sort;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Entity\LogAction;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\LogActionType;
use App\Exception\ValidationInvalideException;
use App\Service\DepouillementService;
use App\Service\DepouillementValidationService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Validation et renvoi : transitions, traçabilité, événement sommaire dans log_action (hors simulations), pas de réouverture.
 */
// @mago-expect lint:too-many-methods
#[Group('integration')]
class DepouillementValidationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementValidationService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementValidationService::class);
    }

    private function aValider(bool $simulation): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation($simulation)
            ->setStatut(DepouillementStatut::A_VALIDER);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    /**
     * @return list<LogAction>
     */
    private function logs(): array
    {
        return array_values($this->em->getRepository(LogAction::class)->findBy(['type' => LogActionType::DEPOUILLEMENT->value]));
    }

    public function testValiderMetLeStatutLaDateEtLeValidateur(): void
    {
        $depouillement = $this->aValider(simulation: false);
        $validateur = UserFactory::createOne();

        $this->service->valider($depouillement, $validateur);

        static::assertSame(DepouillementStatut::VALIDE, $depouillement->getStatut());
        static::assertNotNull($depouillement->getValidatedAt());
        static::assertSame($validateur->getId(), $depouillement->getValidatedBy()?->getId());
        $historique = $depouillement->getHistories()->first();
        static::assertSame(DepouillementHistory::ACTION_VALIDATION, $historique->getActionType());
        static::assertSame(DepouillementStatut::A_VALIDER, $historique->getStatutAvant());
        static::assertSame(DepouillementStatut::VALIDE, $historique->getStatutApres());
    }

    public function testLaValidationEcritUnEvenementSommaireQuiRenvoieALHistorique(): void
    {
        $depouillement = $this->aValider(simulation: false);

        $this->service->valider($depouillement, UserFactory::createOne());

        $logs = $this->logs();
        static::assertCount(1, $logs);
        $donnees = $logs[0]->getData();
        static::assertSame('validation', $donnees['action']);
        static::assertSame($depouillement->getId(), $donnees['depouillement_id']);
        static::assertSame($depouillement->getHistories()->first()->getId(), $donnees['depouillement_history_id']);
    }

    public function testUneSimulationSeValideSansEcrireDansLeJournal(): void
    {
        $depouillement = $this->aValider(simulation: true);

        $this->service->valider($depouillement, UserFactory::createOne());

        static::assertSame(DepouillementStatut::VALIDE, $depouillement->getStatut());
        static::assertSame([], $this->logs());
        static::assertStringContainsString('aucun effet', (string) $depouillement->getHistories()->first()->getMotif());
    }

    public function testRenvoyerRepasseEnDepouillementAvecLeMotif(): void
    {
        $depouillement = $this->aValider(simulation: false);
        $validateur = UserFactory::createOne();

        $this->service->renvoyer($depouillement, $validateur, '  Il manque la photo des potions.  ');

        static::assertSame(DepouillementStatut::EN_DEPOUILLEMENT, $depouillement->getStatut());
        static::assertNull($depouillement->getValidatedAt());
        static::assertNull($depouillement->getValidatedBy());
        $historique = $depouillement->getHistories()->first();
        static::assertSame(DepouillementStatut::EN_DEPOUILLEMENT, $historique->getStatutApres());
        static::assertSame('Renvoyé au dépouilleur : Il manque la photo des potions.', $historique->getMotif());
        static::assertSame('renvoi', $this->logs()[0]->getData()['action']);
    }

    public function testLeMotifDeRenvoiEstObligatoire(): void
    {
        $depouillement = $this->aValider(simulation: false);

        try {
            $this->service->renvoyer($depouillement, UserFactory::createOne(), "  \n ");
            static::fail('Une exception était attendue.');
        } catch (ValidationInvalideException $exception) {
            static::assertStringContainsString('motif du renvoi est obligatoire', $exception->getMessage());
        }

        static::assertSame(DepouillementStatut::A_VALIDER, $depouillement->getStatut());
        static::assertCount(0, $depouillement->getHistories());
        static::assertSame([], $this->logs());
    }

    public function testUnDepouillementValideNePeutPlusEtreRouvertNiRevalide(): void
    {
        $depouillement = $this->aValider(simulation: false);
        $validateur = UserFactory::createOne();
        $this->service->valider($depouillement, $validateur);

        $actions = [
            fn () => $this->service->renvoyer($depouillement, $validateur, 'Je me suis trompé'),
            fn () => $this->service->valider($depouillement, $validateur),
        ];

        foreach ($actions as $action) {
            try {
                $action();
                static::fail('Une exception était attendue.');
            } catch (ValidationInvalideException $exception) {
                static::assertStringContainsString('« Validé »', $exception->getMessage());
            }
        }

        static::assertSame(DepouillementStatut::VALIDE, $depouillement->getStatut());
        static::assertCount(1, $this->logs());
    }

    public function testSeulUnDepouillementAValiderPeutEtreValideOuRenvoye(): void
    {
        $depouillement = $this->aValider(simulation: false)->setStatut(DepouillementStatut::EN_DEPOUILLEMENT);

        $this->expectException(ValidationInvalideException::class);
        $this->expectExceptionMessage('« En dépouillement »');

        $this->service->valider($depouillement, UserFactory::createOne());
    }

    public function testUnDepouillementRenvoyeSeRetermineEtSeValide(): void
    {
        $depouillement = $this->aValider(simulation: false);
        $utilisateur = UserFactory::createOne();
        $this->service->renvoyer($depouillement, $utilisateur, 'À corriger');

        static::assertTrue(static::getContainer()->get(DepouillementService::class)->terminerSaisie($depouillement, $utilisateur));
        $this->service->valider($depouillement, $utilisateur);

        static::assertSame(DepouillementStatut::VALIDE, $depouillement->getStatut());
        // Renvoi, fin de saisie, validation.
        static::assertCount(3, $depouillement->getHistories());
    }

    public function testLaValidationAppliqueLesApprentissagesEtLesConsigne(): void
    {
        $gn = GnFactory::createOne();
        $personnage = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn, 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
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
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $personnage->getId())
            ->setParticipant($this->em->getRepository(Participant::class)->findOneBy(['gn' => $gn, 'personnage' => $personnage]))
            ->setSimulation(false);
        $this->em->persist($depouillement);
        $this->em->flush();
        $contenu = new ContenuJoueur();
        $contenu->sorts = [$sort];
        static::getContainer()->get(DepouillementContenuService::class)->enregistrerContenuJoueur($depouillement, $contenu, UserFactory::createOne());
        $depouillement->setStatut(DepouillementStatut::A_VALIDER);
        $this->em->flush();

        $resultat = $this->service->valider($depouillement, UserFactory::createOne());

        static::assertCount(1, $resultat->apprentissages->ajoutes);
        static::assertCount(1, $personnage->getSorts());
        static::assertStringContainsString('Flamme', (string) $depouillement->getHistories()->last()->getMotif());
        static::assertSame(['Sort Flamme (niveau 1)'], array_map('ucfirst', $this->logs()[0]->getData()['apprentissages']['ajoutes']));
    }

    public function testLaValidationMetEnAttenteDImportQuandLeGnSuivantNEstPasOuvert(): void
    {
        $depouillement = $this->aValider(simulation: false);
        $depouillement->setParticipant($this->participantDe($depouillement));
        $etiquette = (new DepouillementEtiquette())
            ->setNature(EtiquetteNature::OBJET)
            ->setCouleur(EtiquetteCouleur::ORANGE)
            ->setNumero(5)
            ->setQualite('812')
            ->setQuantite(1)
            ->setSituation(EtiquetteSituation::SANS_OBJET)
            ->setDestination(EtiquetteDestination::ENVELOPPE)
            ->setSpeciale(true)
            ->setAPrevoirProchainOpus(true);
        $depouillement->addEtiquette($etiquette);
        $this->em->persist($etiquette);
        $this->em->flush();

        $resultat = $this->service->valider($depouillement, UserFactory::createOne());

        static::assertSame(DepouillementStatut::EN_ATTENTE_IMPORT, $depouillement->getStatut());
        static::assertNotNull($depouillement->getValidatedAt());
        static::assertStringContainsString('Aucun GN actif', $resultat->enveloppeSuivante->anomalies[0]);
        static::assertCount(2, $depouillement->getHistories());
    }

    private function participantDe(Depouillement $depouillement): Participant
    {
        $personnage = PersonnageFactory::createOne();
        $participant = ParticipantFactory::createOne(['gn' => $depouillement->getGn(), 'user' => UserFactory::createOne(), 'personnage' => $personnage]);
        $depouillement->setNumero((string) $personnage->getId());

        return $this->em->getRepository(Participant::class)->find($participant->getId());
    }
}
