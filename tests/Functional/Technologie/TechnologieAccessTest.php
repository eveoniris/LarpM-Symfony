<?php

declare(strict_types=1);

namespace App\Tests\Functional\Technologie;

use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Scénaristes : lecture seule sur /technologie.
 *
 * - consultation autorisée : liste, détail, personnages associés
 * - écriture interdite : création, modification, suppression, gestion des ressources
 */
#[Group('functional')]
class TechnologieAccessTest extends WebTestCase
{
    public function testScenaristeCanAccessTechnologyList(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCanAccessTechnologyDetail(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/detail');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCanAccessTechnologyPersonnages(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/personnages');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCannotAccessTechnologyAdd(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/add');

        static::assertResponseStatusCodeSame(403);
    }

    public function testScenaristeCannotAccessTechnologyUpdate(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/udpate');

        static::assertResponseStatusCodeSame(403);
    }

    public function testScenaristeCannotAccessTechnologyDelete(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/delete');

        static::assertResponseStatusCodeSame(403);
    }

    public function testScenaristeCannotAccessRessourceAdd(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/ressource/add');

        static::assertResponseStatusCodeSame(403);
    }

    public function testScenaristeDoesNotSeeWriteButtonsOnList(): void
    {
        $client = static::createClient();
        TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie');

        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('a[href$="/technologie/add"]');
        static::assertSelectorNotExists('a[href*="/technologie/"][href$="/udpate"]');
        static::assertSelectorNotExists('a[href*="/technologie/"][href$="/delete"]');
        static::assertSelectorNotExists('a[href*="/technologie/"][href$="/ressource/add"]');
    }

    public function testScenaristeStillSeesReadButtonsOnList(): void
    {
        $client = static::createClient();
        TechnologieFactory::createOne();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('a[href*="/technologie/"][href$="/detail"]');
        static::assertSelectorExists('a[href*="/technologie/"][href$="/personnages"]');
    }

    public function testRegleKeepsWriteButtonsOnList(): void
    {
        $client = static::createClient();
        TechnologieFactory::createOne();
        $regle = UserFactory::createOne(['roles' => ['ROLE_REGLE']]);

        $client->loginUser($regle);
        $client->request('GET', '/technologie');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('a[href$="/technologie/add"]');
        static::assertSelectorExists('a[href*="/technologie/"][href$="/udpate"]');
        static::assertSelectorExists('a[href*="/technologie/"][href$="/delete"]');
    }

    public function testOrgaKeepsWriteButtonsOnList(): void
    {
        $client = static::createClient();
        TechnologieFactory::createOne();
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);

        $client->loginUser($orga);
        $client->request('GET', '/technologie');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('a[href$="/technologie/add"]');
        static::assertSelectorExists('a[href*="/technologie/"][href$="/delete"]');
    }

    public function testPlayerIsRedirectedToAccessDenied(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => []]);

        $client->loginUser($user);
        $client->request('GET', '/technologie');

        static::assertResponseRedirects('/access_denied');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/technologie');

        static::assertResponseRedirects();
    }
}
