<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Personnage actif sur LarpManager : notion purement applicative (au nom de qui le
 * joueur agit dans l'outil), à ne pas confondre avec le personnage principal d'une
 * participation à un GN.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class PersonnageActifTest extends WebTestCase
{
    public function testSeulsLesPersonnagesVivantsSontProposes(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        PersonnageFactory::createOne(['user' => $user, 'nom' => 'Vivant', 'vivant' => true]);
        PersonnageFactory::createOne(['user' => $user, 'nom' => 'Trepasse', 'vivant' => false]);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/user/' . $user->getId() . '/personage/default');

        static::assertResponseIsSuccessful();

        $texte = $crawler->filter('form')->text();
        static::assertStringContainsString('Vivant', $texte);
        static::assertStringNotContainsString('Trepasse', $texte);
    }

    public function testLaListeEstLimiteeAuxCinqDerniersPourUnJoueur(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        for ($i = 1; $i <= 8; ++$i) {
            PersonnageFactory::createOne(['user' => $user, 'nom' => 'Perso' . $i]);
        }

        $client->loginUser($user);
        $crawler = $client->request('GET', '/user/' . $user->getId() . '/personage/default');

        // 5 personnages + le choix « Aucun ».
        static::assertCount(6, $crawler->filter('form input[type=radio]'));
    }

    public function testUnScenaristeNEstPasLimite(): void
    {
        $client = static::createClient();

        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        for ($i = 1; $i <= 8; ++$i) {
            PersonnageFactory::createOne(['user' => $scenariste, 'nom' => 'Perso' . $i]);
        }

        $client->loginUser($scenariste);
        $crawler = $client->request('GET', '/user/' . $scenariste->getId() . '/personage/default');

        static::assertCount(9, $crawler->filter('form input[type=radio]'));
    }

    public function testLeDernierJoueApparaitEnPremier(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $user = UserFactory::createOne();

        $ancien = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Ancien']);
        $recent = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Recent']);

        ParticipantFactory::createOne(['gn' => $gn, 'user' => $user, 'personnage' => $ancien]);
        ParticipantFactory::createOne(['gn' => GnFactory::createOne(), 'user' => $user, 'personnage' => $recent]);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/user/' . $user->getId() . '/personage/default');

        $libelles = $crawler->filter('form label')->each(static fn ($node) => $node->text());
        $noms = array_values(array_filter($libelles, static fn (string $l) => str_contains($l, 'Ancien') || str_contains($l, 'Recent')));

        static::assertStringContainsString('Recent', $noms[0]);
    }

    public function testLePersonnageActifResteProposeMemeHorsLimite(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $actif = PersonnageFactory::createOne(['user' => $user, 'nom' => 'DejaActif']);
        for ($i = 1; $i <= 8; ++$i) {
            PersonnageFactory::createOne(['user' => $user, 'nom' => 'Perso' . $i]);
        }

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user->setPersonnage($actif);
        $em->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/user/' . $user->getId() . '/personage/default');

        static::assertStringContainsString('DejaActif', $crawler->filter('form')->text());
    }

    public function testLaBasculeRapideDepuisLeMenuChangeLePersonnageActif(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $cible = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Cible']);

        $client->loginUser($user);

        // On passe par le formulaire réellement rendu dans le menu : cela valide du
        // même coup la présence du sélecteur et la génération du jeton CSRF.
        $crawler = $client->request('GET', '/');
        $bouton = $crawler->filter(sprintf(
            'form[action="/user/personnageActif/%d"] button[type=submit]',
            $cible->getId(),
        ));

        static::assertCount(1, $bouton, 'Le sélecteur rapide du menu ne propose pas le personnage.');

        $client->submit($bouton->form());

        static::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $rafraichi = $em->getRepository(User::class)->find($user->getId());

        static::assertSame($cible->getId(), $rafraichi->getPersonnage(true)?->getId());
    }

    public function testLaBasculeRefuseUnJetonInvalide(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $cible = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Cible']);

        $client->loginUser($user);
        $client->request('POST', '/user/personnageActif/' . $cible->getId(), ['_token' => 'invalide']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $rafraichi = $em->getRepository(User::class)->find($user->getId());

        static::assertNull($rafraichi->getPersonnage(true));
    }

    public function testLaBasculeRefuseLePersonnageDUnAutreJoueur(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $autrui = PersonnageFactory::createOne(['user' => UserFactory::createOne(), 'nom' => 'Autrui']);

        $client->loginUser($user);
        $client->request('POST', '/user/personnageActif/' . $autrui->getId(), ['_token' => 'peu importe']);

        static::assertResponseRedirects('/access_denied');
    }

    public function testLaBasculeRefuseUnPersonnageMort(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $mort = PersonnageFactory::createOne(['user' => $user, 'nom' => 'Mort', 'vivant' => false]);

        $client->loginUser($user);
        $client->request('POST', '/user/personnageActif/' . $mort->getId(), ['_token' => 'peu importe']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $rafraichi = $em->getRepository(User::class)->find($user->getId());

        static::assertNull($rafraichi->getPersonnage(true));
    }
}
