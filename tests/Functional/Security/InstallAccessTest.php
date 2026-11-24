<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * L'assistant /install est réservé au super admin ; les outils de dev /setpwd et /mail sont retirés.
 */
#[Group('functional')]
class InstallAccessTest extends WebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function routesInstall(): iterable
    {
        yield 'index' => ['/install'];
        yield 'création admin' => ['/install/create-user'];
        yield 'mise à jour configuration' => ['/install/update'];
        yield 'création des tables' => ['/install/create'];
        yield 'fin' => ['/install/done'];
    }

    #[DataProvider('routesInstall')]
    public function testAnonymeEstRefuse(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        static::assertResponseRedirects();
    }

    #[DataProvider('routesInstall')]
    public function testAdminEstRefuse(string $path): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', $path);

        static::assertNotSame(200, $client->getResponse()->getStatusCode());
    }

    public function testAnonymeNePeutPasCreerDAdmin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/install/create-user', [
            'install_user_admin' => ['name' => 'intrus', 'email' => 'intrus@example.test'],
        ]);

        static::assertResponseRedirects();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertNull($em->getRepository(User::class)->findOneBy(['email' => 'intrus@example.test']));
    }

    /** @return iterable<string, array{string}> */
    public static function routesDevRetirees(): iterable
    {
        yield 'changement de mot de passe' => ['/setpwd?userid=1'];
        yield 'envoi de mail de test' => ['/mail'];
    }

    #[DataProvider('routesDevRetirees')]
    public function testRoutesDevRetirees(string $path): void
    {
        $client = static::createClient();

        $client->request('GET', $path);

        static::assertResponseStatusCodeSame(404);
    }
}
