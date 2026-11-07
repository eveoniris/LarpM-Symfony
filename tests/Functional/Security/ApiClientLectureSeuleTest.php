<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Gn;
use App\Service\Api\ApiClientManager;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ROLE_API_READ : lecture seule de /api/admin/groupes, limitée au GN de l'application.
 */
#[Group('functional')]
class ApiClientLectureSeuleTest extends WebTestCase
{
    public function testLectureSeuleNeVoitQueSonGn(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['numero' => 9101, 'nom' => 'Dans mon GN']), 'gn' => $gn]);
        GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(['numero' => 9102, 'nom' => 'Autre GN']), 'gn' => $autreGn]);
        $token = $this->jeton($client, 'app-lecture', ['ROLE_API_READ'], $gn);

        // Sans paramètre : le GN de l'application est appliqué d'office.
        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{total: int, items: list<array{numero: int}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertSame([9101], array_column($data['items'], 'numero'));

        // Avec son propre GN : accepté.
        $client->request('GET', '/api/admin/groupes?gn=' . $gn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(200, $client->getResponse()->getStatusCode());

        // Avec un autre GN : refusé.
        $client->request('GET', '/api/admin/groupes?gn=' . $autreGn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testLectureSeuleSansGnEstRefuseALaCreation(): void
    {
        static::createClient();

        $this->expectException(InvalidArgumentException::class);
        static::getContainer()->get(ApiClientManager::class)->create('app-lecture-sans-gn', ['ROLE_API_READ']);
    }

    public function testLectureSeuleNaPasAccesAuxAutresRoutes(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $token = $this->jeton($client, 'app-lecture-limitee', ['ROLE_API_READ'], $gn);

        foreach (['/api/fiefsState', '/api/qrCodeScanList', '/api/mineurs/' . $gn->getId(), '/api/sensible/' . $gn->getId()] as $path) {
            $client->request('GET', $path, [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
            static::assertSame(403, $client->getResponse()->getStatusCode(), $path);
        }
    }

    public function testRevocationCoupeLaLectureSeule(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get(ApiClientManager::class);
        $gn = GnFactory::createOne();
        ['client' => $app, 'secret' => $secret] = $manager->create('app-lecture-revoquee', ['ROLE_API_READ'], $gn);
        $token = $this->echanger($client, $app->getClientId(), $secret);

        $manager->revoke($app);

        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminVoitToujoursTousLesGn(): void
    {
        $client = static::createClient();
        $token = $this->jeton($client, 'app-admin-complet', ['ROLE_API_ADMIN'], null);

        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @param list<string> $roles
     */
    private function jeton(KernelBrowser $client, string $name, array $roles, ?Gn $gn): string
    {
        ['client' => $app, 'secret' => $secret] = static::getContainer()->get(ApiClientManager::class)->create($name, $roles, $gn);

        return $this->echanger($client, $app->getClientId(), $secret);
    }

    private function echanger(KernelBrowser $client, string $clientId, string $secret): string
    {
        $client->request('POST', '/api/client/token', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['client_id' => $clientId, 'client_secret' => $secret]));
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['token'];
    }
}
