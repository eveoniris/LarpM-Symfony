<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Security\IpBlocklist;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Une IP bloquée est refusée à la connexion (même avec les bons identifiants) et ne déclenche ni mail ni
 * création de compte, alors que l'écran laisse croire que tout s'est bien passé.
 */
#[Group('functional')]
class IpBloqueeTest extends WebTestCase
{
    private const string IP_BLOQUEE = '45.33.32.201';
    private const string IP_LIBRE = '45.33.32.202';

    private function bloquer(KernelBrowser $client): IpBlocklist
    {
        $blocklist = static::getContainer()->get(IpBlocklist::class);
        $blocklist->block(self::IP_BLOQUEE, null, 'test');
        $client->setServerParameter('REMOTE_ADDR', self::IP_BLOQUEE);

        return $blocklist;
    }

    private function nettoyer(IpBlocklist $blocklist): void
    {
        // Le noyau est redémarré entre deux requêtes : on repasse par le conteneur courant.
        static::getContainer()->get(EntityManagerInterface::class)->createQuery('DELETE FROM App\Entity\IpBloquee')->execute();
        static::getContainer()->get(IpBlocklist::class)->invalidate();
    }

    public function testLoginJwtRefuseEnIpBloqueeMemeAvecLesBonsIdentifiants(): void
    {
        $client = static::createClient();
        $secret = bin2hex(random_bytes(6));
        UserFactory::createOne(['email' => 'bloque-jwt@example.com', 'pwd' => password_hash($secret, \PASSWORD_BCRYPT)]);
        $blocklist = $this->bloquer($client);
        $body = (string) json_encode(['username' => 'bloque-jwt@example.com', 'password' => $secret]);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        static::assertSame(401, $client->getResponse()->getStatusCode());

        $client->setServerParameter('REMOTE_ADDR', self::IP_LIBRE);
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        static::assertResponseIsSuccessful();

        $this->nettoyer($blocklist);
    }

    public function testMotDePasseOublieNenvoieRienEnIpBloquee(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'bloque-oubli@example.com']);
        $blocklist = $this->bloquer($client);

        $client->request('GET', '/user/forgot');
        $client->submitForm('Envoyer une réinitialisation de votre mot de passe', ['user_forgot_password[email]' => 'bloque-oubli@example.com']);

        static::assertEmailCount(0);
        static::assertResponseRedirects('/login');
        static::assertStringContainsString('ont été envoyé par mail', implode(' ', $client->getRequest()->getSession()->getFlashBag()->peek('alert')));

        $this->nettoyer($blocklist);
    }

    public function testMotDePasseOublieLimiteLesEnvoisParAdresse(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        // Les compteurs survivent entre deux exécutions (cache fichier) : adresse et IP uniques à chaque run.
        $adresse = 'limite-' . bin2hex(random_bytes(4)) . '@example.com';
        $client->setServerParameter('REMOTE_ADDR', '45.33.' . random_int(1, 254) . '.' . random_int(1, 254));
        UserFactory::createOne(['email' => $adresse]);

        $envoyes = 0;
        for ($i = 0; $i < 5; ++$i) {
            $client->request('GET', '/user/forgot');
            $client->submitForm('Envoyer une réinitialisation de votre mot de passe', ['user_forgot_password[email]' => $adresse]);
            static::assertResponseRedirects('/login');
            // Le collecteur de mails est propre à chaque requête : on cumule.
            $envoyes += \count(static::getMailerMessages());
        }

        // 3 envois par heure et par adresse : les 2 suivants sont simulés.
        static::assertSame(3, $envoyes);
    }

    public function testRenvoiDeConfirmationNenvoieRienEnIpBloquee(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'bloque-renvoi@example.com', 'isEnabled' => false]);
        $blocklist = $this->bloquer($client);

        $client->request('POST', '/user/register/resend-confirmation', ['email' => 'bloque-renvoi@example.com']);

        static::assertEmailCount(0);
        static::assertResponseRedirects();

        $this->nettoyer($blocklist);
    }

    public function testInscriptionSimuleeEnIpBloquee(): void
    {
        $client = static::createClient();
        $blocklist = $this->bloquer($client);

        $client->request('GET', '/user/register');
        $client->submitForm("S'enregistrer", [
            'user_register[email]' => 'fantome@example.com',
            'user_register[username]' => 'fantome',
            'user_register[pwd]' => 'Azerty123!Azerty',
            'user_register[confirm_password]' => 'Azerty123!Azerty',
        ]);

        static::assertEmailCount(0);
        static::assertResponseIsSuccessful();
        static::assertNull(static::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->findOneBy(['email' => 'fantome@example.com']));

        $this->nettoyer($blocklist);
    }
}
