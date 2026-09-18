<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour la refonte de sécurité du menu Univers (rôle Cohérence,
 * lecture seule pour le Scénariste sauf sur son propre territoire) et l'ouverture en
 * lecture du menu Règles au Scénariste.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class UniversAndReglesAccessTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Espèces : lecture Cartographe/Scénariste, écriture Cohérence uniquement
    // -------------------------------------------------------------------------

    public function testEspeceListDeniedForPlainRegle(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_REGLE']]);
        $client->loginUser($user);

        $client->request('GET', '/espece');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEspeceListAllowedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/espece');

        static::assertResponseIsSuccessful();
    }

    public function testEspeceListAllowedForCartographe(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_CARTOGRAPHE']]);
        $client->loginUser($user);

        $client->request('GET', '/espece');

        static::assertResponseIsSuccessful();
    }

    public function testEspeceAddDeniedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/espece/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEspeceAddDeniedForOrga(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);
        $client->loginUser($user);

        $client->request('GET', '/espece/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEspeceAddAllowedForCoherence(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_COHERENCE']]);
        $client->loginUser($user);

        $client->request('GET', '/espece/add');

        static::assertResponseIsSuccessful();
    }

    public function testEspeceDetailDoesNotShowGamemasterInfoToOwningPlayer(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $espece = EspeceFactory::createOne(['description_secrete' => 'Ne doit pas être visible']);
        // Le joueur n'a accès au détail que parce que son personnage possède cette espèce.
        PersonnageFactory::createOne(['user' => $user, 'especes' => [$espece]]);

        $client->loginUser($user);
        $client->request('GET', '/espece/' . (string) $espece->getId() . '/detail');

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Ne doit pas être visible', (string) $client->getResponse()->getContent());
    }

    public function testEspeceDetailShowsGamemasterInfoToScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $espece = EspeceFactory::createOne(['description_secrete' => 'Information réservée au staff']);

        $client->loginUser($user);
        $client->request('GET', '/espece/' . (string) $espece->getId() . '/detail');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Information réservée au staff', (string) $client->getResponse()->getContent());
    }

    // -------------------------------------------------------------------------
    // Culture : lecture Cartographe/Scénariste, écriture Cohérence uniquement
    // -------------------------------------------------------------------------

    public function testCultureIndexAllowedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/culture');

        static::assertResponseIsSuccessful();
    }

    public function testCultureAddDeniedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/culture/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testCultureAddAllowedForCoherence(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_COHERENCE']]);
        $client->loginUser($user);

        $client->request('GET', '/culture/add');

        static::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Cosmogonie / Autres mondes : même modèle que les Espèces
    // -------------------------------------------------------------------------

    public function testCosmogonieListAllowedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/cosmogonie');

        static::assertResponseIsSuccessful();
    }

    public function testCosmogonieAddDeniedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/cosmogonie/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testCosmogonieAddAllowedForCoherence(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_COHERENCE']]);
        $client->loginUser($user);

        $client->request('GET', '/cosmogonie/add');

        static::assertResponseIsSuccessful();
    }

    public function testAutreMondeListAllowedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/autre-monde');

        static::assertResponseIsSuccessful();
    }

    public function testAutreMondeAddDeniedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/autre-monde/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Règles : lecture désormais ouverte au Scénariste, écriture toujours réservée
    // à ROLE_REGLE
    // -------------------------------------------------------------------------

    public function testLevelListAllowedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/level/');

        static::assertResponseIsSuccessful();
    }

    public function testLevelAddDeniedForScenariste(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($user);

        $client->request('GET', '/level/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testLevelAddAllowedForRegle(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_REGLE']]);
        $client->loginUser($user);

        $client->request('GET', '/level/add');

        static::assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Territoire : écriture globale retirée aux Scénaristes, sauf sur leur propre
    // territoire (via TerritoireVoter)
    // -------------------------------------------------------------------------

    public function testScenaristeCanUpdateOwnTerritoire(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $groupe = GroupeFactory::createOne(['scenariste' => $scenariste]);
        $territoire = TerritoireFactory::createOne(['groupe' => $groupe]);

        $client->loginUser($scenariste);
        $client->request('GET', '/territoire/' . $territoire->getId() . '/update');

        static::assertResponseIsSuccessful();
    }

    public function testScenaristeCannotUpdateOtherTerritoire(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $autreScenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $groupe = GroupeFactory::createOne(['scenariste' => $autreScenariste]);
        $territoire = TerritoireFactory::createOne(['groupe' => $groupe]);

        $client->loginUser($scenariste);
        $client->request('GET', '/territoire/' . $territoire->getId() . '/update');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testScenaristeCannotAddNewTerritoire(): void
    {
        $client = static::createClient();
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);
        $client->loginUser($scenariste);

        $client->request('GET', '/territoire/add');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }
}
