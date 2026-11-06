<?php

declare(strict_types=1);

namespace App\Tests\Functional\Territoire;

use App\Entity\Territoire;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Enregistrement du zonage d'un territoire depuis la carte (POST /world/countries/{id}/update).
 */
#[Group('functional')]
class TerritoireZonageTest extends WebTestCase
{
    private const GEOM = '{"type":"Feature","properties":{},"geometry":{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1],[0,0]]]}}';

    public function testUnCartographeEnregistreLeZonage(): void
    {
        $client = static::createClient();
        $territoire = TerritoireFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_CARTOGRAPHE']]));

        $client->request('POST', '/world/countries/' . $territoire->getId() . '/update', ['geom' => self::GEOM, 'color' => '#336699']);

        static::assertResponseIsSuccessful();
        $reponse = json_decode((string) $client->getResponse()->getContent(), associative: true);
        static::assertSame($territoire->getId(), $reponse['id']);
        static::assertSame('#336699', $reponse['color']);

        $enregistre = static::getContainer()->get('doctrine')->getManager()->find(Territoire::class, $territoire->getId());
        static::assertSame(self::GEOM, $enregistre->getGeojson());
    }

    public function testUnZonageInvalideEstRefuse(): void
    {
        $client = static::createClient();
        $territoire = TerritoireFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_CARTOGRAPHE']]));

        $client->request('POST', '/world/countries/' . $territoire->getId() . '/update', ['geom' => 'pas du json', 'color' => '#336699']);

        static::assertResponseStatusCodeSame(400);
    }

    public function testUnJoueurNePeutPasEnregistrerLeZonage(): void
    {
        $client = static::createClient();
        $territoire = TerritoireFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('POST', '/world/countries/' . $territoire->getId() . '/update', ['geom' => self::GEOM, 'color' => '#336699']);

        static::assertFalse($client->getResponse()->isSuccessful());
        $enregistre = static::getContainer()->get('doctrine')->getManager()->find(Territoire::class, $territoire->getId());
        static::assertNull($enregistre->getGeojson());
    }
}
