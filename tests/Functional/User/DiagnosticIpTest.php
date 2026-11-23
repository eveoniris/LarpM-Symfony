<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Le diagnostic de résolution de l'IP client n'est visible que des super admins.
 */
#[Group('functional')]
class DiagnosticIpTest extends WebTestCase
{
    public function testSuperAdminVoitLeDiagnostic(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::createOne(['roles' => ['ROLE_SUPER_ADMIN']]);
        $client->loginUser($superAdmin);

        $client->request('GET', '/user/' . $superAdmin->getId());

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('#diagnostic-ip');
        static::assertSelectorTextContains('#diagnostic-ip', 'REMOTE_ADDR');
        static::assertSelectorExists('#diagnostic-ip[data-role="admin"]');
    }

    public function testAdminSimpleNeVoitPasLeDiagnostic(): void
    {
        $client = static::createClient();
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);

        $client->request('GET', '/user/' . $admin->getId());

        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('#diagnostic-ip');
    }
}
