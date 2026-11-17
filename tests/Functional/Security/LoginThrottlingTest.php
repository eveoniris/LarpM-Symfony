<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\LoginHistory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SensitiveParameter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Anti force brute : au-delà de 5 échecs sur 15 minutes, même le bon mot de passe est refusé,
 * et le blocage est visible dans l'historique de connexion.
 */
#[Group('functional')]
class LoginThrottlingTest extends WebTestCase
{
    private const string GOOD_CREDENTIAL = 'test123';
    private const string BAD_CREDENTIAL = 'mauvais';

    /**
     * Les compteurs vivent dans le pool cache.rate_limiter (système de fichiers) : ils persistent
     * entre deux requêtes, et donc entre deux exécutions. On les vide avant et après chaque test.
     * Un pool en mémoire ne convient pas : il est réinitialisé entre deux requêtes du client de test.
     */
    protected function tearDown(): void
    {
        $this->clearLimiterPool();
        parent::tearDown();
    }

    private function newClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->clearLimiterPool();

        return $client;
    }

    private function clearLimiterPool(): void
    {
        static::getContainer()->get('cache.rate_limiter')->clear();
    }

    public function testApiBloqueApresCinqEchecsMemeAvecLeBonMotDePasse(): void
    {
        $client = $this->newClient();
        $this->createUser('throttle-api@example.com');

        for ($i = 0; $i < 5; ++$i) {
            $this->apiLogin($client, 'throttle-api@example.com', self::BAD_CREDENTIAL);
            static::assertSame(401, $client->getResponse()->getStatusCode());
        }

        $this->apiLogin($client, 'throttle-api@example.com', self::GOOD_CREDENTIAL);
        static::assertSame(401, $client->getResponse()->getStatusCode(), 'Le 6e essai est refusé même avec le bon mot de passe.');

        $reasons = array_map(static fn (LoginHistory $entry): ?string => $entry->getFailureReason(), $this->entriesFor('throttle-api@example.com'));
        static::assertSame('too_many_login_attempts', end($reasons));
        static::assertCount(6, $reasons);
    }

    public function testWebBloqueApresCinqEchecsMemeAvecLeBonMotDePasse(): void
    {
        $client = $this->newClient();
        $this->createUser('throttle-web@example.com');

        for ($i = 0; $i < 5; ++$i) {
            $this->webLogin($client, 'throttle-web@example.com', self::BAD_CREDENTIAL);
        }
        $this->webLogin($client, 'throttle-web@example.com', self::GOOD_CREDENTIAL);

        $entries = $this->entriesFor('throttle-web@example.com');
        static::assertCount(6, $entries);
        static::assertFalse($entries[5]->isSuccess());
        static::assertSame('too_many_login_attempts', $entries[5]->getFailureReason());
    }

    public function testUnEchecIsoleNeBloquePas(): void
    {
        $client = $this->newClient();
        $this->createUser('throttle-ok@example.com');

        $this->webLogin($client, 'throttle-ok@example.com', self::BAD_CREDENTIAL);
        $this->webLogin($client, 'throttle-ok@example.com', self::GOOD_CREDENTIAL);

        $entries = $this->entriesFor('throttle-ok@example.com');
        static::assertCount(2, $entries);
        static::assertTrue($entries[1]->isSuccess());
    }

    private function createUser(string $email): void
    {
        UserFactory::createOne([
            'email' => $email,
            'pwd' => password_hash(self::GOOD_CREDENTIAL, \PASSWORD_BCRYPT),
            'roles' => ['ROLE_USER'],
        ]);
    }

    private function apiLogin(KernelBrowser $client, string $email, #[SensitiveParameter] string $password): void
    {
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['username' => $email, 'password' => $password]));
    }

    private function webLogin(KernelBrowser $client, string $email, #[SensitiveParameter] string $password): void
    {
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('Se connecter')->form(['username' => $email, 'password' => $password]));
    }

    /** @return list<LoginHistory> */
    private function entriesFor(string $identifier): array
    {
        return static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(LoginHistory::class)
            ->findBy(['identifier' => $identifier], ['id' => 'ASC']);
    }
}
