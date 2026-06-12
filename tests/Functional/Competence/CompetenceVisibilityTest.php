<?php

declare(strict_types=1);

namespace App\Tests\Functional\Competence;

use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for competence list visibility by role.
 *
 * Covers the fix: SCENARISTE sees all levels, ROLE_USER sees only Apprentice.
 * Buttons: SCENARISTE sees Détail but not Modifier/Supprimer.
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class CompetenceVisibilityTest extends WebTestCase
{
    public function testUserCanAccessCompetenceList(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCanAccessCompetenceList(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCannotSeeModifierButton(): void
    {
        $client = static::createClient();

        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'label' => 'Apprentice']);
        CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level, 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('a[title="Modifier"]'));
        static::assertCount(0, $crawler->filter('a[title="Supprimer"]'));
    }

    public function testScenaristeCanSeeDetailButton(): void
    {
        $client = static::createClient();

        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'label' => 'Apprentice']);
        CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level, 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('a[title="Détail"]')->count());
    }

    public function testAdminCanSeeModifierButton(): void
    {
        $client = static::createClient();

        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'label' => 'Apprentice']);
        CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level, 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_REGLE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('a[title="Modifier"]')->count());
    }

    public function testUserCannotSeeAnyAdminButton(): void
    {
        $client = static::createClient();

        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'label' => 'Apprentice']);
        CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level, 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/competence');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('a[title="Modifier"]'));
        static::assertCount(0, $crawler->filter('a[title="Supprimer"]'));
        static::assertCount(0, $crawler->filter('a[title="Détail"]'));
    }
}
