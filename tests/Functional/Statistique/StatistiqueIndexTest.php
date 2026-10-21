<?php

declare(strict_types=1);

namespace App\Tests\Functional\Statistique;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La page /statistique est par GN : sans identifiant elle ouvre le prochain GN actif. Chart.js est servi par
 * l'importmap (entrée 'stats') et les 6 canvas sont exposés.
 */
#[Group('functional')]
class StatistiqueIndexTest extends WebTestCase
{
    public function testIndexOpensTheNextActiveGn(): void
    {
        $client = static::createClient();
        GnFactory::createOne(['actif' => true]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/statistique');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('canvas#chart-classes');
        static::assertStringContainsString('stats', (string) $client->getResponse()->getContent());
    }

    public function testIndexWithGnRendersCharts(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/statistique/' . $gn->getId());

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('canvas#chart-competencesFamily');
    }
}
