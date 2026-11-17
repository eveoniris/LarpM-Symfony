<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\LoginHistory;
use App\Enum\LoginMethod;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SensitiveParameter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Chaque connexion (web, API, impersonation) est enregistrée dans login_history avec IP et user agent.
 */
#[Group('functional')]
class LoginHistoryTest extends WebTestCase
{
    private const string GOOD_CREDENTIAL = 'test123';
    private const string BAD_CREDENTIAL = 'mauvais';

    public function testConnexionWebReussieEstEnregistree(): void
    {
        $client = static::createClient();
        $user = $this->createUser('web-ok@example.com');

        $this->submitWebLogin($client, 'web-ok@example.com', self::GOOD_CREDENTIAL);

        $entries = $this->entriesFor('web-ok@example.com');
        static::assertCount(1, $entries);
        static::assertTrue($entries[0]->isSuccess());
        static::assertSame(LoginMethod::WEB, $entries[0]->getMethod());
        static::assertSame($user->getId(), $entries[0]->getUser()?->getId());
        static::assertSame('127.0.0.1', $entries[0]->getIp());
        static::assertSame('Test-Agent/1.0', $entries[0]->getUserAgent());
        static::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $entries[0]->getFingerprint());
    }

    public function testMauvaisMotDePasseEstEnregistreAvecLUtilisateur(): void
    {
        $client = static::createClient();
        $user = $this->createUser('web-ko@example.com');

        $this->submitWebLogin($client, 'web-ko@example.com', self::BAD_CREDENTIAL);

        $entries = $this->entriesFor('web-ko@example.com');
        static::assertCount(1, $entries);
        static::assertFalse($entries[0]->isSuccess());
        static::assertSame('bad_credentials', $entries[0]->getFailureReason());
        static::assertSame($user->getId(), $entries[0]->getUser()?->getId());
    }

    public function testCompteInconnuEstEnregistreSansUtilisateur(): void
    {
        $client = static::createClient();

        $this->submitWebLogin($client, 'inconnu@example.com', 'peu-importe');

        $entries = $this->entriesFor('inconnu@example.com');
        static::assertCount(1, $entries);
        static::assertFalse($entries[0]->isSuccess());
        static::assertNull($entries[0]->getUser());
    }

    public function testConnexionApiEstEnregistreeEtLesRequetesJwtNeLeSontPas(): void
    {
        $client = static::createClient();
        $this->createUser('api-ok@example.com', ['ROLE_USER']);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['username' => 'api-ok@example.com', 'password' => self::GOOD_CREDENTIAL]));
        static::assertResponseIsSuccessful();
        $gn = GnFactory::createOne();
        /** @var array<string, string> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), associative: true);

        $client->request('GET', '/api/competences/' . $gn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token']]);
        static::assertNotSame(401, $client->getResponse()->getStatusCode());
        $client->request('GET', '/api/competences/' . $gn->getId(), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token']]);
        static::assertNotSame(401, $client->getResponse()->getStatusCode());

        $entries = $this->entriesFor('api-ok@example.com');
        static::assertCount(1, $entries, 'Seul le login est tracé, pas chaque requête authentifiée par JWT.');
        static::assertSame(LoginMethod::API_JWT, $entries[0]->getMethod());
        static::assertTrue($entries[0]->isSuccess());
    }

    public function testEchecApiEstEnregistre(): void
    {
        $client = static::createClient();
        $this->createUser('api-ko@example.com', ['ROLE_USER']);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['username' => 'api-ko@example.com', 'password' => self::BAD_CREDENTIAL]));

        $entries = $this->entriesFor('api-ko@example.com');
        static::assertCount(1, $entries);
        static::assertFalse($entries[0]->isSuccess());
        static::assertSame(LoginMethod::API_JWT, $entries[0]->getMethod());
    }

    public function testSwitchUserEstEnregistreSurLeCompteUsurpe(): void
    {
        $client = static::createClient();
        $admin = UserFactory::createOne(['email' => 'super@example.com', 'roles' => ['ROLE_SUPER_ADMIN']]);
        $cible = UserFactory::createOne(['email' => 'cible@example.com', 'roles' => ['ROLE_USER']]);
        $client->loginUser($admin);

        $client->request('GET', '/?_switch_user=cible@example.com');

        $entries = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(LoginHistory::class)
            ->findBy(['method' => LoginMethod::SWITCH_USER]);
        static::assertCount(1, $entries);
        static::assertSame($cible->getId(), $entries[0]->getUser()?->getId());
        static::assertSame('super@example.com', $entries[0]->getIdentifier());
    }

    /** @param list<string> $roles */
    private function createUser(string $email, array $roles = []): \App\Entity\User
    {
        return UserFactory::createOne([
            'email' => $email,
            'pwd' => password_hash(self::GOOD_CREDENTIAL, \PASSWORD_BCRYPT),
            'roles' => $roles,
        ]);
    }

    private function submitWebLogin(KernelBrowser $client, string $email, #[SensitiveParameter] string $password): void
    {
        $client->setServerParameter('HTTP_USER_AGENT', 'Test-Agent/1.0');
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form(['username' => $email, 'password' => $password]);
        $client->submit($form);
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
