<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Accès au hub Larp > Statistique.
 *
 * ROLE_STATISTIQUE conditionne l'accès au module ; les panneaux restent
 * cloisonnés par rôle métier (admin, orga, scénariste, wargame).
 */
#[Group('functional')]
class StatistiqueAccessTest extends WebTestCase
{
    /**
     * L'administrateur hérite de ROLE_STATISTIQUE via role_hierarchy.
     */
    public function testAdminCanAccessStatsList(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
    }

    public function testAdminInheritsStatistiqueRole(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
    }

    #[DataProvider('provideBusinessRoles')]
    public function testBusinessRoleWithoutStatistiqueIsDenied(string $role): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => [$role]]));

        $client->request('GET', '/stats/list');

        static::assertResponseRedirects('/access_denied');
    }

    #[DataProvider('provideBusinessRoles')]
    public function testBusinessRoleWithStatistiqueIsAllowed(string $role): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => [$role, 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
    }

    public function testStatistiqueAloneShowsNoPanel(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Gestion orga', $crawler->filter('body')->html());
        static::assertStringNotContainsString('Scénarisation', $crawler->filter('body')->html());
        static::assertStringNotContainsString('Jeu de domaine', $crawler->filter('body')->html());
    }

    public function testOrgaWithStatistiqueDoesNotSeeScenarisationPanel(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA', 'ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Gestion orga', $crawler->filter('body')->html());
        static::assertStringNotContainsString('Scénarisation', $crawler->filter('body')->html());
    }

    public function testScenaristeWithStatistiqueSeesScenarisationPanelAndExtractLink(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/stats/list');

        static::assertResponseIsSuccessful();
        $body = $crawler->filter('body')->html();
        static::assertStringContainsString('Scénarisation', $body);
        static::assertStringContainsString('Extract des personnages', $body);
    }

    public function testPlainUserIsDenied(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => []]));

        $client->request('GET', '/stats/list');

        static::assertResponseRedirects('/access_denied');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/stats/list');

        static::assertResponseRedirects();
    }

    public function testLarpMenuIsVisibleWithStatistique(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('/stats/list', $crawler->filter('body')->html());
    }

    public function testLarpMenuIsHiddenWithoutStatistique(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => []]));

        $crawler = $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('/stats/list', $crawler->filter('body')->html());
    }

    public function testAdminOnlyMenuEntriesAreHiddenForStatistiqueAlone(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        $body = $crawler->filter('body')->html();
        static::assertStringNotContainsString('/admin/action/logs', $body);
        static::assertStringNotContainsString('/install', $body);
    }

    public function testAdminOnlyMenuEntriesAreVisibleForAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('/admin/action/logs', $crawler->filter('body')->html());
    }

    /** @return iterable<string, array{string}> */
    public static function provideBusinessRoles(): iterable
    {
        yield 'ORGA' => ['ROLE_ORGA'];
        yield 'SCENARISTE' => ['ROLE_SCENARISTE'];
        yield 'WARGAME' => ['ROLE_WARGAME'];
    }
}
