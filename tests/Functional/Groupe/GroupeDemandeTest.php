<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\GroupeGnDemandeType;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnDemandeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests des demandes d'adhésion à un groupe de session :
 * - candidature (joueur -> groupe) et invitation (chef -> joueur),
 * - acceptation / refus,
 * - garde-fous d'accès.
 *
 * DAMA wrappe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class GroupeDemandeTest extends WebTestCase
{
    /**
     * Construit un contexte : un GN, un groupe ouvert au recrutement dont $chef est
     * responsable, un joueur avec billet mais sans groupe.
     *
     * @return array{gn: mixed, chef: User, chefParticipant: Participant, groupeGn: GroupeGn, joueur: User, joueurParticipant: Participant}
     */
    private function createContext(): array
    {
        $gn = GnFactory::createOne();
        $chef = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $groupe = GroupeFactory::createOne(['userRelatedByResponsableId' => $chef]);
        $groupeGn = GroupeGnFactory::createOne([
            'groupe' => $groupe,
            'gn' => $gn,
            'free' => true,
            'place_available' => 5,
        ]);
        $chefParticipant = ParticipantFactory::createOne(['user' => $chef, 'gn' => $gn, 'groupeGn' => $groupeGn]);

        $billet = BilletFactory::createOne(['gn' => $gn]);
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $joueurParticipant = ParticipantFactory::createOne([
            'user' => $joueur,
            'gn' => $gn,
            'billet' => $billet,
            'groupeGn' => null,
        ]);

        return [
            'gn' => $gn,
            'chef' => $chef,
            'chefParticipant' => $chefParticipant,
            'groupeGn' => $groupeGn,
            'joueur' => $joueur,
            'joueurParticipant' => $joueurParticipant,
        ];
    }

    /**
     * Envoie la réponse (POST) avec le jeton CSRF affiché sur la page du joueur ou de gestion du groupe.
     * Sans bouton visible (utilisateur sans droit), un jeton factice est envoyé : le contrôle d'accès passe avant.
     */
    private function repondre(KernelBrowser $client, int $demandeId, string $reponse): void
    {
        $demande = GroupeGnDemandeFactory::find($demandeId);
        $page = $demande->isInvitation()
            ? '/participant/' . $demande->getParticipant()->getId() . '/index'
            : '/groupeGn/' . $demande->getGroupeGn()->getGroupe()->getId() . '/list/';
        $url = '/groupeGn/demande/' . $demandeId . '/' . $reponse;

        $crawler = $client->request('GET', $page);
        $champ = $crawler->filter(\sprintf('form[action$="%s"] input[name=_token]', $url));
        $jeton = $champ->count() > 0 ? (string) $champ->attr('value') : 'invalide';

        $client->request('POST', $url, ['_token' => $jeton]);
    }

    // -------------------------------------------------------------------------
    // Invitation (chef -> joueur)
    // -------------------------------------------------------------------------

    public function testChefCanAccessInviteForm(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $client->loginUser($ctx['chef']);
        $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/joueur/add/');

        static::assertResponseIsSuccessful();
    }

    public function testRandomUserCannotAccessInviteForm(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $client->loginUser($ctx['joueur']);
        $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/joueur/add/');

        static::assertResponseRedirects('/access_denied');
    }

    public function testChefInvitePlayerCreatesInvitation(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $client->loginUser($ctx['chef']);
        $crawler = $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/joueur/add/');

        $form = $crawler->selectButton('Inviter le joueur choisi')->form();
        $client->submit($form, [
            'form[participant]' => (string) $ctx['joueurParticipant']->getId(),
            'form[message]' => 'Rejoins-nous !',
        ]);

        static::assertResponseRedirects();
        static::assertSame(1, GroupeGnDemandeFactory::count());
        $demande = GroupeGnDemandeFactory::first();
        static::assertSame(GroupeGnDemandeType::INVITATION, $demande->getType());
        static::assertSame('Rejoins-nous !', $demande->getMessage());
    }

    public function testPlayerAcceptsInvitationJoinsGroup(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $demande = GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::INVITATION,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $ctx['groupeGn'],
        ]);

        $client->loginUser($ctx['joueur']);
        $this->repondre($client, $demande->getId(), 'accept');

        static::assertResponseRedirects();
        static::assertSame(0, GroupeGnDemandeFactory::count());
        $joueurParticipant = ParticipantFactory::find($ctx['joueurParticipant']->getId());
        static::assertNotNull($joueurParticipant->getGroupeGn());
        static::assertSame($ctx['groupeGn']->getId(), $joueurParticipant->getGroupeGn()->getId());
    }

    public function testPlayerRefusesInvitation(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $demande = GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::INVITATION,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $ctx['groupeGn'],
        ]);

        $client->loginUser($ctx['joueur']);
        $this->repondre($client, $demande->getId(), 'refuse');

        static::assertResponseRedirects();
        static::assertSame(0, GroupeGnDemandeFactory::count());
        $joueurParticipant = ParticipantFactory::find($ctx['joueurParticipant']->getId());
        static::assertNull($joueurParticipant->getGroupeGn());
    }

    public function testRandomUserCannotAcceptInvitationOfAnother(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $demande = GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::INVITATION,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $ctx['groupeGn'],
        ]);

        $intrus = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($intrus);
        $this->repondre($client, $demande->getId(), 'accept');

        static::assertResponseRedirects('/access_denied');
        static::assertSame(1, GroupeGnDemandeFactory::count());
    }

    // -------------------------------------------------------------------------
    // Candidature (joueur -> groupe)
    // -------------------------------------------------------------------------

    public function testPlayerPostuleCreatesCandidature(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $client->loginUser($ctx['joueur']);
        $url = '/participant/' . $ctx['joueurParticipant']->getId() . '/groupe/' . $ctx['groupeGn']->getId() . '/postuler';
        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton('Postuler')->form();
        $client->submit($form, ['form[message]' => 'Je veux jouer ce rôle.']);

        static::assertResponseRedirects();
        static::assertSame(1, GroupeGnDemandeFactory::count());
        static::assertSame(GroupeGnDemandeType::CANDIDATURE, GroupeGnDemandeFactory::first()->getType());
    }

    public function testResponsableAcceptsCandidatureJoinsPlayer(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $demande = GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::CANDIDATURE,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $ctx['groupeGn'],
        ]);

        $client->loginUser($ctx['chef']);
        $this->repondre($client, $demande->getId(), 'accept');

        static::assertResponseRedirects();
        static::assertSame(0, GroupeGnDemandeFactory::count());
        $joueurParticipant = ParticipantFactory::find($ctx['joueurParticipant']->getId());
        static::assertNotNull($joueurParticipant->getGroupeGn());
    }

    public function testAcceptPurgesOtherPendingDemandes(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        // Une autre session à laquelle le joueur a aussi postulé.
        $autreGroupe = GroupeFactory::createOne();
        $autreGroupeGn = GroupeGnFactory::createOne(['groupe' => $autreGroupe, 'gn' => $ctx['gn'], 'free' => true, 'place_available' => 3]);

        $invitation = GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::INVITATION,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $ctx['groupeGn'],
        ]);
        GroupeGnDemandeFactory::createOne([
            'type' => GroupeGnDemandeType::CANDIDATURE,
            'participant' => $ctx['joueurParticipant'],
            'groupeGn' => $autreGroupeGn,
        ]);
        static::assertSame(2, GroupeGnDemandeFactory::count());

        $client->loginUser($ctx['joueur']);
        $this->repondre($client, $invitation->getId(), 'accept');

        static::assertResponseRedirects();
        // Le joueur ayant rejoint un groupe, toutes ses demandes sont purgées.
        static::assertSame(0, GroupeGnDemandeFactory::count());
    }

    // -------------------------------------------------------------------------
    // Garde-fous de sécurité
    // -------------------------------------------------------------------------

    public function testRandomUserCannotRemoveParticipant(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $intrus = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($intrus);
        $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/participant/remove/' . $ctx['joueurParticipant']->getId());

        static::assertResponseRedirects('/access_denied');
    }

    public function testScenaristeCanAccessDirectAdd(): void
    {
        $client = static::createClient();
        $ctx = $this->createContext();

        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($scenariste);
        $client->request('GET', '/groupeGn/' . $ctx['groupeGn']->getId() . '/participants/add/');

        static::assertResponseIsSuccessful();
    }
}
