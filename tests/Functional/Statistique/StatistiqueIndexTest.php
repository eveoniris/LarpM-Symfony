<?php

declare(strict_types=1);

namespace App\Tests\Functional\Statistique;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La page /statistique doit charger Chart.js et exposer les 6 canvas, avec ou sans GN.
 */
#[Group('functional')]
class StatistiqueIndexTest extends WebTestCase
{
    public function testIndexWithoutGnRendersCharts(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/statistique');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('canvas#myChartClasses');
        static::assertStringContainsString('chart.js', (string) $client->getResponse()->getContent());
    }

    public function testIndexWithGnRendersCharts(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/statistique/' . $gn->getId());

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('canvas#myChartCompetencesFamily');
    }
}
