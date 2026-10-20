<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Cadres de rôle (administratif, jeu de domaine) de la fiche GN : data-role présent,
 * icône et liens en boutons propres à l'interface bêta.
 */
#[Group('functional')]
class GnDetailCadresRolesTest extends WebTestCase
{
    private function charger(bool $beta): string
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN', 'ROLE_WARGAME']]);
        $admin->setUiTheme($beta ? 'beta' : null);
        static::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($admin);
        $client->request('GET', '/gn/' . $gn->getId());
        static::assertResponseIsSuccessful();

        return (string) $client->getInternalResponse()->getContent();
    }

    public function testDataRolePresentsEtIconeBeta(): void
    {
        $html = $this->charger(true);

        static::assertStringContainsString('data-ui="beta"', $html);
        static::assertStringContainsString('data-role="admin"', $html);
        static::assertStringContainsString('data-role="domaine"', $html);
        static::assertStringContainsString('fa-chess-rook', $html);
        static::assertStringNotContainsString('fa-shield-halved text-warning', $html);
    }

    public function testCompteNonBetaSansClassesBeta(): void
    {
        $html = $this->charger(false);

        static::assertStringNotContainsString('data-ui="beta"', $html);
        static::assertStringNotContainsString('fa-chess-rook', $html);
        static::assertStringNotContainsString('lm-design.css', $html);
        static::assertStringContainsString('fa-shield-halved text-warning', $html);
        static::assertStringContainsString('data-role="admin"', $html);
    }
}
