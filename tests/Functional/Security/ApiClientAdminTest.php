<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Repository\ApiClientRepository;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiClientAdminTest extends WebTestCase
{
    public function testNonAdminEstRefuse(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', '/admin/api-clients');

        static::assertResponseRedirects('/access_denied');
    }

    public function testAdminCreeUneApplicationLectureSeule(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $this->creer($client, 'app-admin-test', 'ROLE_API_READ');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('client_secret', (string) $client->getResponse()->getContent());
        $app = static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-admin-test']);
        static::assertNotNull($app);
        static::assertContains('ROLE_API_READ', $app->getUser()->getRoles());

        // La liste ne montre jamais le secret.
        $client->request('GET', '/admin/api-clients');
        static::assertSelectorTextContains('body', 'app-admin-test');
        static::assertStringNotContainsString($app->getSecretHash(), (string) $client->getResponse()->getContent());
    }

    public function testAdminSimpleNePeutPasCreerApiAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $this->creer($client, 'app-interdite', 'ROLE_API_ADMIN');

        static::assertNull(static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-interdite']));
        static::assertSelectorTextContains('.alert-danger', 'super admin');
    }

    public function testSuperAdminPeutCreerApiAdminPuisRevoquer(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN']]));

        $this->creer($client, 'app-super', 'ROLE_API_ADMIN');
        $app = static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-super']);
        static::assertNotNull($app);
        static::assertContains('ROLE_API_ADMIN', $app->getUser()->getRoles());

        $crawler = $client->request('GET', '/admin/api-clients');
        $form = $crawler->filter('form[action$="/' . $app->getId() . '/revoke"]')->form();
        $client->submit($form);

        static::assertResponseRedirects('/admin/api-clients');
        $app = static::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-super']);
        static::assertFalse($app->isActive());
        static::assertNotContains('ROLE_API_ADMIN', $app->getUser()->getRoles());
    }

    public function testComptesDeServiceAbsentsDeUserList(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $this->creer($client, 'app-masquee', 'ROLE_API_READ');

        $client->request('GET', '/user/list?limit=500');

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('api-app-masquee', (string) $client->getResponse()->getContent());
        static::assertStringNotContainsString('@api.larpmanager.invalid', (string) $client->getResponse()->getContent());
    }

    private function creer(KernelBrowser $client, string $name, string $role): void
    {
        $crawler = $client->request('GET', '/admin/api-clients/new');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $client->request('POST', '/admin/api-clients/new', ['_token' => $token, 'name' => $name, 'role' => $role, 'gn' => '']);
    }
}
