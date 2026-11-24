<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

/**
 * Matrice d'accès anonyme : chaque route de l'application est appelée sans être connecté.
 * Seules les routes de la liste blanche ci-dessous peuvent répondre autre chose qu'un refus
 * (redirection vers /login ou /access_denied, 401, 403).
 *
 * Ajouter une page publique demande donc deux gestes volontaires : une règle PUBLIC_ACCESS dans
 * config/packages/security.yaml et une entrée ici. Un oubli de garde sur une nouvelle route fait
 * échouer ce test.
 */
#[Group('functional')]
class MatriceAccesAnonymeTest extends WebTestCase
{
    /**
     * Routes volontairement publiques : accueil, connexion, inscription et récupération du compte,
     * webhook Discord, règles (classes, compétences, religions, documents) et carte du monde.
     */
    private const array PUBLIQUES = [
        'homepage',
        'app_login',
        'app_logout',
        'legal',
        'access_denied',
        'user.register',
        'user.register.resend',
        'user.forgot-password',
        'user.reset-password',
        'user.confirm-email',
        'user.resend-confirmation',
        'discord.interactions',
        'classe.index',
        'classe.list',
        'classe.image',
        'competence.list',
        'religion.list',
        'religion.blason',
        'rule.list',
        'rules.list',
        'rule.detail',
        'app_rule_detail',
        'rule.document',
        'world',
        'world.countries.json',
        'world.fiefs.json',
        'world.regions.json',
    ];

    /**
     * Préfixes traités ailleurs : l'API a ses propres règles et tests (réponses JSON 401),
     * les outils de debug n'existent pas en production.
     */
    private const array PREFIXES_EXCLUS = ['/api', '/_profiler', '/_wdt', '/_error'];

    public function testToutesLesRoutesNonPubliquesRefusentUnAnonyme(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        // Sans redémarrage du noyau entre deux requêtes : un millier de routes passent en quelques secondes.
        $client->disableReboot();
        $router = static::getContainer()->get(RouterInterface::class);

        $ouvertes = [];
        $verifiees = 0;
        foreach ($router->getRouteCollection()->all() as $nom => $route) {
            if (\in_array($nom, self::PUBLIQUES, strict: true) || $this->estExclue($route)) {
                continue;
            }

            $url = $this->url($router, $nom, $route);
            if (null === $url) {
                continue;
            }

            $methode = $route->getMethods()[0] ?? 'GET';
            $client->request($methode, $url);
            ++$verifiees;
            if (!$this->estRefusee($client)) {
                $ouvertes[] = \sprintf('%s %s (%s) => %d', $methode, $url, $nom, $client->getResponse()->getStatusCode());
            }

            $client->getCookieJar()->clear();
        }

        static::assertGreaterThan(500, $verifiees, 'La collection de routes semble incomplète.');
        static::assertSame([], $ouvertes, "Routes accessibles sans être connecté (ajouter une garde, ou les déclarer publiques) :\n" . implode("\n", $ouvertes));
    }

    public function testLesPagesPubliquesRestentAccessibles(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);

        foreach (['/', '/login', '/legal', '/user/register', '/user/forgot', '/classe', '/competence', '/religion', '/rule', '/world', '/world/fiefs.json'] as $url) {
            $client->request('GET', $url);
            static::assertFalse($this->estRefusee($client), $url . ' doit rester publique.');
            $client->restart();
        }
    }

    private function estExclue(Route $route): bool
    {
        foreach (self::PREFIXES_EXCLUS as $prefixe) {
            if (str_starts_with($route->getPath(), $prefixe)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Les paramètres reçoivent une valeur factice : un anonyme doit être refusé par le pare-feu
     * avant même la recherche de l'entité.
     */
    private function url(RouterInterface $router, string $nom, Route $route): ?string
    {
        foreach (['1', 'x'] as $valeur) {
            $params = array_fill_keys($route->compile()->getPathVariables(), $valeur);
            try {
                return $router->generate($nom, $params);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function estRefusee(KernelBrowser $client): bool
    {
        $reponse = $client->getResponse();
        if (\in_array($reponse->getStatusCode(), [401, 403], strict: true)) {
            return true;
        }

        $cible = (string) $reponse->headers->get('Location');

        return $reponse->isRedirection() && (str_contains($cible, '/login') || str_contains($cible, '/access_denied'));
    }
}
