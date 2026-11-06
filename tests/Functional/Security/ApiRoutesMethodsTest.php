<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Service\Api\ApiClientManager;
use App\Tests\Factory\GnFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Garde-fou sur toutes les routes /api : méthode HTTP déclarée et refus sans jeton.
 */
#[Group('functional')]
class ApiRoutesMethodsTest extends WebTestCase
{
    public function testToutesLesRoutesApiDeclarentLeursMethodes(): void
    {
        $client = static::createClient();
        $sansMethode = [];
        foreach ($this->routesApi($client->getContainer()->get(RouterInterface::class)) as $name => $route) {
            if ([] === $route->getMethods()) {
                $sansMethode[] = $name . ' ' . $route->getPath();
            }
        }

        static::assertSame([], $sansMethode, 'Routes /api sans methods: ' . implode(', ', $sansMethode));
    }

    public function testToutesLesRoutesApiProtegeesRefusentSansJeton(): void
    {
        $client = static::createClient();
        $publiques = ['/api/login_check', '/api/token/refresh', '/api/client/token', '/api/doc', '/api/doc.json'];
        $acceptes = [];

        foreach ($this->routesApi($client->getContainer()->get(RouterInterface::class)) as $route) {
            if (\in_array($route->getPath(), $publiques, true)) {
                continue;
            }
            $methods = $route->getMethods();
            $path = (string) preg_replace('/\{[^}]+\}/', '1', $route->getPath());
            $client->request($methods[0], $path);
            if (401 !== $client->getResponse()->getStatusCode()) {
                $acceptes[] = $methods[0] . ' ' . $path . ' => ' . $client->getResponse()->getStatusCode();
            }
        }

        static::assertSame([], $acceptes, 'Routes /api qui ne répondent pas 401 sans jeton : ' . implode(', ', $acceptes));
    }

    public function testCompteDeServiceApiAdminSeulNaPasAccesAuxAnciennesRoutes(): void
    {
        $client = static::createClient();
        ['client' => $app, 'secret' => $secret] = static::getContainer()->get(ApiClientManager::class)->create('app-droits-herites', ['ROLE_API_ADMIN']);
        $client->request('POST', '/api/client/token', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['client_id' => $app->getClientId(), 'client_secret' => $secret]));
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $gnId = GnFactory::createOne()->getId();

        $autorises = [];
        foreach ([
            '/api/fiefsState',
            '/api/qrCodeScanList',
            '/api/constructions',
            '/api/constructions/fiefs',
            '/api/gameItemWithoutPersonnage',
            '/api/personnages',
            '/api/ressource',
            '/api/mineurs/' . $gnId,
            '/api/sensible/' . $gnId,
        ] as $path) {
            $client->request('GET', $path, [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token']]);
            if (403 !== $client->getResponse()->getStatusCode()) {
                $autorises[] = $path . ' => ' . $client->getResponse()->getStatusCode();
            }
        }
        $client->request('POST', '/api/chronologies', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token'], 'CONTENT_TYPE' => 'application/json'], '{}');
        if (403 !== $client->getResponse()->getStatusCode()) {
            $autorises[] = 'POST /api/chronologies => ' . $client->getResponse()->getStatusCode();
        }

        static::assertSame([], $autorises, 'Routes héritées accessibles à un compte ROLE_API_ADMIN seul : ' . implode(', ', $autorises));
    }

    public function testChronologieSuppressionEstAtteignable(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $router->getContext()->setMethod('DELETE');

        static::assertSame('api.chronologie.delete', $router->match('/api/chronologies/1')['_route'] ?? null);
    }

    /**
     * @return iterable<string, \Symfony\Component\Routing\Route>
     */
    private function routesApi(RouterInterface $router): iterable
    {
        foreach ($router->getRouteCollection() as $name => $route) {
            if (str_starts_with($route->getPath(), '/api/') || '/api' === $route->getPath()) {
                yield $name => $route;
            }
        }
    }
}
