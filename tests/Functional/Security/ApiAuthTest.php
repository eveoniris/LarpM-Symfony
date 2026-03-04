<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Functional tests for JWT API authentication.
 *
 * Requires config/jwt/private.pem and public.pem (generated via lexik:jwt:generate-keypair).
 *
 * @group functional
 */
class ApiAuthTest extends WebTestCase
{
    use Factories;

    // -------------------------------------------------------------------------
    // Login check
    // -------------------------------------------------------------------------

    public function testValidCredentialsReturnToken(): void
    {
        $client = static::createClient();
        $hashedPassword = password_hash('test123', PASSWORD_BCRYPT);
        UserFactory::createOne([
            'email' => 'apitest@example.com',
            'pwd'   => $hashedPassword,
            'roles' => ['ROLE_USER'],
        ]);

        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['username' => 'apitest@example.com', 'password' => 'test123']),
        );

        self::assertResponseIsSuccessful();
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testInvalidPasswordReturns401(): void
    {
        $client = static::createClient();
        $hashedPassword = password_hash('test123', PASSWORD_BCRYPT);
        UserFactory::createOne([
            'email' => 'apifail@example.com',
            'pwd'   => $hashedPassword,
        ]);

        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['username' => 'apifail@example.com', 'password' => 'wrongpassword']),
        );

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testUnknownUserReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['username' => 'nobody@example.com', 'password' => 'any']),
        );

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Protected API endpoints
    // -------------------------------------------------------------------------

    public function testProtectedEndpointWithoutTokenReturns401(): void
    {
        // AccessDeniedListener skips /api/* paths so the JWT entry point can return 401.
        $client = static::createClient();
        $gn = GnFactory::createOne();

        $client->request('GET', '/api/competences/' . $gn->getId());

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testProtectedEndpointWithValidTokenSucceeds(): void
    {
        $client = static::createClient();
        $hashedPassword = password_hash('test123', PASSWORD_BCRYPT);
        UserFactory::createOne([
            'email' => 'apiok@example.com',
            'pwd'   => $hashedPassword,
            'roles' => ['ROLE_USER'],
        ]);

        // Obtain a valid JWT token
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['username' => 'apiok@example.com', 'password' => 'test123']),
        );
        /** @var array<string, string> $tokenData */
        $tokenData = json_decode((string) $client->getResponse()->getContent(), true);
        $token = $tokenData['token'];

        $gn = GnFactory::createOne();
        $client->request('GET', '/api/competences/' . $gn->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertNotSame(401, $client->getResponse()->getStatusCode());
    }

    public function testProtectedEndpointWithInvalidTokenReturns401(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();

        $client->request('GET', '/api/competences/' . $gn->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer this.is.not.a.valid.jwt',
        ]);

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }
}
