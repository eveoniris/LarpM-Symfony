<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validation et renvoi depuis le récapitulatif : droits (scénariste, wargame, admin), boutons, définitif.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementValidationTest extends WebTestCase
{
    private function utilisateur(string ...$roles): User
    {
        return UserFactory::createOne(['username' => 'valideur', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST', ...$roles]]);
    }

    private function depouillement(DepouillementStatut $statut = DepouillementStatut::A_VALIDER): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true)
            ->setStatut($statut);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($depouillement);
        $em->flush();

        return $depouillement;
    }

    private function recapitulatif(Depouillement $depouillement): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/etape/recapitulatif';
    }

    private function statut(Depouillement $depouillement): DepouillementStatut
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Depouillement::class, $depouillement->getId())->getStatut();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function rolesQuiValident(): iterable
    {
        yield 'scénariste' => ['ROLE_SCENARISTE'];
        yield 'wargame' => ['ROLE_WARGAME'];
        yield 'admin' => ['ROLE_ADMIN'];
    }

    #[DataProvider('rolesQuiValident')]
    public function testLesBoutonsDeValidationSontProposesAuxValideurs(string $role): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur($role));

        $client->request('GET', $this->recapitulatif($depouillement));

        static::assertSelectorTextContains('body', 'Valider le dépouillement');
        static::assertSelectorTextContains('body', 'Renvoyer au dépouilleur');
    }

    public function testUnSimpleBetaTesteurNeVoitPasLesBoutonsEtNePeutPasValider(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur());

        $client->request('GET', $this->recapitulatif($depouillement));
        static::assertSelectorNotExists('form[action$="/valider"]');
        static::assertSelectorNotExists('form[action$="/renvoyer"]');

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/valider');
        static::assertResponseRedirects('/access_denied');
        static::assertSame(DepouillementStatut::A_VALIDER, $this->statut($depouillement));
    }

    public function testValiderDepuisLeRecapitulatif(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur('ROLE_SCENARISTE'));

        $crawler = $client->request('GET', $this->recapitulatif($depouillement));
        $client->submit($crawler->filter('form[action$="/valider"]')->form());

        static::assertResponseRedirects($this->recapitulatif($depouillement));
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Dépouillement validé. Il ne peut plus être rouvert.');
        static::assertSelectorTextContains('body', 'par valideur le');
        static::assertSelectorNotExists('form[action$="/valider"]');
        static::assertSelectorNotExists('form[action$="/renvoyer"]');
        static::assertSame(DepouillementStatut::VALIDE, $this->statut($depouillement));
    }

    public function testRenvoyerAvecUnMotifPuisLeDepouilleurLeVoit(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur('ROLE_WARGAME'));

        $crawler = $client->request('GET', $this->recapitulatif($depouillement));
        $form = $crawler->filter('form[action$="/renvoyer"]')->form();
        $form['motif'] = 'Photo des potions manquante.';
        $client->submit($form);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Dépouillement renvoyé au dépouilleur pour correction.');
        static::assertSelectorTextContains('body', 'Renvoyé au dépouilleur : Photo des potions manquante.');
        static::assertSame(DepouillementStatut::EN_DEPOUILLEMENT, $this->statut($depouillement));
        static::assertSelectorExists('button:contains("Terminer le dépouillement")');
    }

    public function testRenvoyerSansMotifEstRefuse(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur('ROLE_ADMIN'));

        $crawler = $client->request('GET', $this->recapitulatif($depouillement));
        $form = $crawler->filter('form[action$="/renvoyer"]')->form();
        $form['motif'] = '   ';
        $client->submit($form);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'motif du renvoi est obligatoire');
        static::assertSame(DepouillementStatut::A_VALIDER, $this->statut($depouillement));
    }

    public function testUnDepouillementValideNeSeRouvrePas(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser($this->utilisateur('ROLE_ADMIN'));

        // Jeton valide récupéré tant que le dépouillement est à valider, puis il passe à "validé".
        $crawler = $client->request('GET', $this->recapitulatif($depouillement));
        $jeton = $crawler->filter('form[action$="/renvoyer"] input[name=_token]')->attr('value');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $em->find(Depouillement::class, $depouillement->getId())->setStatut(DepouillementStatut::VALIDE);
        $em->flush();

        $client->request('GET', $this->recapitulatif($depouillement));
        static::assertSelectorNotExists('form[action$="/renvoyer"]');
        static::assertSelectorNotExists('form[action$="/terminer"]');

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/renvoyer', ['motif' => 'Retour en arrière', '_token' => $jeton]);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'seul un dépouillement « à valider »');
        static::assertSame(DepouillementStatut::VALIDE, $this->statut($depouillement));
    }

    public function testSansLeRoleBetaMemeUnAdminNeValidePas(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/valider');

        static::assertResponseRedirects('/access_denied');
        static::assertSame(DepouillementStatut::A_VALIDER, $this->statut($depouillement));
    }
}
