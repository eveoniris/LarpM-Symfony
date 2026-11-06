<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiRefreshTokenTest extends WebTestCase
{
    public function testLoginRenvoieUnRefreshToken(): void
    {
        $client = static::createClient();

        $data = $this->login($client, 'refresh1@example.com');

        static::assertNotEmpty($data['token']);
        static::assertNotEmpty($data['refresh_token']);
    }

    public function testRefreshDonneUnNouveauJetonUtilisable(): void
    {
        $client = static::createClient();
        $data = $this->login($client, 'refresh2@example.com');

        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['refresh_token' => $data['refresh_token']]));

        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array<string, string> $new */
        $new = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertNotEmpty($new['token']);

        $client->request('GET', '/api/joueur/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $new['token']]);
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testRefreshTokenAUsageUnique(): void
    {
        $client = static::createClient();
        $data = $this->login($client, 'refresh3@example.com');
        $body = (string) json_encode(['refresh_token' => $data['refresh_token']]);

        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        static::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testRefreshTokenInconnuRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['refresh_token' => 'inconnu']));

        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    /** @return array<string, string> */
    private function login(KernelBrowser $client, string $email): array
    {
        UserFactory::createOne([
            'email' => $email,
            'pwd' => password_hash('test123', \PASSWORD_BCRYPT),
            'roles' => ['ROLE_USER'],
        ]);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['username' => $email, 'password' => 'test123']));

        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data;
    }
}
