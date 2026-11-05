<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\DiscordGuild;
use App\Repository\DiscordGuildRepository;
use App\Service\Discord\DiscordInteractionHandler;
use App\Service\Discord\DiscordMappingService;
use App\Service\Discord\DiscordRoleResolver;
use App\Service\Discord\DiscordSyncService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Endpoint des interactions Discord : signature obligatoire, PING et commande /roles.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordInteractionTest extends WebTestCase
{
    private const string GUILD = '900000000000000001';
    private const string DISCORD_USER = '800000000000000001';
    private const string ROLE_MEMBRE = '100000000000000001';

    private EntityManagerInterface $em;
    private FakeDiscordApi $api;

    protected function setUp(): void
    {
        static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->api = new FakeDiscordApi([self::GUILD => [self::DISCORD_USER => []]]);
    }

    public function testRequeteSansSignatureEstRefusee(): void
    {
        $client = static::getClient();
        $client->request('POST', '/discord/interactions', content: '{"type":1}');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRequeteAvecSignatureInvalideEstRefusee(): void
    {
        $client = static::getClient();
        $client->request(
            'POST',
            '/discord/interactions',
            server: [
                'HTTP_X_SIGNATURE_ED25519' => str_repeat('ab', 64),
                'HTTP_X_SIGNATURE_TIMESTAMP' => '1700000000',
            ],
            content: '{"type":1}',
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPing(): void
    {
        $response = $this->handler()->handle(['type' => 1]);

        $this->assertSame(['type' => 1], $response?->body);
    }

    public function testCommandeInconnueEstIgnoree(): void
    {
        $this->assertNull($this->handler()->handle(['type' => 2, 'data' => ['name' => 'autre']]));
    }

    public function testRolesSurUnServeurNonConfigure(): void
    {
        $response = $this->handler()->handle($this->rolesPayload('999999999999999999'));

        $this->assertStringContainsString('pas configuré', $response?->body['data']['content']);
    }

    public function testRolesSansCompteLieDonneLeLienDeLiaison(): void
    {
        $this->createGuild();

        $response = $this->handler()->handle($this->rolesPayload(self::GUILD));

        $this->assertSame(4, $response?->body['type']);
        $this->assertStringContainsString('/discord/lier', $response->body['data']['content']);
    }

    public function testRolesAvecCompteLieSynchroniseEtRepondEnDiffere(): void
    {
        $guild = $this->createGuild();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $user->setDiscordId(self::DISCORD_USER);
        $this->em->flush();

        $response = $this->handler()->handle($this->rolesPayload($guild->getGuildId()));

        $this->assertSame(5, $response?->body['type']);
        $this->assertNotNull($response->afterResponse);

        ($response->afterResponse)();

        $this->assertSame([self::ROLE_MEMBRE], $this->api->members[self::GUILD][self::DISCORD_USER]);
        $this->assertCount(1, $this->api->interactionReplies);
        $this->assertStringContainsString('ajouté(s) : Membre', $this->api->interactionReplies[0]);
    }

    private function handler(): DiscordInteractionHandler
    {
        $container = static::getContainer();
        $guilds = $container->get(DiscordGuildRepository::class);

        return new DiscordInteractionHandler(
            $guilds,
            $this->em,
            new DiscordSyncService($container->get(DiscordRoleResolver::class), $this->api, $guilds),
            $this->api,
            $container->get(UrlGeneratorInterface::class),
            new NullLogger(),
        );
    }

    private function createGuild(): DiscordGuild
    {
        $guild = (new DiscordGuild())
            ->setGuildId(self::GUILD)
            ->setNom('Serveur test');
        $this->em->persist($guild);
        $this->em->flush();
        $this->assertSame([], static::getContainer()->get(DiscordMappingService::class)->apply($guild, self::ROLE_MEMBRE, '', []));

        return $guild;
    }

    /** @return array<string, mixed> */
    private function rolesPayload(string $guildId): array
    {
        return [
            'type' => 2,
            'application_id' => '700000000000000001',
            'token' => 'jeton-interaction',
            'guild_id' => $guildId,
            'member' => ['user' => ['id' => self::DISCORD_USER]],
            'data' => ['name' => 'roles'],
        ];
    }
}
