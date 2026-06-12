<?php

declare(strict_types=1);

namespace App\Tests\Functional\Territoire;

use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for territoire detail page access.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class TerritoireTest extends WebTestCase
{
    public function testUserCanAccessTerritoireDetail(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $territoire = TerritoireFactory::createOne();

        $client->loginUser($user);
        $client->request('GET', '/territoire/' . $territoire->getId());

        static::assertResponseIsSuccessful();
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $territoire = TerritoireFactory::createOne();
        $client->request('GET', '/territoire/' . $territoire->getId());

        static::assertResponseRedirects();
    }
}
