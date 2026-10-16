<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Le filtre « par rôle » de /user/list doit se cumuler avec la recherche texte
 * et rester présent dans les formulaires.
 */
#[Group('functional')]
class UserListRoleFilterTest extends WebTestCase
{
    public function testLeFiltreRoleSeCumuleAvecLaRecherche(): void
    {
        $client = static::createClient();

        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        UserFactory::createOne(['username' => 'zzorga_alpha', 'roles' => ['ROLE_ORGA']]);
        UserFactory::createOne(['username' => 'zzorga_beta', 'roles' => ['ROLE_ORGA']]);
        UserFactory::createOne(['username' => 'zzjoueur_alpha', 'roles' => []]);

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/user/list', [
            'role_filter' => 'ROLE_ORGA',
            'list_find' => ['value' => 'alpha', 'type' => '*'],
        ]);

        static::assertResponseIsSuccessful();
        $table = $crawler->filter('table')->text();
        static::assertStringContainsString('zzorga_alpha', $table);
        static::assertStringNotContainsString('zzorga_beta', $table);
        static::assertStringNotContainsString('zzjoueur_alpha', $table);
    }

    public function testLeFiltreRoleNeRemonteQueLeRoleExact(): void
    {
        $client = static::createClient();

        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        UserFactory::createOne(['username' => 'zzsuper_only', 'roles' => ['ROLE_SUPER_ADMIN']]);
        UserFactory::createOne(['username' => 'zzadmin_only', 'roles' => ['ROLE_ADMIN']]);

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/user/list', [
            'role_filter' => 'ROLE_ADMIN',
            'list_find' => ['value' => 'zz', 'type' => '*'],
        ]);

        static::assertResponseIsSuccessful();
        $table = $crawler->filter('table')->text();
        static::assertStringContainsString('zzadmin_only', $table);
        static::assertStringNotContainsString('zzsuper_only', $table);
    }

    public function testLesFormulairesSePassentLesParametres(): void
    {
        $client = static::createClient();

        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);
        $crawler = $client->request('GET', '/user/list', [
            'role_filter' => 'ROLE_ORGA',
            'list_find' => ['value' => 'alpha', 'type' => '*'],
            'limit' => 20,
        ]);

        static::assertResponseIsSuccessful();
        // Le formulaire de recherche embarque le rôle choisi.
        static::assertCount(1, $crawler->filter('.search input[type=hidden][name=role_filter][value=ROLE_ORGA]'));
        // Le formulaire de rôle embarque la recherche et la limite.
        static::assertCount(1, $crawler->filter('form[action$="/user/list"] input[type=hidden][name="list_find[value]"][value=alpha]'));
        static::assertCount(1, $crawler->filter('form[action$="/user/list"] input[type=hidden][name=limit][value="20"]'));
    }
}
