<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Depouillement;
use App\Entity\GroupeGn;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use App\Service\ContenuGroupe;
use App\Service\DepouillementGroupeService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Contenu de l'enveloppe d'un groupe et reliquat (collecté par le groupe plus surplus déclaré par ses joueurs).
 */
#[Group('integration')]
class DepouillementGroupeServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementGroupeService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(DepouillementGroupeService::class);
    }

    private function enveloppeGroupe(GroupeGn $groupeGn, bool $simulation = true): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn($groupeGn->getGn())
            ->setType(DepouillementType::GROUPE)
            ->setNumero((string) $groupeGn->getGroupe()->getNumero())
            ->setGroupeGn($groupeGn)
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    /**
     * @param array<string, int> $surplus
     */
    private function enveloppeJoueur(GroupeGn $groupeGn, array $surplus, bool $simulation = true): Depouillement
    {
        $participant = ParticipantFactory::createOne([
            'gn' => $groupeGn->getGn(),
            'user' => UserFactory::createOne(),
            'personnage' => PersonnageFactory::createOne(),
            'groupeGn' => $groupeGn,
        ]);
        $depouillement = (new Depouillement())
            ->setGn($groupeGn->getGn())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero((string) $participant->getPersonnage()->getId())
            ->setParticipant($participant)
            ->setSimulation($simulation)
            ->setDonnees(['contenu_joueur' => ['surplus' => $surplus]]);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    public function testEnregistrerPuisRelireNormaliseLesQuantites(): void
    {
        $enveloppe = $this->enveloppeGroupe(GroupeGnFactory::createOne());
        $contenu = new ContenuGroupe();
        $contenu->ficheRetour = true;
        $contenu->materiel = ['pieges' => 3, 'cadenas' => -2, 'inconnu' => 8];
        $contenu->autreMateriel = 'Une corne de brume';
        $contenu->compteurs = ['bois' => 5, 'sel' => -1, 'inconnu' => 4];

        $this->service->enregistrerContenuGroupe($enveloppe, $contenu, UserFactory::createOne());
        $this->em->clear();

        $relu = $this->service->lireContenuGroupe($this->em->find(Depouillement::class, $enveloppe->getId()));

        static::assertTrue($relu->ficheRetour);
        static::assertSame(3, $relu->materiel['pieges']);
        static::assertSame(0, $relu->materiel['cadenas']);
        static::assertArrayNotHasKey('inconnu', $relu->materiel);
        static::assertSame('Une corne de brume', $relu->autreMateriel);
        static::assertSame(5, $relu->compteurs['bois']);
        static::assertSame(0, $relu->compteurs['sel']);
        static::assertCount(24, $relu->compteurs);
        static::assertCount(1, $this->em->find(Depouillement::class, $enveloppe->getId())->getHistories());
    }

    public function testLeReliquatAdditionneLeGroupeEtLeSurplusDeSesJoueurs(): void
    {
        $groupeGn = GroupeGnFactory::createOne();
        $enveloppe = $this->enveloppeGroupe($groupeGn);
        $this->enveloppeJoueur($groupeGn, ['bois' => 2, 'pieces_or' => 3]);
        $this->enveloppeJoueur($groupeGn, ['bois' => 1, 'vin' => 4]);
        $contenu = new ContenuGroupe();
        $contenu->compteurs = ['bois' => 5, 'sel' => 6];
        $this->service->enregistrerContenuGroupe($enveloppe, $contenu, UserFactory::createOne());

        $reliquat = $this->service->reliquat($enveloppe);

        static::assertSame(2, $reliquat['enveloppesJoueurs']);
        static::assertSame(['groupe' => 5, 'joueurs' => 3, 'total' => 8], $reliquat['lignes']['Bois']);
        static::assertSame(['groupe' => 6, 'joueurs' => 0, 'total' => 6], $reliquat['lignes']['Sel']);
        static::assertSame(['groupe' => 0, 'joueurs' => 3, 'total' => 3], $reliquat['lignes']['Pièces d\'or']);
        static::assertSame(['groupe' => 0, 'joueurs' => 4, 'total' => 4], $reliquat['lignes']['Vin']);
        static::assertArrayNotHasKey('Pierre', $reliquat['lignes']);
    }

    public function testLesJoueursDUnAutreGroupeNeComptentPas(): void
    {
        $groupeGn = GroupeGnFactory::createOne();
        $autreGroupeGn = GroupeGnFactory::createOne(['gn' => $groupeGn->getGn()]);
        $enveloppe = $this->enveloppeGroupe($groupeGn);
        $this->enveloppeJoueur($autreGroupeGn, ['bois' => 9]);

        $reliquat = $this->service->reliquat($enveloppe);

        static::assertSame(0, $reliquat['enveloppesJoueurs']);
        static::assertSame([], $reliquat['lignes']);
    }

    public function testUneSimulationNeMelangePasLesTotauxAvecLeReel(): void
    {
        $groupeGn = GroupeGnFactory::createOne();
        $enveloppe = $this->enveloppeGroupe($groupeGn);
        $this->enveloppeJoueur($groupeGn, ['bois' => 7], simulation: false);
        $this->enveloppeJoueur($groupeGn, ['bois' => 1]);

        $reliquat = $this->service->reliquat($enveloppe);

        static::assertSame(1, $reliquat['enveloppesJoueurs']);
        static::assertSame(1, $reliquat['lignes']['Bois']['total']);
    }

    public function testReliquatSansAucunContenuNiJoueur(): void
    {
        $reliquat = $this->service->reliquat($this->enveloppeGroupe(GroupeGnFactory::createOne()));

        static::assertSame(['lignes' => [], 'enveloppesJoueurs' => 0], $reliquat);
    }

    public function testRefuseUneEnveloppeDeJoueur(): void
    {
        $enveloppe = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true);

        $this->expectException(ContenuInvalideException::class);
        $this->expectExceptionMessage('enveloppes de groupe');

        $this->service->enregistrerContenuGroupe($enveloppe, new ContenuGroupe(), UserFactory::createOne());
    }

    public function testRefuseQuandLaSaisieEstTerminee(): void
    {
        $enveloppe = $this->enveloppeGroupe(GroupeGnFactory::createOne())->setStatut(DepouillementStatut::A_VALIDER);

        $this->expectException(ContenuInvalideException::class);
        $this->expectExceptionMessage('plus modifiable');

        $this->service->enregistrerContenuGroupe($enveloppe, new ContenuGroupe(), UserFactory::createOne());
    }
}
