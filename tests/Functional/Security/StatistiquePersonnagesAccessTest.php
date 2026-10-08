<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Accès à l'extract des personnages (/stats/personnages).
 *
 * La page vit dans le panneau Scénarisation : elle est donc réservée à
 * ROLE_SCENARISTE et ROLE_ADMIN, en plus du ROLE_STATISTIQUE de la classe.
 */
#[Group('functional')]
class StatistiquePersonnagesAccessTest extends WebTestCase
{
    public function testScenaristeWithStatistiqueCanAccessExtract(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseIsSuccessful();
    }

    public function testAdminCanAccessExtract(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseIsSuccessful();
    }

    /**
     * Orga a bien ROLE_STATISTIQUE (accès au hub) mais pas la Scénarisation.
     */
    public function testOrgaWithStatistiqueIsDenied(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseRedirects('/access_denied');
    }

    public function testWargameWithStatistiqueIsDenied(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_WARGAME', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseRedirects('/access_denied');
    }

    public function testScenaristeWithoutStatistiqueIsDenied(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseRedirects('/access_denied');
    }

    public function testStatistiqueAloneIsDenied(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/personnages');

        static::assertResponseRedirects('/access_denied');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/stats/personnages');

        static::assertResponseRedirects();
    }

    #[DataProvider('provideExtractRoutes')]
    public function testEveryExtractRouteIsProtected(string $path, bool $redirects): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $client->request('GET', $path);

        if ($redirects) {
            static::assertResponseRedirects('/access_denied');

            return;
        }

        // /api/* passe l'access_control (utilisateur authentifié) puis se fait
        // refuser par le IsGranted du contrôleur : 403 plutôt qu'une redirection.
        static::assertResponseStatusCodeSame(403);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideExtractRoutes(): iterable
    {
        yield 'html' => ['/stats/personnages', true];
        yield 'csv' => ['/stats/personnages/csv', true];
        yield 'json' => ['/stats/personnages/json', true];
        yield 'api' => ['/api/personnages', false];
    }

    public function testPageRendersThePersonnages(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $personnage = PersonnageFactory::createOne([
            'nom' => 'PersonnageExtractTest',
            'classe' => ClasseFactory::createOne(['label_masculin' => 'Roublard', 'label_feminin' => 'Roublarde']),
            'genre' => GenreFactory::createOne(['label' => 'Masculin']),
            'age' => AgeFactory::createOne(['label' => 'Adulte']),
            'territoire' => TerritoireFactory::createOne(['nom' => 'Terre dEssai']),
        ]);
        CompetenceFamilyFactory::createOne(['label' => 'Essai']);

        $crawler = $client->request('GET', '/stats/personnages');

        static::assertResponseIsSuccessful();
        $body = $crawler->filter('body')->html();
        static::assertStringContainsString('PersonnageExtractTest', $body);
        static::assertStringContainsString('Roublard', $body);
        static::assertStringContainsString('Terre dEssai', $body);
        static::assertStringContainsString('Essai', $body);
    }

    public function testCsvExportIsServedAsCsvAttachment(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        CompetenceFamilyFactory::createOne(['label' => 'CompFamCsv']);
        PersonnageFactory::createOne(['nom' => 'PersonnageCsv']);

        $client->request('GET', '/stats/personnages/csv');
        $response = $client->getResponse();

        static::assertResponseIsSuccessful();

        // Le corps part en StreamedResponse : il n'est pas récupérable via
        // getContent(), le contenu réel est donc vérifié par le test
        // d'intégration du StatsService. Ici on contrôle l'en-tête HTTP et le
        // nom de fichier, qui valident le titre passé à sendCsv().
        static::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        static::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        static::assertMatchesRegularExpression('/attachment; filename=personnages-extract-\d{8}\.csv/', (string) $response->headers->get('Content-Disposition'));
    }

    public function testJsonExportIsValidJson(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        PersonnageFactory::createOne(['nom' => 'PersonnageJson']);

        $client->request('GET', '/stats/personnages/json');
        $content = (string) $client->getResponse()->getContent();

        static::assertResponseIsSuccessful();
        $decoded = json_decode($content, true);
        static::assertIsArray($decoded);
        static::assertArrayHasKey('xpTotal', $decoded[0]);
        static::assertArrayHasKey('nbPersosMortsJoueur', $decoded[0]);
    }

    public function testApiRouteReturnsJson(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/api/personnages');

        static::assertResponseIsSuccessful();
        static::assertIsArray(json_decode((string) $client->getResponse()->getContent(), true));
    }

    public function testGnFilterIsAccepted(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $gn = GnFactory::createOne();

        $client->request('GET', '/stats/personnages?gn=' . $gn->getId());

        static::assertResponseIsSuccessful();
    }

    public function testEmptyGnFilterIsAccepted(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/personnages?gn=');

        static::assertResponseIsSuccessful();
    }
}
