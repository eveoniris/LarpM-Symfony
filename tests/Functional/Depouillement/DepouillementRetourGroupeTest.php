<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\GroupeGn;
use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DepouillementRetourGroupeService;
use App\Service\DepouillementService;
use App\Service\DepouillementTitresService;
use App\Service\RetourGroupe;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Retour de jeu d'un groupe : le chef de groupe et les titulaires des six titres saisissent le même dossier, les autres joueurs n'y
 * ont pas accès, les numéros de titre sont contrôlés, et la validation applique les titres sans écraser un titre déjà tenu.
 */
#[Group('functional')]
class DepouillementRetourGroupeTest extends WebTestCase
{
    private const array BETA = ['ROLE_USER', 'ROLE_BETA_TEST'];

    private Gn $gn;

    private GroupeGn $groupeGn;

    /**
     * Un groupe avec son chef et un diplomate titré (deux joueurs différents).
     *
     * @return array{0: User, 1: User, 2: Personnage}
     */
    private function groupe(): array
    {
        $this->gn = GnFactory::createOne();
        $this->groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $this->gn]);

        $chef = UserFactory::createOne(['roles' => self::BETA]);
        ParticipantFactory::createOne(['user' => $chef, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => PersonnageFactory::createOne(['user' => $chef])]);
        $diplomate = UserFactory::createOne(['roles' => self::BETA]);
        $personnageDiplomate = PersonnageFactory::createOne(['user' => $diplomate]);
        ParticipantFactory::createOne(['user' => $diplomate, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $personnageDiplomate]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $participantChef = $em->getRepository(Participant::class)->findOneBy(['user' => $chef, 'gn' => $this->gn]);
        $this->groupeGn->setParticipant($participantChef);
        $this->groupeGn->setDiplomate($personnageDiplomate);
        $em->flush();

        return [$chef, $diplomate, $personnageDiplomate];
    }

    private function dossier(User $joueur): Depouillement
    {
        return static::getContainer()->get(DepouillementService::class)->ouvrirPourGroupe($this->gn, $joueur);
    }

    private function url(Depouillement $dossier, string $suite): string
    {
        return '/gn/' . $this->gn->getId() . '/depouillement/' . $dossier->getId() . '/' . $suite;
    }

    public function testLeChefEtLeTitulaireOuvrentLeMemeDossierDeGroupe(): void
    {
        [$chef, $diplomate] = $this->groupe();

        $dossierChef = $this->dossier($chef);
        $dossierDiplomate = $this->dossier($diplomate);

        static::assertSame($dossierChef->getId(), $dossierDiplomate->getId());
        static::assertSame(DepouillementType::GROUPE, $dossierChef->getType());
        static::assertFalse($dossierChef->isSimulation());
    }

    public function testUnJoueurSansTitreNEstPasDuGroupe(): void
    {
        $this->groupe();
        $autre = UserFactory::createOne(['roles' => self::BETA]);
        ParticipantFactory::createOne(['user' => $autre, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => PersonnageFactory::createOne(['user' => $autre])]);

        static::assertNotInstanceOf(GroupeGn::class, static::getContainer()->get(DepouillementService::class)->groupeDuJoueur($this->gn, $autre));
    }

    public function testLEcranDEntreeOuvreLeDossierDuGroupe(): void
    {
        $client = static::createClient();
        [$chef] = $this->groupe();
        $client->loginUser($chef);

        $crawler = $client->request('GET', '/gn/' . $this->gn->getId() . '/mon-groupe');
        static::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Ouvrir le retour de mon groupe')->form());

        static::assertResponseRedirects();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy(['type' => DepouillementType::GROUPE]));
    }

    public function testLeTitulaireEnregistreLeRetourEtUnTiersEstRefuse(): void
    {
        $client = static::createClient();
        [$chef, $diplomate, $personnageDiplomate] = $this->groupe();
        $dossier = $this->dossier($chef);
        $autre = UserFactory::createOne(['roles' => self::BETA]);

        $client->loginUser($autre);
        $client->request('GET', $this->url($dossier, 'etape/retour-groupe'));
        static::assertResponseRedirects('/access_denied');

        $client->loginUser($diplomate);
        $crawler = $client->request('GET', $this->url($dossier, 'etape/retour-groupe'));
        static::assertResponseIsSuccessful();
        $form = $crawler
            ->selectButton('Enregistrer le retour du groupe')
            ->form([
                'retour_groupe[ordres1]' => 'Conquérir la côte nord',
                'retour_groupe[titres][diplomate]' => (string) $personnageDiplomate->getId(),
            ]);
        $client->submit($form);
        static::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $recharge = $em->find(Depouillement::class, $dossier->getId());
        static::assertInstanceOf(Depouillement::class, $recharge);
        $retour = static::getContainer()->get(DepouillementRetourGroupeService::class)->lire($recharge);
        static::assertSame('Conquérir la côte nord', $retour->ordres1);
        static::assertSame($personnageDiplomate->getId(), $retour->titres['diplomate']);
    }

    public function testUnNumeroDeTitreInconnuDuGnEstRefuse(): void
    {
        [$chef] = $this->groupe();
        $dossier = $this->dossier($chef);
        $retour = new RetourGroupe();
        $retour->titres['suzerain'] = 99_999_999;

        $this->expectException(\App\Exception\ContenuInvalideException::class);
        static::getContainer()->get(DepouillementRetourGroupeService::class)->enregistrer($dossier, $retour, $chef);
    }

    public function testLaValidationAppliqueLesTitresSansEcraserUnTitreDejaTenu(): void
    {
        [$chef, , $personnageDiplomate] = $this->groupe();
        $dossier = $this->dossier($chef);
        $intendant = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $intendant]);
        $autreDiplomate = PersonnageFactory::createOne();
        ParticipantFactory::createOne(['user' => UserFactory::createOne(), 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $autreDiplomate]);

        $retour = new RetourGroupe();
        $retour->titres = ['intendant' => $intendant->getId(), 'diplomate' => $autreDiplomate->getId()];
        static::getContainer()->get(DepouillementRetourGroupeService::class)->enregistrer($dossier, $retour, $chef);

        $resultat = static::getContainer()->get(DepouillementTitresService::class)->appliquer($dossier);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();
        $em->clear();
        $groupeGn = $em->find(GroupeGn::class, $this->groupeGn->getId());
        static::assertInstanceOf(GroupeGn::class, $groupeGn);

        static::assertTrue($resultat->applique);
        static::assertCount(1, $resultat->ajoutes);
        static::assertCount(1, $resultat->anomalies);
        static::assertSame($intendant->getId(), $groupeGn->getIntendant()?->getId());
        static::assertSame($personnageDiplomate->getId(), $groupeGn->getDiplomate()?->getId());
    }
}
