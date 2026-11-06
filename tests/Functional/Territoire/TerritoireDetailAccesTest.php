<?php

declare(strict_types=1);

namespace App\Tests\Functional\Territoire;

use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Le détail d'un territoire (lien de la carte) doit s'afficher pour tout joueur connecté,
 * y compris quand aucun groupe n'est rattaché au territoire.
 */
#[Group('functional')]
class TerritoireDetailAccesTest extends WebTestCase
{
    public function testJoueurVoitLeDetailDUnTerritoireSansGroupe(): void
    {
        $client = static::createClient();
        $territoire = TerritoireFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', '/territoire/' . $territoire->getId());

        static::assertResponseIsSuccessful();
    }
}
