<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Squelette du cron de la timeline : la commande existe, ne plante pas et ne fait rien.
 */
#[Group('integration')]
class GnTimelineRunTest extends KernelTestCase
{
    public function testLaCommandeNeFaitRienPourLeMoment(): void
    {
        $application = new Application(self::bootKernel());
        $testeur = new CommandTester($application->find('app:gn-timeline:run'));

        static::assertSame(0, $testeur->execute([]));
        static::assertStringContainsString('simulation', $testeur->getDisplay());
        static::assertSame(0, $testeur->execute(['--apply' => true]));
        static::assertStringContainsString('aucune étape automatique', $testeur->getDisplay());
    }
}
