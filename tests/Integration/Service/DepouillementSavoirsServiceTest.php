<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\DepouillementType;
use App\Enum\LangueSourceType;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementDecisionService;
use App\Service\DepouillementSavoirsService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\LangueFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Langues, connaissances et technologies apprises en jeu : ajout direct à la validation, doublons ignorés, simulation sans effet
 * sur la fiche, ligne refusée par le staff jamais appliquée.
 */
#[Group('integration')]
// @mago-expect lint:too-many-methods
class DepouillementSavoirsServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementSavoirsService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementSavoirsService::class);
    }

    private function connaissance(string $label): Connaissance
    {
        $connaissance = (new Connaissance())
            ->setLabel($label)
            ->setSecret(false);
        $this->em->persist($connaissance);
        $this->em->flush();

        return $connaissance;
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

    private function recharger(Personnage $personnage): Personnage
    {
        $this->em->clear();
        $recharge = $this->em->find(Personnage::class, $personnage->getId());
        static::assertInstanceOf(Personnage::class, $recharge);

        return $recharge;
    }

    /**
     * @return array{langues: int, connaissances: int, technologies: int, sources: list<string>}
     */
    private function nombres(Personnage $personnage): array
    {
        $langues = $personnage->getPersonnageLangues();
        $connaissances = $personnage->getConnaissances();
        $technologies = $personnage->getTechnologies();

        return [
            'langues' => $langues->count(),
            'connaissances' => $connaissances->count(),
            'technologies' => $technologies->count(),
            'sources' => array_values($langues->map(static fn ($ligne): string => $ligne->getSource())->toArray()),
        ];
    }

    private function contenuComplet(): ContenuJoueur
    {
        $contenu = new ContenuJoueur();
        $contenu->langues = [LangueFactory::createOne()];
        $contenu->connaissances = [$this->connaissance('Savoir ' . uniqid())];
        $contenu->technologies = [TechnologieFactory::createOne()];
        $contenu->enseignantTechnologieNumero = 42;

        return $contenu;
    }

    public function testLesSavoirsDeclaresSontAjoutesALaFiche(): void
    {
        [$depouillement, $personnage] = $this->enveloppe($this->contenuComplet(), false);

        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();
        $personnage = $this->recharger($personnage);

        static::assertTrue($resultat->applique);
        static::assertCount(3, $resultat->ajoutes);
        static::assertStringContainsString('enseignant n°42', implode(' ', $resultat->ajoutes));
        static::assertSame(1, $this->nombres($personnage)['langues']);
        static::assertSame([LangueSourceType::JEU->value], $this->nombres($personnage)['sources']);
        static::assertSame(1, $this->nombres($personnage)['connaissances']);
        static::assertSame(1, $this->nombres($personnage)['technologies']);
    }

    public function testUnSavoirDejaConnuEstIgnore(): void
    {
        [$depouillement] = $this->enveloppe($this->contenuComplet(), false);
        $this->service->appliquer($depouillement);
        $this->em->flush();

        $resultat = $this->service->appliquer($depouillement);

        static::assertSame([], $resultat->ajoutes);
        static::assertCount(3, $resultat->ignores);
    }

    public function testUneSimulationNEcritRienSurLaFiche(): void
    {
        [$depouillement, $personnage] = $this->enveloppe($this->contenuComplet(), true);

        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();
        $personnage = $this->recharger($personnage);

        static::assertFalse($resultat->applique);
        static::assertCount(3, $resultat->ajoutes);
        static::assertSame(0, $this->nombres($personnage)['langues']);
        static::assertSame(0, $this->nombres($personnage)['connaissances']);
        static::assertSame(0, $this->nombres($personnage)['technologies']);
    }

    public function testUneLigneRefuseeParLeStaffNEstPasAppliquee(): void
    {
        [$depouillement, $personnage] = $this->enveloppe($this->contenuComplet(), false);
        $contenuService = static::getContainer()->get(DepouillementContenuService::class);
        $langueId = $contenuService->lireContenuJoueur($depouillement)->langues[0]->getId();

        static::getContainer()
            ->get(DepouillementDecisionService::class)
            ->decider($depouillement, 'langues', (string) $langueId, DepouillementDecisionService::REFUSER, UserFactory::createOne());
        $resultat = $this->service->appliquer($depouillement);
        $this->em->flush();
        $personnage = $this->recharger($personnage);

        static::assertCount(2, $resultat->ajoutes);
        static::assertSame(0, $this->nombres($personnage)['langues']);
        static::assertSame(1, $this->nombres($personnage)['connaissances']);
    }

    public function testUnDossierSansSavoirNeFaitRien(): void
    {
        [$depouillement] = $this->enveloppe(new ContenuJoueur(), false);

        static::assertTrue($this->service->appliquer($depouillement)->estVide());
    }

    public function testUneSourceAutrePreciseeEstEnregistreeSurLaLangue(): void
    {
        $contenu = $this->contenuComplet();
        $contenu->sourceLangue = 'AUTRE';
        $contenu->sourceLangueAutre = '  Récompense de quête  ';
        [$depouillement, $personnage] = $this->enveloppe($contenu, false);

        $this->service->appliquer($depouillement);
        $this->em->flush();

        static::assertSame(['Récompense de quête'], $this->nombres($this->recharger($personnage))['sources']);
    }

    public function testAutreSansPrecisionRetombeSurLaSourceJeu(): void
    {
        $contenu = $this->contenuComplet();
        $contenu->sourceLangue = 'AUTRE';
        $contenu->sourceLangueAutre = '   ';
        [$depouillement, $personnage] = $this->enveloppe($contenu, false);

        $this->service->appliquer($depouillement);
        $this->em->flush();

        static::assertSame([LangueSourceType::JEU->value], $this->nombres($this->recharger($personnage))['sources']);
    }
}
