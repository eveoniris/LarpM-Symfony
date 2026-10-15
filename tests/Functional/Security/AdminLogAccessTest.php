<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * /admin/log ne doit jamais renvoyer de 500, même sans fichier de log.
 */
#[Group('functional')]
class AdminLogAccessTest extends WebTestCase
{
    public function testAdminCanViewLogs(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/log?level=ALL&limit=50&q=test');

        static::assertResponseIsSuccessful();
    }

    public function testEmptyLogsRequiresPost(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/log/empty');

        static::assertResponseStatusCodeSame(405);
    }
}
