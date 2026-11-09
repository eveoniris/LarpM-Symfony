<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\Domaine;
use App\Entity\Gn;
use App\Entity\Sort;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DecisionsDeclaration;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementDeclarationService;
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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Décisions du staff sur la déclaration du joueur : confirmer, refuser (retire de ce qui sera appliqué), remettre en attente.
 */
#[Group('functional')]
// @mago-expect lint:too-many-methods
class DepouillementDecisionTest extends WebTestCase
{
    /**
     * @return array{0: Gn, 1: User, 2: Depouillement}
     */
    private function dossierDeJoueur(): array
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        $dossier = static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId(), $user);

        return [$gn, $user, $dossier];
    }

    private function sort(): Sort
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $domaine = (new Domaine())
            ->setLabel('Feu')
            ->setDescription('');
        $em->persist($domaine);
        $sort = (new Sort())
            ->setLabel('Boule de feu')
            ->setNiveau(1)
            ->setDomaine($domaine)
            ->setDocumentUrl('');
        $em->persist($sort);
        $em->flush();

        return $sort;
    }

    private function recharger(Depouillement $dossier): Depouillement
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $recharge = $em->find(Depouillement::class, $dossier->getId());
        static::assertInstanceOf(Depouillement::class, $recharge);

        return $recharge;
    }

    private function staff(): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);
    }

    /**
     * Le joueur déclare un sort et une renommée.
     */
    private function declarer(KernelBrowser $client, Gn $gn, User $user, Depouillement $dossier, Sort $sort): void
    {
        $client->loginUser($user);
        $adresse = '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/etape/declaration';
        $crawler = $client->request('GET', $adresse);
        $form = $crawler->selectButton('Enregistrer ce que j\'ai appris')->form();
        $form->setValues(['contenu_joueur[sorts]' => [(string) $sort->getId()]]);
        $client->submit($form);

        $crawler = $client->request('GET', $adresse);
        $form = $crawler->selectButton('Enregistrer mon retour')->form();
        $form['declaration_joueur[renommee]'] = '+2 pour un discours';
        $client->submit($form);
    }

    // @mago-expect lint:excessive-parameter-list
    private function decider(KernelBrowser $client, Gn $gn, Depouillement $dossier, string $champ, string $element, string $decision): void
    {
        $adresse = '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/etape/declaration';
        $crawler = $client->request('GET', $adresse);
        $jeton = $crawler->filter('form[action$="/decision"] input[name="_token"]')->first()->attr('value');
        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/decision', [
            '_token' => $jeton,
            'champ' => $champ,
            'element' => $element,
            'decision' => $decision,
        ]);
    }

    /**
     * @return list<int|null>
     */
    private function idsDesSorts(Depouillement $dossier): array
    {
        $contenu = static::getContainer()->get(DepouillementContenuService::class)->lireContenuJoueur($dossier);

        return array_map(static fn (Sort $sort): ?int => $sort->getId(), $contenu->sorts);
    }

    public function testRefuserUnSortLeRetireDeCeQuiSeraAppliquePuisLeRemetEnAttente(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $sort = $this->sort();
        $this->declarer($client, $gn, $user, $dossier, $sort);
        static::assertSame([$sort->getId()], $this->idsDesSorts($this->recharger($dossier)));

        $client->loginUser($this->staff());
        $this->decider($client, $gn, $dossier, 'sorts', (string) $sort->getId(), 'refuser');

        static::assertResponseRedirects();
        $apres = $this->recharger($dossier);
        static::assertSame([], $this->idsDesSorts($apres));
        $entree = DecisionsDeclaration::entree(DecisionsDeclaration::depuis($apres->getDonnees()), 'sorts', (string) $sort->getId());
        static::assertSame('refuse', $entree['etat'] ?? null);
        static::assertSame('Boule de feu', $entree['libelle'] ?? null);
        static::assertSame('joueur', $entree['source'] ?? null);

        $this->decider($client, $gn, $dossier, 'sorts', (string) $sort->getId(), 'remettre');
        $remis = $this->recharger($dossier);
        static::assertSame([$sort->getId()], $this->idsDesSorts($remis));
        static::assertSame('en_attente', DecisionsDeclaration::lire(DecisionsDeclaration::depuis($remis->getDonnees()), 'sorts', (int) $sort->getId())['etat']);
    }

    public function testConfirmerUnTexteDuJoueur(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $this->declarer($client, $gn, $user, $dossier, $this->sort());

        $client->loginUser($this->staff());
        $this->decider($client, $gn, $dossier, 'renommee', '_', 'confirmer');

        $apres = $this->recharger($dossier);
        $decision = DecisionsDeclaration::lire(DecisionsDeclaration::depuis($apres->getDonnees()), 'renommee');
        static::assertSame(['source' => 'joueur', 'etat' => 'confirme'], $decision);
        static::assertSame('+2 pour un discours', static::getContainer()->get(DepouillementDeclarationService::class)->lire($apres)->renommee);
    }

    public function testRefuserUnTexteLeVideMaisGardeLaValeurRefusee(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $this->declarer($client, $gn, $user, $dossier, $this->sort());

        $client->loginUser($this->staff());
        $this->decider($client, $gn, $dossier, 'renommee', '_', 'refuser');
        $refuse = $this->recharger($dossier);
        static::assertNull(static::getContainer()->get(DepouillementDeclarationService::class)->lire($refuse)->renommee);
        $entree = DecisionsDeclaration::entree(DecisionsDeclaration::depuis($refuse->getDonnees()), 'renommee');
        static::assertSame('+2 pour un discours', $entree['valeur'] ?? null);

        $this->decider($client, $gn, $dossier, 'renommee', '_', 'remettre');
        static::assertSame('+2 pour un discours', static::getContainer()->get(DepouillementDeclarationService::class)->lire($this->recharger($dossier))->renommee);
    }

    public function testLeJoueurNeDecidePasDeSaPropreDeclaration(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $sort = $this->sort();
        $this->declarer($client, $gn, $user, $dossier, $sort);

        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/decision', [
            'champ' => 'sorts',
            'element' => (string) $sort->getId(),
            'decision' => 'confirmer',
        ]);

        static::assertResponseRedirects('/access_denied');
        static::assertSame('en_attente', DecisionsDeclaration::lire(DecisionsDeclaration::depuis($this->recharger($dossier)->getDonnees()), 'sorts', (int) $sort->getId())['etat']);
    }

    public function testUneDecisionInconnueOuUnDossierEnvoyeNeChangeRien(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $sort = $this->sort();
        $this->declarer($client, $gn, $user, $dossier, $sort);

        $client->loginUser($this->staff());
        $this->decider($client, $gn, $dossier, 'sorts', (string) $sort->getId(), 'detruire');
        static::assertSame('en_attente', DecisionsDeclaration::lire(DecisionsDeclaration::depuis($this->recharger($dossier)->getDonnees()), 'sorts', (int) $sort->getId())['etat']);

        // Un dossier envoyé n'est plus modifiable : on prépare le jeton avant l'envoi.
        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/etape/declaration');
        $jeton = $crawler->filter('form[action$="/decision"] input[name="_token"]')->first()->attr('value');
        $envoye = $this->recharger($dossier);
        static::getContainer()->get(DepouillementService::class)->terminerSaisie($envoye, UserFactory::createOne());
        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/decision', [
            '_token' => $jeton,
            'champ' => 'sorts',
            'element' => (string) $sort->getId(),
            'decision' => 'refuser',
        ]);

        static::assertSame([$sort->getId()], $this->idsDesSorts($this->recharger($dossier)));
    }
}
