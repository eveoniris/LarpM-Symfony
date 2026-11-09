<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Service\DepouillementService;
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
 * Retour de jeu du joueur : il ouvre le dossier réel de son personnage, le saisit tant qu'aucun tiers ne l'a repris,
 * et ne voit jamais le dossier d'un autre joueur.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementJoueurTest extends WebTestCase
{
    private const array BETA = ['ROLE_USER', 'ROLE_BETA_TEST'];

    /**
     * @return array{0: User, 1: Participant}
     */
    private function joueur(Gn $gn): array
    {
        $user = UserFactory::createOne(['roles' => self::BETA]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);
        $participant = ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        return [$user, $participant];
    }

    private function dossierReel(Gn $gn, Participant $participant, User $saisisseur): Depouillement
    {
        return static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($gn, DepouillementType::JOUEUR, (string) $participant->getPersonnage()->getId(), $saisisseur);
    }

    private function url(Gn $gn, Depouillement $dossier, string $suite): string
    {
        return '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/' . $suite;
    }

    public function testLeJoueurOuvreLeDossierReelDeSonPersonnage(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Ouvrir mon retour de jeu')->form());

        static::assertResponseRedirects();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $dossiers = $em->getRepository(Depouillement::class)->findAll();
        static::assertCount(1, $dossiers);
        static::assertFalse($dossiers[0]->isSimulation());
        static::assertSame((string) $participant->getPersonnage()->getId(), $dossiers[0]->getNumero());
        static::assertSame($user->getId(), $dossiers[0]->getDepouilleur()?->getId());
        static::assertSame($participant->getId(), $dossiers[0]->getParticipant()?->getId());

        // Une seconde ouverture retrouve le même dossier, sans en créer un autre.
        $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseRedirects();
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findAll());
    }

    public function testUnJoueurSansPersonnageNeCreeRien(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => self::BETA]));

        $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('form[action$="/mon-retour"]');
        $client->request('POST', '/gn/' . $gn->getId() . '/mon-retour');

        static::assertCount(0, static::getContainer()->get(EntityManagerInterface::class)->getRepository(Depouillement::class)->findAll());
    }

    public function testUnJoueurNOuvrePasLeDossierDUnAutre(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$proprietaire, $participant] = $this->joueur($gn);
        [$intrus] = $this->joueur($gn);
        $dossier = $this->dossierReel($gn, $participant, $proprietaire);
        $client->loginUser($intrus);

        foreach (['identification', 'recapitulatif'] as $etape) {
            $client->request('GET', $this->url($gn, $dossier, 'etape/' . $etape));
            static::assertResponseRedirects('/access_denied', 302, $etape);
        }
        $client->request('POST', $this->url($gn, $dossier, 'terminer'));
        static::assertResponseRedirects('/access_denied');
    }

    public function testLeJoueurSaisitSonDossierTantQueRienNEstRepris(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $dossier = $this->dossierReel($gn, $participant, $user);
        $client->loginUser($user);

        $client->request('GET', $this->url($gn, $dossier, 'etape/recapitulatif'));
        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[action$="/terminer"]');
    }

    public function testApresLaRepriseParUnTiersLeJoueurEstEnLectureSeule(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $staff = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']]);
        $dossier = $this->dossierReel($gn, $participant, $user);
        static::getContainer()->get(DepouillementService::class)->reprendre($dossier, $staff);
        $client->loginUser($user);

        // Il peut toujours lire son dossier...
        $client->request('GET', $this->url($gn, $dossier, 'etape/recapitulatif'));
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('form[action$="/terminer"]');

        // ... mais plus l'écrire, ni le reprendre.
        $client->request('POST', $this->url($gn, $dossier, 'terminer'));
        static::assertResponseRedirects('/access_denied');
        $client->request('POST', $this->url($gn, $dossier, 'reprendre'));
        static::assertResponseRedirects('/access_denied');

        // Le point d'entrée « Mon retour de jeu » l'amène au récapitulatif en lecture.
        $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseRedirects($this->url($gn, $dossier, 'etape/recapitulatif'));
    }

    public function testUnDossierEnvoyeEstEnLectureSeulePourLeJoueur(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $dossier = $this->dossierReel($gn, $participant, $user);
        $client->loginUser($user);

        static::getContainer()->get(DepouillementService::class)->terminerSaisie($dossier, $user);
        static::assertSame(DepouillementStatut::A_VALIDER, $dossier->getStatut());

        $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseRedirects($this->url($gn, $dossier, 'etape/recapitulatif'));
    }

    public function testLesSimulationsRestentOuvertesAuxBetaTesteurs(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [, $participant] = $this->joueur($gn);
        $simulation = static::getContainer()
            ->get(DepouillementService::class)
            ->creerSimulation($gn, DepouillementType::JOUEUR, (string) $participant->getPersonnage()->getId(), UserFactory::createOne(['roles' => self::BETA]));
        $client->loginUser(UserFactory::createOne(['roles' => self::BETA]));

        $client->request('GET', $this->url($gn, $simulation, 'etape/identification'));

        static::assertResponseIsSuccessful();
    }

    public function testLeStaffOuvreUnDossierReel(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $dossier = $this->dossierReel($gn, $participant, $user);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]));

        $client->request('GET', $this->url($gn, $dossier, 'etape/identification'));

        static::assertResponseIsSuccessful();
    }

    public function testUnBetaTesteurSansRoleStaffNeReprendPasUnDossierReel(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        [$user, $participant] = $this->joueur($gn);
        $dossier = $this->dossierReel($gn, $participant, $user);
        $client->loginUser(UserFactory::createOne(['roles' => self::BETA]));

        $client->request('POST', $this->url($gn, $dossier, 'reprendre'));

        static::assertResponseRedirects('/access_denied');
    }
}
