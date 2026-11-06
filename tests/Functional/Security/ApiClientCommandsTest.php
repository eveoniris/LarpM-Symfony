<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Repository\ApiClientRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('functional')]
class ApiClientCommandsTest extends KernelTestCase
{
    public function testCreerListerRevoquer(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $create = new CommandTester($application->find('api:client:create'));
        $create->execute(['name' => 'app-cli', '--role' => ['ROLE_API_ADMIN']]);
        $out = $create->getDisplay();
        static::assertStringContainsString('client_secret', $out);

        $app = self::getContainer()->get(ApiClientRepository::class)->findOneBy(['name' => 'app-cli']);
        static::assertNotNull($app);
        static::assertTrue($app->isActive());
        static::assertContains('ROLE_API_ADMIN', $app->getUser()->getRoles());

        $list = new CommandTester($application->find('api:client:list'));
        $list->execute([]);
        static::assertStringContainsString($app->getClientId(), $list->getDisplay());
        static::assertStringNotContainsString($app->getSecretHash(), $list->getDisplay());

        $revoke = new CommandTester($application->find('api:client:revoke'));
        $revoke->execute(['client_id' => $app->getClientId()]);
        static::assertSame(0, $revoke->getStatusCode());
        static::assertFalse($app->isActive());
        static::assertNotContains('ROLE_API_ADMIN', $app->getUser()->getRoles());
    }

    public function testRevoquerClientInconnuEchoue(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $revoke = new CommandTester($application->find('api:client:revoke'));
        $revoke->execute(['client_id' => 'lm_inconnu']);

        static::assertSame(1, $revoke->getStatusCode());
    }
}
