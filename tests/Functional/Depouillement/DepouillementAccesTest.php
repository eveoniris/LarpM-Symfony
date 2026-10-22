<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Dépouillement d'enveloppe : accès réservé à ROLE_BETA_TEST (et donc au super admin).
 */
#[Group('functional')]
class DepouillementAccesTest extends WebTestCase
{
    public function testBetaTesteurAccede(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');

        static::assertResponseIsSuccessful();
    }

    public function testSuperAdminAccede(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SUPER_ADMIN']]));

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');

        static::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{0: list<string>}>
     */
    public static function rolesSansAcces(): iterable
    {
        yield 'utilisateur simple' => [['ROLE_USER']];
        yield 'admin simple' => [['ROLE_USER', 'ROLE_ADMIN']];
        yield 'scénariste' => [['ROLE_USER', 'ROLE_SCENARISTE']];
        yield 'wargame' => [['ROLE_USER', 'ROLE_WARGAME']];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('rolesSansAcces')]
    public function testAutresRolesRefuses(array $roles): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => $roles]));

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
        // L'application redirige vers /access_denied au lieu de renvoyer un 403.
        static::assertResponseRedirects('/access_denied');

        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/purge');
        static::assertResponseRedirects('/access_denied');
    }

    public function testLienSurLeDetailDuGnVisibleSeulementPourLesBetaTesteurs(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $lien = 'a[href="/gn/' . $gn->getId() . '/depouillement"]';

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $client->request('GET', '/gn/' . $gn->getId());
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists($lien);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));
        $client->request('GET', '/gn/' . $gn->getId());
        static::assertResponseIsSuccessful();
        static::assertSelectorExists($lien);
    }

    public function testAnonymeRedirigeVersLaConnexion(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');

        static::assertResponseRedirects();
    }
}
