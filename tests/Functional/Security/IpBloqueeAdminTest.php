<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\LoginHistory;
use App\Entity\LogAction;
use App\Enum\LogActionType;
use App\Enum\LoginMethod;
use App\Security\IpBlocklist;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Bouton « Bloquer cette IP » des pages qui affichent une IP : réservé aux admins, journalisé, protégé.
 */
#[Group('functional')]
class IpBloqueeAdminTest extends WebTestCase
{
    private const string IP = '45.33.32.211';

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\IpBloquee')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\LoginHistory h WHERE h.ip = :ip')->setParameter('ip', self::IP)->execute();
        static::getContainer()->get(IpBlocklist::class)->invalidate();

        parent::tearDown();
    }

    private function connexionAvecIp(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist((new LoginHistory(LoginMethod::WEB, false))->setIp(self::IP));
        $entityManager->flush();
    }

    private function admin(KernelBrowser $client): void
    {
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));
    }

    public function testAdminBloqueUneIpDepuisLaPageConnexions(): void
    {
        $client = static::createClient();
        $this->connexionAvecIp();
        $this->admin($client);

        $crawler = $client->request('GET', '/admin/connexions?ip=' . self::IP);
        $form = $crawler->filter('details[data-role="admin"] form')->first()->form(['motif' => 'abus', 'days' => '30']);
        $client->submit($form);

        static::assertResponseRedirects();
        static::assertTrue(static::getContainer()->get(IpBlocklist::class)->isBlocked(self::IP));
        static::assertNotEmpty(static::getContainer()->get(EntityManagerInterface::class)->getRepository(LogAction::class)->findBy(['type' => LogActionType::IP_BLOCAGE->value]));
    }

    public function testUnNonAdminNePeutPasBloquer(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));

        $client->request('POST', '/admin/ip-bloquees/bloquer', ['ip' => self::IP, '_token' => bin2hex(random_bytes(4))]);

        static::assertResponseRedirects('/access_denied');
        static::assertFalse(static::getContainer()->get(IpBlocklist::class)->isBlocked(self::IP));
    }

    public function testJetonInvalideEtPropreIpRefuses(): void
    {
        $client = static::createClient();
        $this->admin($client);

        $client->request('POST', '/admin/ip-bloquees/bloquer', ['ip' => self::IP, '_token' => bin2hex(random_bytes(4))]);
        static::assertResponseStatusCodeSame(403);

        $client->setServerParameter('REMOTE_ADDR', self::IP);
        $this->connexionAvecIp();
        $crawler = $client->request('GET', '/admin/connexions?ip=' . self::IP);
        $client->submit($crawler->filter('details[data-role="admin"] form')->first()->form());

        static::assertResponseRedirects();
        static::assertFalse(static::getContainer()->get(IpBlocklist::class)->isBlocked(self::IP), 'Un admin ne peut pas bloquer sa propre IP.');
    }

    public function testListeEtDeblocage(): void
    {
        $client = static::createClient();
        $this->admin($client);
        static::getContainer()->get(IpBlocklist::class)->block(self::IP, null, 'motif visible');

        $crawler = $client->request('GET', '/admin/ip-bloquees');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('table', self::IP . '/32');
        static::assertSelectorTextContains('table', 'motif visible');

        $client->submit($crawler->filter('table form')->first()->form());

        static::assertResponseRedirects('/admin/ip-bloquees');
        static::assertFalse(static::getContainer()->get(IpBlocklist::class)->isBlocked(self::IP));
        static::assertNotEmpty(static::getContainer()->get(EntityManagerInterface::class)->getRepository(LogAction::class)->findBy(['type' => LogActionType::IP_DEBLOCAGE->value]));
    }

    public function testListeInterditeAuxNonAdmins(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));

        $client->request('GET', '/admin/ip-bloquees');

        static::assertResponseRedirects('/access_denied');
    }

    public function testFicheUtilisateurSignaleUneIpBloquee(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(
            (new LoginHistory(LoginMethod::WEB, false))
                ->setIp(self::IP)
                ->setUser($cible),
        );
        $entityManager->flush();
        static::getContainer()->get(IpBlocklist::class)->block(self::IP);
        $this->admin($client);

        $client->request('GET', '/user/' . $cible->getId() . '/connexions');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('.alert-danger', self::IP);
    }

    public function testRedirectionExterneIgnoree(): void
    {
        $client = static::createClient();
        $this->connexionAvecIp();
        $this->admin($client);

        $crawler = $client->request('GET', '/admin/connexions?ip=' . self::IP);
        $form = $crawler->filter('details[data-role="admin"] form')->first()->form();
        $form['redirect'] = '//evil.example.com';
        $client->submit($form);

        static::assertResponseRedirects('/admin/connexions');
    }
}
