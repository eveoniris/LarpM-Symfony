<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for personnage detail page access control.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class PersonnageAccessTest extends WebTestCase
{
    public function testPlayerCanViewOwnPersonnageDetail(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseIsSuccessful();
    }

    public function testPlayerCannotViewAnotherPlayerPersonnage(): void
    {
        $client = static::createClient();

        $owner = UserFactory::createOne();
        $viewer = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $owner]);

        // hasAccess() restricts to owner or SCENARISTE/ORGA - other players get redirected
        $client->loginUser($viewer);
        $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseRedirects('/access_denied');
    }

    public function testScenaristeCanViewAnyPersonnage(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $owner = UserFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $personnage = PersonnageFactory::createOne(['user' => $owner]);

        $client->loginUser($scenariste);
        $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseIsSuccessful();
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseRedirects();
    }

    public function testDetailAliasRouteWorks(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/' . $personnage->getId() . '/detail');

        static::assertResponseIsSuccessful();
    }
}
