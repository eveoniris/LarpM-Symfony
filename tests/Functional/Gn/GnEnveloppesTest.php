<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Impression en flux de la synthèse des groupes d'un GN (route groupes.enveloppes).
 *
 * Vérifie que le rendu groupe par groupe (StreamedResponse + clear EM) fonctionne.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class GnEnveloppesTest extends WebTestCase
{
    public function testOrgaCanStreamGroupesEnveloppes(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne(['nom' => 'GammaGroupe']);
        GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);
        $user = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);

        $client->loginUser($user);
        $client->request('GET', '/gn/' . $gn->getId() . '/groupes/enveloppes');

        static::assertResponseIsSuccessful();

        $content = (string) $client->getInternalResponse()->getContent();
        static::assertStringContainsString('GammaGroupe', $content);
    }

    public function testNonOrgaCannotAccessGroupesEnveloppes(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser($user);
        $client->request('GET', '/gn/' . $gn->getId() . '/groupes/enveloppes');

        static::assertResponseRedirects('/access_denied');
    }
}
