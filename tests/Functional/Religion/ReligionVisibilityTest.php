<?php

declare(strict_types=1);

namespace App\Tests\Functional\Religion;

use App\Tests\Factory\ReligionFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for religion list visibility by role.
 *
 * Covers the fix: SCENARISTE can see secret religions, ROLE_USER cannot.
 * Buttons: SCENARISTE sees Détail but not Modifier/Supprimer.
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class ReligionVisibilityTest extends WebTestCase
{
    public function testUserCannotSeeSecretReligion(): void
    {
        $client = static::createClient();

        ReligionFactory::createOne(['label' => 'Culte Interdit', 'secret' => true]);
        ReligionFactory::createOne(['label' => 'Foi du Soleil', 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $client->request('GET', '/religion');

        static::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        static::assertStringNotContainsString('Culte Interdit', $content);
        static::assertStringContainsString('Foi du Soleil', $content);
    }

    public function testScenaristeCanSeeSecretReligion(): void
    {
        $client = static::createClient();

        ReligionFactory::createOne(['label' => 'Culte Interdit', 'secret' => true]);

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/religion');

        static::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        static::assertStringContainsString('Culte Interdit', $content);
    }

    public function testScenaristeCannotSeeModifierButton(): void
    {
        $client = static::createClient();

        ReligionFactory::createOne(['label' => 'Culte Test', 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/religion');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('a[title="Modifier"]'));
        static::assertCount(0, $crawler->filter('a[title="Supprimer"]'));
    }

    public function testScenaristeCanSeeDetailButton(): void
    {
        $client = static::createClient();

        ReligionFactory::createOne(['label' => 'Culte Test', 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/religion');

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('a[title="Détail"]')->count());
    }

    public function testAdminCanSeeModifierButton(): void
    {
        $client = static::createClient();

        ReligionFactory::createOne(['label' => 'Culte Test', 'secret' => false]);

        $user = UserFactory::createOne(['roles' => ['ROLE_REGLE']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/religion');

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('a[title="Modifier"]')->count());
    }
}
