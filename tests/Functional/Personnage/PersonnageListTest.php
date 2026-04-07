<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for personnage list and scenariste filter.
 *
 * NOTE: the scenariste filter joins through participant → groupeGn → groupe → scenariste.
 * Personnages created by factory have no participants so they never match the filter
 * (inner join excludes them). Behaviour tests therefore focus on HTTP-level correctness:
 * the filter must not crash, must honour access control, and must render the form field.
 */
#[Group('functional')]
class PersonnageListTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Access control
    // -------------------------------------------------------------------------

    public function testListIsAccessibleToScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/list');

        self::assertResponseIsSuccessful();
    }

    public function testListIsAccessibleToOrga(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/list');

        self::assertResponseIsSuccessful();
    }

    public function testListDeniedToPlainUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/list');

        self::assertResponseRedirects('/access_denied');
    }

    public function testListRedirectsAnonymous(): void
    {
        $client = static::createClient();

        $client->request('GET', '/personnage/list');

        self::assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // Scenariste filter — form rendering & query safety
    // -------------------------------------------------------------------------

    public function testScenaristeFilterRendersFormField(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/personnage/list');

        self::assertResponseIsSuccessful();
        // The form field for scenariste must be present in the rendered page
        self::assertSelectorExists('select[name="personnage_find[scenariste]"]');
    }

    public function testScenaristeFilterWithValidIdReturns200(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/personnage/list', [
            'personnage_find' => ['scenariste' => $scenariste->getId()],
        ]);

        // Must not throw a SQL/DQL error even though no personnages match
        self::assertResponseIsSuccessful();
    }

    public function testScenaristeFilterWithNonExistentIdReturns200(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/list', [
            'personnage_find' => ['scenariste' => 999999],
        ]);

        // entityManager->find returns null → criteria not set → no crash
        self::assertResponseIsSuccessful();
    }

    public function testScenaristeFilterShowsNoResultsForUserWithNoGroupe(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        // Personnage exists but has no participant → not linked to any groupe via participant chain
        PersonnageFactory::createOne();

        $client->loginUser($scenariste);
        $client->request('GET', '/personnage/list', [
            'personnage_find' => ['scenariste' => $scenariste->getId()],
        ]);

        self::assertResponseIsSuccessful();
        // Page renders but the table body has no data rows for this scenariste
        self::assertSelectorNotExists('table tbody tr td a[href*="/personnage/"]');
    }
}
