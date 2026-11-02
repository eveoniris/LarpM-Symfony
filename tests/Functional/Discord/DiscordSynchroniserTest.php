<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Bouton « Synchroniser mes rôles Discord » : jeton CSRF, redirection interne, aucun appel Discord sans guilde configurée.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordSynchroniserTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSansJetonCsrfEstRefuse(): void
    {
        $this->client->loginUser($this->linkedUser());
        $this->client->request('POST', '/discord/synchroniser', ['retour' => '/']);

        $this->assertResponseRedirects('/access_denied');
    }

    public function testSansGuildeConfigureeRedirigeVersLaPageDemandee(): void
    {
        $user = $this->linkedUser();
        $this->client->loginUser($user);
        $token = $this->csrfToken($user);

        $this->client->request('POST', '/discord/synchroniser', ['_token' => $token, 'retour' => '/groupeSecondaire/list']);

        $this->assertResponseRedirects('/groupeSecondaire/list');
    }

    public function testRedirectionVersUnHoteExterneEstIgnoree(): void
    {
        $user = $this->linkedUser();
        $this->client->loginUser($user);
        $token = $this->csrfToken($user);

        foreach (['//evil.example', 'https://evil.example'] as $retour) {
            $this->client->request('POST', '/discord/synchroniser', ['_token' => $token, 'retour' => $retour]);

            $this->assertResponseRedirects(\sprintf('/user/%d/edit', $user->getId()));
        }
    }

    public function testCompteNonLieNeContacteJamaisDiscord(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->client->loginUser($user);

        // Le formulaire de synchro n'existe que pour un compte lié : on récupère un jeton valide sur une autre page liée.
        $linked = $this->linkedUser();
        $this->client->loginUser($linked);
        $token = $this->csrfToken($linked);

        $this->client->loginUser($user);
        $this->client->request('POST', '/discord/synchroniser', ['_token' => $token]);

        // Compte non lié : retour à la fiche avec un message, sans aucun appel à Discord.
        $this->assertResponseRedirects(\sprintf('/user/%d/edit', $user->getId()));
    }

    private function linkedUser(): User
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $user->setDiscordId('800000000000000001');
        $this->em->flush();

        return $user;
    }

    private function csrfToken(User $user): string
    {
        $crawler = $this->client->request('GET', \sprintf('/user/%d/edit', $user->getId()));

        return (string) $crawler->filter('#form-discord-synchroniser input[name=_token]')->attr('value');
    }
}
