<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiAdminGroupesTest extends WebTestCase
{
    public function testSansJetonRenvoie401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/groupes');

        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testSansRoleApiAdminRenvoie403(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'api-user@example.com', ['ROLE_ADMIN']);

        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAvecRoleApiAdminRenvoieLaListe(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'api-admin@example.com', ['ROLE_API_ADMIN']);

        $client->request('GET', '/api/admin/groupes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertArrayHasKey('items', $data);
        static::assertArrayHasKey('total', $data);
    }

    public function testFiltreParGnEtPagination(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'api-admin2@example.com', ['ROLE_API_ADMIN']);

        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $g1 = GroupeFactory::createOne(['numero' => 9001, 'nom' => 'Alpha']);
        $g2 = GroupeFactory::createOne(['numero' => 9002, 'nom' => 'Beta']);
        $g3 = GroupeFactory::createOne(['numero' => 9003, 'nom' => 'Gamma']);
        GroupeGnFactory::createOne(['groupe' => $g1, 'gn' => $gn]);
        GroupeGnFactory::createOne(['groupe' => $g2, 'gn' => $gn]);
        GroupeGnFactory::createOne(['groupe' => $g3, 'gn' => $autreGn]);
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];

        // Filtre par GN : seuls les groupes du GN, triés par numéro.
        $client->request('GET', '/api/admin/groupes?gn=' . $gn->getId(), [], [], $auth);
        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{total: int, items: list<array{numero: int, nom: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertSame(2, $data['total']);
        static::assertSame([9001, 9002], array_column($data['items'], 'numero'));
        static::assertSame('Alpha', $data['items'][0]['nom']);

        // Pagination : page 2 avec limit 1 renvoie le second groupe, total inchangé.
        $client->request('GET', '/api/admin/groupes?gn=' . $gn->getId() . '&page=2&limit=1', [], [], $auth);
        /** @var array{total: int, items: list<array{numero: int}>} $page2 */
        $page2 = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertSame(2, $page2['total']);
        static::assertSame([9002], array_column($page2['items'], 'numero'));
    }

    /** @param list<string> $roles */
    private function token(KernelBrowser $client, string $email, array $roles): string
    {
        UserFactory::createOne([
            'email' => $email,
            'pwd' => password_hash('test123', \PASSWORD_BCRYPT),
            'roles' => $roles,
        ]);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['username' => $email, 'password' => 'test123']));
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return $data['token'];
    }
}
