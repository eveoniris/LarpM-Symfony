<?php

declare(strict_types=1);

namespace App\Tests\Functional\Technologie;

use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TechnologieFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * SEC-01 : un joueur ne lit le détail d'une technologie que si l'un de ses personnages la connaît.
 */
#[Group('functional')]
#[Group('SEC-01')]
class TechnologieSecretVisibiliteTest extends WebTestCase
{
    public function testJoueurNeVoitPasUneTechnologieSecreteInconnue(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne(['label' => 'Forge interdite', 'secret' => true]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/detail');

        static::assertResponseRedirects('/access_denied');
    }

    public function testJoueurNeTelechargePasLeDocumentDUneTechnologieInconnue(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne(['secret' => true]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/document');

        static::assertResponseRedirects('/access_denied');
    }

    public function testJoueurVoitUneTechnologieQuIlConnait(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne(['label' => 'Forge interdite', 'secret' => true]);
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $personnage->addTechnologie($technologie);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($user);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/detail');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', 'Forge interdite');
    }

    public function testScenaristeVoitUneTechnologieSecrete(): void
    {
        $client = static::createClient();
        $technologie = TechnologieFactory::createOne(['secret' => true]);
        $scenariste = UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]);

        $client->loginUser($scenariste);
        $client->request('GET', '/technologie/' . $technologie->getId() . '/detail');

        static::assertResponseIsSuccessful();
    }
}
