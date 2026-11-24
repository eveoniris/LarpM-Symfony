<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Le menu « Technique » regroupe les liens d'administration technique, réservés aux admins.
 */
#[Group('functional')]
class MenuTechniqueTest extends WebTestCase
{
    /** @return list<string> libellés des menus de premier niveau */
    private function menus(Crawler $crawler): array
    {
        return array_map(trim(...), $crawler->filter('nav a.nav-link.dropdown-toggle')->each(static fn (Crawler $node): string => $node->text()));
    }

    /** @return list<string> libellés des liens d'un menu */
    private function liens(Crawler $crawler, string $menu): array
    {
        $liens = [];
        $crawler
            ->filter('nav li.nav-item.dropdown')
            ->each(static function (Crawler $item) use ($menu, &$liens): void {
                if (trim($item->filter('a.dropdown-toggle')->first()->text()) === $menu) {
                    $liens = array_map(trim(...), $item->filter('a.dropdown-item')->each(static fn (Crawler $a): string => $a->text()));
                }
            });

        return $liens;
    }

    public function testAdminVoitLeMenuTechniqueEtPlusLesLiensTechniquesDansAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/');

        static::assertContains('Technique', $this->menus($crawler));
        static::assertNotContains('Larp', $this->menus($crawler));
        $technique = $this->liens($crawler, 'Technique');
        foreach (['Connexions', 'IP bloquées', 'Liste des droits', 'Log action', 'Bot Discord', 'Applications API', 'Administration', 'Gain XP', 'Usage XP', 'Ajouts en masse'] as $lien) {
            static::assertContains($lien, $technique);
        }
        static::assertNotContains('Messages en masse', $technique);

        $admin = $this->liens($crawler, 'Admin');
        static::assertContains('Messages en masse', $admin);
        foreach (['Connexions', 'IP bloquées', 'Bot Discord', 'Applications API', 'Liste des droits', 'Administration'] as $lien) {
            static::assertNotContains($lien, $admin);
        }
    }

    public function testRoleStatistiqueSeulNeVoitPasTechnique(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_STATISTIQUE']]));

        $crawler = $client->request('GET', '/');

        static::assertNotContains('Technique', $this->menus($crawler));
        static::assertContains('Statistiques', $this->menus($crawler));
        static::assertSame(['Statistique'], $this->liens($crawler, 'Statistiques'));
    }
}
