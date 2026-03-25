<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression test for GroupeRepository hydration bug:
 * addSelect() on ManyToOne joins caused Doctrine to return mixed arrays
 * instead of Groupe objects, breaking the /groupe list template.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class GroupeListTest extends WebTestCase
{
    public function testScenaristeCanAccessGroupeList(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        GroupeFactory::createMany(3);

        $client->loginUser($user);
        $client->request('GET', '/groupe');

        self::assertResponseIsSuccessful();
    }

    public function testUserCannotAccessGroupeList(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => []]);
        $client->loginUser($user);
        $client->request('GET', '/groupe');

        // ROLE_SCENARISTE required — app redirects to /access_denied
        self::assertResponseRedirects('/access_denied');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/groupe');

        self::assertResponseRedirects();
    }

    public function testGroupeListRendersWithoutHydrationError(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        // Create several groupes to exercise the paginated repository query
        GroupeFactory::createMany(5);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/groupe');

        self::assertResponseIsSuccessful();
        // Page must not show a Twig "Undefined array key" or similar error
        self::assertSelectorNotExists('.alert-danger');
    }
}
