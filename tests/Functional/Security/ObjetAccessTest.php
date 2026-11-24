<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * /personnage/{id}/items et les actions d'ObjetController (création, modification, liaison,
 * impressions, export CSV) sont réservées à l'orga, aux règles et aux scénaristes.
 */
#[Group('functional')]
class ObjetAccessTest extends WebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function routesObjet(): iterable
    {
        yield 'impression de toutes les étiquettes' => ['/item/print-all'];
        yield 'impression photos' => ['/item/print-photo'];
        yield 'export CSV' => ['/item/print-csv'];
    }

    #[DataProvider('routesObjet')]
    public function testAnonymeEstRefuse(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        static::assertResponseRedirects();
    }

    #[DataProvider('routesObjet')]
    public function testJoueurEstRefuse(string $path): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', $path);

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testImpressionAutoriseeOrga(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));

        $client->request('GET', '/item/print-all');

        static::assertResponseIsSuccessful();
    }

    public function testObjetsDUnPersonnageRefusesAnonyme(): void
    {
        $client = static::createClient();
        $personnage = PersonnageFactory::createOne();

        $client->request('GET', '/personnage/' . $personnage->getId() . '/items');

        static::assertResponseRedirects();
    }

    public function testObjetsDUnPersonnageRefusesMemeAuProprietaire(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $personnage = PersonnageFactory::createOne(['user' => $joueur]);
        $client->loginUser($joueur);

        $client->request('GET', '/personnage/' . $personnage->getId() . '/items');

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }
}
