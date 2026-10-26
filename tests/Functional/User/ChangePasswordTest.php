<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le changement de mot de passe est isolé de l'édition du compte (/user/{id}/password).
 *
 * @mago-ignore lint:no-literal-password
 */
#[Group('functional')]
class ChangePasswordTest extends WebTestCase
{
    private function creerUtilisateurAvecMotDePasse(string $motDePasse): User
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $entity = static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        $entity->setPassword($hasher->hashPassword($entity, $motDePasse));
        static::getContainer()->get('doctrine')->getManager()->flush();

        return $entity;
    }

    /** @param array<string, string> $champs */
    private function envoyer(KernelBrowser $client, User $cible, array $champs): void
    {
        $crawler = $client->request('GET', '/user/' . $cible->getId() . '/password');
        static::assertResponseIsSuccessful();
        $token = (string) $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/user/' . $cible->getId() . '/password', $champs + ['_token' => $token]);
    }

    private function hashActuel(User $user): ?string
    {
        static::getContainer()->get('doctrine')->getManager()->clear();

        return static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId())->getPassword();
    }

    public function testLeFormulaireDEditionNeContientPlusLeMotDePasse(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/user/' . $user->getId() . '/edit');
        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('input[type="password"]'));
    }

    public function testEnregistrerLEditionNeToucheRienAuMotDePasse(): void
    {
        $client = static::createClient();
        $user = $this->creerUtilisateurAvecMotDePasse('ancien-mdp');
        $avant = $user->getPassword();
        $client->loginUser($user);

        $client->request('POST', '/user/' . $user->getId() . '/edit', [
            'email' => (string) $user->getEmail(),
            'password' => 'prerempli-par-le-navigateur',
            'confirm_password' => 'different',
        ]);

        static::assertSame($avant, $this->hashActuel($user));
    }

    public function testChangementSurSonCompteExigeLeMotDePasseActuel(): void
    {
        $client = static::createClient();
        $user = $this->creerUtilisateurAvecMotDePasse('ancien-mdp');
        $avant = $user->getPassword();
        $client->loginUser($user);

        $this->envoyer($client, $user, ['current_password' => 'faux', 'password' => 'nouveau-mdp', 'confirm_password' => 'nouveau-mdp']);

        static::assertSame($avant, $this->hashActuel($user));
    }

    public function testConfirmationDifferenteOuTropCourtEstRefuse(): void
    {
        $client = static::createClient();
        $user = $this->creerUtilisateurAvecMotDePasse('ancien-mdp');
        $avant = $user->getPassword();
        $client->loginUser($user);

        $this->envoyer($client, $user, ['current_password' => 'ancien-mdp', 'password' => 'nouveau-mdp', 'confirm_password' => 'autre-chose']);
        static::assertSame($avant, $this->hashActuel($user));

        $this->envoyer($client, $user, ['current_password' => 'ancien-mdp', 'password' => 'abc', 'confirm_password' => 'abc']);
        static::assertSame($avant, $this->hashActuel($user));
    }

    public function testChangementReussiSurSonCompte(): void
    {
        $client = static::createClient();
        $user = $this->creerUtilisateurAvecMotDePasse('ancien-mdp');
        $avant = $user->getPassword();
        $client->loginUser($user);

        $this->envoyer($client, $user, ['current_password' => 'ancien-mdp', 'password' => 'nouveau-mdp', 'confirm_password' => 'nouveau-mdp']);

        static::assertResponseRedirects('/user/' . $user->getId());
        $apres = $this->hashActuel($user);
        static::assertNotSame($avant, $apres);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        static::assertTrue($hasher->isPasswordValid($fresh, 'nouveau-mdp'));
    }

    public function testUnAdminChangeLeMotDePasseDUnAutreSansLActuel(): void
    {
        $client = static::createClient();
        $cible = $this->creerUtilisateurAvecMotDePasse('ancien-mdp');
        $admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN']]);
        $client->loginUser($admin);

        $this->envoyer($client, $cible, ['password' => 'nouveau-mdp', 'confirm_password' => 'nouveau-mdp']);

        static::assertResponseRedirects('/user/' . $cible->getId());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $fresh = static::getContainer()->get('doctrine')->getManager()->find(User::class, $cible->getId());
        static::assertTrue($hasher->isPasswordValid($fresh, 'nouveau-mdp'));
    }

    public function testUnJoueurNePeutPasChangerLeMotDePasseDUnAutre(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $autre = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser($autre);

        $client->request('GET', '/user/' . $cible->getId() . '/password');

        static::assertResponseRedirects('/access_denied');
    }
}
