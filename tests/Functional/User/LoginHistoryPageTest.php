<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Enum\LoginMethod;
use App\Tests\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Page « Mes connexions » : réservée au propriétaire du compte et aux administrateurs.
 */
#[Group('functional')]
class LoginHistoryPageTest extends WebTestCase
{
    private const string FP_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const string FP_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    public function testProprietaireVoitSesConnexionsEtLeNouvelAppareil(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->addEntry($user, true, self::FP_A, '203.0.113.1', new DateTimeImmutable('-3 days'));
        $this->addEntry($user, true, self::FP_A, '203.0.113.1', new DateTimeImmutable('-2 days'));
        $this->addEntry($user, true, self::FP_B, '198.51.100.7', new DateTimeImmutable('-1 day'));
        $this->addEntry($user, false, null, '198.51.100.7', new DateTimeImmutable('-1 hour'));
        $client->loginUser($user);

        $crawler = $client->request('GET', '/user/' . $user->getId() . '/connexions');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h5', 'Mes connexions');
        static::assertCount(4, $crawler->filter('tbody tr'));
        static::assertSame(1, $crawler->filter('.badge:contains("Nouvel appareil")')->count(), 'Seule la 1re connexion du 2e navigateur est signalée.');
        static::assertSame(1, $crawler->filter('.badge:contains("Échec")')->count());
        static::assertSelectorExists('code:contains("198.51.100.7")');
    }

    public function testAdminVoitLesConnexionsDUnAutreCompte(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->addEntry($cible, true, self::FP_A, '203.0.113.1', new DateTimeImmutable('-1 day'));
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/user/' . $cible->getId() . '/connexions');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h5', 'Connexions de');
        static::assertSelectorExists('code:contains("203.0.113.1")');
    }

    public function testUnAutreUtilisateurEstRefuse(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', '/user/' . $cible->getId() . '/connexions');

        static::assertResponseStatusCodeSame(403);
    }

    public function testOrgaSansDroitAdminEstRefuse(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));

        $client->request('GET', '/user/' . $cible->getId() . '/connexions');

        static::assertResponseStatusCodeSame(403);
    }

    public function testAnonymeEstRedirigeVersLaConnexion(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->request('GET', '/user/' . $cible->getId() . '/connexions');

        static::assertResponseRedirects();
    }

    public function testLaFicheProposeLeLienAuProprietaire(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $client->request('GET', '/user/' . $user->getId());

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('#lien-connexions[href="/user/' . $user->getId() . '/connexions"]');
    }

    private function addEntry(User $user, bool $success, ?string $fingerprint, string $ip, DateTimeImmutable $date): void
    {
        $entry = (new LoginHistory(LoginMethod::WEB, $success, $date))
            ->setUser($user)
            ->setIdentifier($user->getUserIdentifier())
            ->setIp($ip)
            ->setUserAgent('Test-Agent/1.0')
            ->setFingerprint($fingerprint);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($entry);
        $em->flush();
    }
}
