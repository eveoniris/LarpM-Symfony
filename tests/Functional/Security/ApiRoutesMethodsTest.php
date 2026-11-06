<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

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
