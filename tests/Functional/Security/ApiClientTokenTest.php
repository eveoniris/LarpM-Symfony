<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\ApiClient;
use App\Service\Api\ApiClientManager;
use PHPUnit\Framework\Attributes\Group;
use SensitiveParameter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiClientTokenTest extends WebTestCase
{
    public function testEchangeCleContreJetonPuisAccesAdmin(): void
    {
        $client = static::createClient();
        ['client' => $app, 'secret' => $secret] = $this->creer('app-service-1', ['ROLE_API_ADMIN']);

        $token = $this->echanger($client, $app->getClientId(), $secret);

        static::assertNotNull($token);
        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testApplicationSansRoleNaPasAccesAdmin(): void
    {
        $client = static::createClient();
        ['client' => $app, 'secret' => $secret] = $this->creer('app-sans-role', []);

        $token = $this->echanger($client, $app->getClientId(), $secret);

        static::assertNotNull($token);
        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSecretIncorrectRenvoie401(): void
    {
        $client = static::createClient();
        ['client' => $app] = $this->creer('app-mauvais-secret', ['ROLE_API_ADMIN']);

        static::assertNull($this->echanger($client, $app->getClientId(), 'mauvais'));
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testClientIdInconnuRenvoie401(): void
    {
        $client = static::createClient();

        static::assertNull($this->echanger($client, 'lm_inconnu', 'x'));
        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testRevocationBloqueEchangeEtJetonDejaEmis(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get(ApiClientManager::class);
        ['client' => $app, 'secret' => $secret] = $this->creer('app-revoquee', ['ROLE_API_ADMIN']);
        $token = $this->echanger($client, $app->getClientId(), $secret);
        static::assertNotNull($token);

        $manager->revoke($app);

        // Un nouvel échange est refusé.
        static::assertNull($this->echanger($client, $app->getClientId(), $secret));
        // Le jeton déjà émis perd l'accès aux routes protégées par rôle.
        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSecretStockeEnEmpreinteSeulement(): void
    {
        static::createClient();
        ['client' => $app, 'secret' => $secret] = $this->creer('app-empreinte', []);

        static::assertNotSame($secret, $app->getSecretHash());
        static::assertSame(hash('sha256', $secret), $app->getSecretHash());
    }

    /**
     * @param list<string> $roles
     *
     * @return array{client: ApiClient, secret: string}
     */
    private function creer(string $name, array $roles): array
    {
        return static::getContainer()->get(ApiClientManager::class)->create($name, $roles);
    }

    private function echanger(KernelBrowser $client, string $clientId, #[SensitiveParameter] string $secret): ?string
    {
        $client->request('POST', '/api/client/token', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['client_id' => $clientId, 'client_secret' => $secret]));

        /** @var array<string, string>|null $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['token'] ?? null;
    }
}
