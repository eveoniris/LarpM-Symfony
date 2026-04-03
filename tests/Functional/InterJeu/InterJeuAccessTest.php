<?php

declare(strict_types=1);

namespace App\Tests\Functional\InterJeu;

use App\Tests\Factory\InterJeuFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests access control for /inter-jeu routes.
 */
#[Group('functional')]
class InterJeuAccessTest extends WebTestCase
{
    public function testInterJeuRoleCanAccessList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanAccessList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu');

        self::assertResponseIsSuccessful();
    }

    public function testUserCannotAccessList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => []]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu');

        self::assertResponseRedirects('/access_denied');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inter-jeu');

        self::assertResponseRedirects();
    }

    public function testListRendersWithoutError(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        InterJeuFactory::createMany(3);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.alert-danger');
    }
}
