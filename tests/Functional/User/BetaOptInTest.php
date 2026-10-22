<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * L'interface bêta est activable par n'importe quel compte, depuis son propre profil.
 */
#[Group('functional')]
class BetaOptInTest extends WebTestCase
{
    public function testUnJoueurSansRoleVoitLInterrupteurEtPeutActiverLaBeta(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER'], 'email' => 'joueur@example.test']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/user/' . $user->getId() . '/edit');
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('#input-ui-theme'));

        $client->request('POST', '/user/' . $user->getId() . '/edit', [
            'email' => 'joueur@example.test',
            'ui_theme_form' => '1',
            'ui_theme' => 'beta',
        ]);

        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        static::assertTrue($fresh->isUiBeta());

        // la case décochée (champ absent) ramène à l'interface actuelle
        $client->request('POST', '/user/' . $user->getId() . '/edit', ['email' => 'joueur@example.test', 'ui_theme_form' => '1']);
        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        static::assertFalse($fresh->isUiBeta());
    }

    public function testOnNePeutPasActiverLaBetaDUnAutreCompte(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);

        $client->request('POST', '/user/' . $cible->getId() . '/edit', ['email' => (string) $cible->getEmail(), 'ui_theme_form' => '1', 'ui_theme' => 'beta']);

        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $cible->getId());
        static::assertFalse($fresh->isUiBeta());
    }
}
