<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\LoginHistory;
use App\Enum\LoginMethod;
use App\Tests\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Vue globale /admin/connexions : réservée aux administrateurs (ORGA exclu, l'IP est une donnée sensible).
 */
#[Group('functional')]
class AdminConnexionsTest extends WebTestCase
{
    public function testAnonymeEstRedirige(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/connexions');

        static::assertResponseRedirects();
    }

    public function testJoueurEtOrgaSontRefuses(): void
    {
        $client = static::createClient();

        foreach (['ROLE_USER', 'ROLE_ORGA', 'ROLE_SCENARISTE'] as $role) {
            $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', $role]]));
            $client->request('GET', '/admin/connexions');

            static::assertNotSame(200, $client->getResponse()->getStatusCode(), $role . ' ne doit pas lire toutes les connexions.');
        }
    }

    public function testAdminVoitTousLesComptesEtLesEchecsSansCompte(): void
    {
        $client = static::createClient();
        $alice = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->addEntry($alice, true, '203.0.113.10', LoginMethod::WEB, new DateTimeImmutable('-2 days'));
        $this->addEntry(null, false, '198.51.100.20', LoginMethod::WEB, new DateTimeImmutable('-1 day'), 'inconnu@example.test', 'bad_credentials');
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $crawler = $client->request('GET', '/admin/connexions');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('code:contains("203.0.113.10")');
        static::assertSelectorExists('code:contains("198.51.100.20")');
        static::assertSelectorTextContains('tbody', 'inconnu@example.test');
        static::assertSelectorTextContains('tbody', 'Compte inconnu');
        static::assertGreaterThanOrEqual(2, $crawler->filter('tbody tr')->count());
    }

    public function testFiltreParResultatParIpEtParMethode(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->addEntry($user, true, '203.0.113.10', LoginMethod::WEB, new DateTimeImmutable('-3 days'));
        $this->addEntry($user, false, '198.51.100.20', LoginMethod::WEB, new DateTimeImmutable('-2 days'), null, 'bad_credentials');
        $this->addEntry($user, true, '192.0.2.30', LoginMethod::API_JWT, new DateTimeImmutable('-1 day'));
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/connexions?result=failure');
        static::assertSelectorExists('code:contains("198.51.100.20")');
        static::assertSelectorNotExists('code:contains("203.0.113.10")');

        $client->request('GET', '/admin/connexions?ip=192.0.2.30');
        static::assertSelectorExists('code:contains("192.0.2.30")');
        static::assertSelectorNotExists('code:contains("198.51.100.20")');

        $client->request('GET', '/admin/connexions?method=api_jwt');
        static::assertSelectorExists('code:contains("192.0.2.30")');
        static::assertSelectorNotExists('code:contains("203.0.113.10")');
    }

    public function testFiltreParPeriodeEtParRecherche(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->addEntry($user, true, '203.0.113.10', LoginMethod::WEB, new DateTimeImmutable('-10 days'));
        $this->addEntry($user, true, '198.51.100.20', LoginMethod::WEB, new DateTimeImmutable('-1 day'));
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $depuis = (new DateTimeImmutable('-3 days'))->format('Y-m-d');
        $client->request('GET', '/admin/connexions?from=' . $depuis);
        static::assertSelectorExists('code:contains("198.51.100.20")');
        static::assertSelectorNotExists('code:contains("203.0.113.10")');

        $jusqua = (new DateTimeImmutable('-5 days'))->format('Y-m-d');
        $client->request('GET', '/admin/connexions?to=' . $jusqua);
        static::assertSelectorExists('code:contains("203.0.113.10")');
        static::assertSelectorNotExists('code:contains("198.51.100.20")');

        $client->request('GET', '/admin/connexions?q=' . rawurlencode('203.0.113'));
        static::assertSelectorExists('code:contains("203.0.113.10")');
        static::assertSelectorNotExists('code:contains("198.51.100.20")');
    }

    public function testParametresInvalidesNeCassentPasLaPage(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/connexions?method=nimporte&result=zzz&from=pas-une-date&page=-4&limit=100000&q=%25_');

        static::assertResponseIsSuccessful();
    }

    public function testLeMenuAdminContientLeLien(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/connexions');

        static::assertSelectorExists('a.dropdown-item[href="/admin/connexions"]');
    }

    // @mago-expect lint:excessive-parameter-list
    private function addEntry(
        ?\App\Entity\User $user,
        bool $success,
        string $ip,
        LoginMethod $method,
        DateTimeImmutable $date,
        ?string $identifier = null,
        ?string $reason = null,
    ): void {
        $entry = (new LoginHistory($method, $success, $date))
            ->setUser($user)
            ->setIdentifier($identifier ?? $user?->getUserIdentifier())
            ->setFailureReason($reason)
            ->setIp($ip)
            ->setUserAgent('Test-Agent/1.0');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($entry);
        $em->flush();
    }
}
