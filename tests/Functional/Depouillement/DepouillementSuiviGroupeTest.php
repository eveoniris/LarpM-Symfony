<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use App\Service\DepouillementService;
use App\Service\DepouillementSuiviGroupeService;
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
 * Suivi des retours des membres d'un groupe : compteur reçus / attendus, clôture avec motif, lien de la fiche du participant vers son retour.
 */
#[Group('functional')]
class DepouillementSuiviGroupeTest extends WebTestCase
{
    private Gn $gn;

    private GroupeGn $groupeGn;

    private User $staff;

    /**
     * Un groupe de deux joueurs avec personnage ; le premier a envoyé son retour.
     *
     * @return array{0: Depouillement, 1: Participant, 2: Participant}
     */
    private function groupeDeDeux(): array
    {
        $this->gn = GnFactory::createOne();
        $this->groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $this->gn]);
        $this->staff = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $participants = [];
        $numeros = [];
        foreach ([1, 2] as $ignore) {
            $user = UserFactory::createOne();
            $personnage = PersonnageFactory::createOne(['user' => $user]);
            ParticipantFactory::createOne(['user' => $user, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $personnage]);
            $participant = $em->getRepository(Participant::class)->findOneBy(['user' => $user, 'gn' => $this->gn]);
            static::assertInstanceOf(Participant::class, $participant);
            $participants[] = $participant;
            $numeros[] = (string) $personnage->getId();
        }

        $service = static::getContainer()->get(DepouillementService::class);
        $groupe = $service->ouvrirReel($this->gn, DepouillementType::GROUPE, (string) $this->groupeGn->getGroupe()->getNumero(), $this->staff);
        $dossier = $service->ouvrirReel($this->gn, DepouillementType::JOUEUR, $numeros[0], $this->staff);
        $dossier->setStatut(DepouillementStatut::A_VALIDER);
        $em->flush();

        return [$groupe, $participants[0], $participants[1]];
    }

    public function testLeCompteurDistingueRecusEtAttendus(): void
    {
        [$groupe] = $this->groupeDeDeux();

        $suivi = static::getContainer()->get(DepouillementSuiviGroupeService::class)->suivi($groupe);

        static::assertSame(2, $suivi['attendus']);
        static::assertSame(1, $suivi['recus']);
        static::assertSame(0, $suivi['clotures']);
    }

    public function testLaClotureAvecMotifCompteCommeTraitee(): void
    {
        [$groupe, , $absent] = $this->groupeDeDeux();
        $service = static::getContainer()->get(DepouillementSuiviGroupeService::class);

        $service->cloturer($groupe, $absent, 'absent', 'Malade', $this->staff);
        $suivi = $service->suivi($groupe);

        static::assertSame(1, $suivi['clotures']);
        static::assertSame('Malade', $suivi['lignes'][1]['cloture']['motif'] ?? null);

        $service->rouvrir($groupe, $absent, $this->staff);
        static::assertSame(0, $service->suivi($groupe)['clotures']);
    }

    public function testLaClotureExigeUnMotifEtUnStatutConnu(): void
    {
        [$groupe, , $absent] = $this->groupeDeDeux();
        $service = static::getContainer()->get(DepouillementSuiviGroupeService::class);

        try {
            $service->cloturer($groupe, $absent, 'absent', '  ', $this->staff);
            $this->fail('Motif vide accepté');
        } catch (ContenuInvalideException) {
            static::assertSame(0, $service->suivi($groupe)['clotures']);
        }

        $this->expectException(ContenuInvalideException::class);
        $service->cloturer($groupe, $absent, 'inconnu', 'Motif', $this->staff);
    }

    public function testLeStaffClotureDepuisLEtapeEtUnJoueurNeLePeutPas(): void
    {
        $client = static::createClient();
        [$groupe, , $absent] = $this->groupeDeDeux();
        $base = '/gn/' . $this->gn->getId() . '/depouillement/' . $groupe->getId();

        $client->loginUser($this->staff);
        $crawler = $client->request('GET', $base . '/etape/retour-groupe');
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('1 / 2', $crawler->filter('body')->text());
        $form = $crawler->filter('form[action$="/cloture/' . $absent->getId() . '/cloturer"]')->form(['statut' => 'pas_de_retour', 'motif' => 'Injoignable']);
        $client->submit($form);
        static::assertResponseRedirects();

        $utilisateur = $absent->getUser();
        static::assertInstanceOf(User::class, $utilisateur);
        $client->loginUser($utilisateur);
        $client->request('POST', $base . '/cloture/' . $absent->getId() . '/rouvrir');
        static::assertResponseRedirects('/access_denied');
    }

    public function testLeLienDeLaFicheOuvreLeRetourDuParticipant(): void
    {
        $client = static::createClient();
        [, $recu, $sansRetour] = $this->groupeDeDeux();
        $client->loginUser($this->staff);

        $client->request('GET', '/gn/' . $this->gn->getId() . '/participant/' . $recu->getId() . '/retour');
        static::assertResponseRedirects();
        static::assertStringContainsString('/etape/recapitulatif', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/gn/' . $this->gn->getId() . '/participant/' . $sansRetour->getId() . '/retour');
        static::assertResponseRedirects('/participant/' . $sansRetour->getId());
    }
}
