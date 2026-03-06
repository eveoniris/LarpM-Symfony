<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for role-based access control.
 *
 * Uses Symfony's WebTestCase and loginUser() — no password hashing needed.
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 *
 * @group functional
 */
class RoleAccessTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Anonymous access
    // -------------------------------------------------------------------------

    public function testAnonymousCannotAccessAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        // AccessDeniedListener intercepts all AccessDeniedException (even for anonymous)
        // and redirects to /access_denied — not /login.
        self::assertResponseRedirects();
    }

    public function testAnonymousCannotAccessPersonnageList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/personnage/list');

        self::assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // ROLE_USER — should be forbidden from admin
    // -------------------------------------------------------------------------

    public function testRoleUserCannotAccessAdmin(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $client->request('GET', '/admin');

        // Must NOT be 200 — either redirect (302) or forbidden (403)
        self::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // ROLE_ADMIN — should access /admin
    // -------------------------------------------------------------------------

    public function testRoleAdminCanAccessAdmin(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($user);

        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // ROLE_SCENARISTE — should access /intrigue but not /admin
    // -------------------------------------------------------------------------

    public function testRoleScenaristeCanAccessIntrigue(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/intrigue');

        self::assertResponseIsSuccessful();
    }

    public function testRoleScenaristeCannotAccessAdmin(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/admin');

        self::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // ROLE_TERRITOIRE — should access territory routes
    // -------------------------------------------------------------------------

    public function testRoleTerritoireCanAccessTerritoireList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_TERRITOIRE']]);
        $client->loginUser($user);

        $client->request('GET', '/territoire');

        self::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Login page
    // -------------------------------------------------------------------------

    public function testLoginPageIsPubliclyAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }
}
