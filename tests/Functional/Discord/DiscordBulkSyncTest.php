<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\DiscordGuild;
use App\Entity\User;
use App\Repository\DiscordGuildRepository;
use App\Service\Discord\DiscordApiInterface;
use App\Service\Discord\DiscordBulkSyncService;
use App\Service\Discord\DiscordMappingService;
use App\Service\Discord\DiscordRoleResolver;
use App\Service\Discord\DiscordSyncService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Synchronisation de tous les comptes liés : simulation, application, pagination et arrêt sur erreurs répétées.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordBulkSyncTest extends WebTestCase
{
    private const string GUILD = '900000000000000001';
    private const string ROLE_MEMBRE = '100000000000000001';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private DiscordGuild $guild;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Les comptes déjà liés dans les données de développement fausseraient les totaux.
        $this->em->createQuery('UPDATE ' . User::class . ' u SET u.discordId = NULL')->execute();

        $this->guild = (new DiscordGuild())
            ->setGuildId(self::GUILD)
            ->setNom('Serveur test');
        $this->em->persist($this->guild);
        $this->em->flush();
        $this->assertSame([], static::getContainer()->get(DiscordMappingService::class)->apply($this->guild, self::ROLE_MEMBRE, '', []));
    }

    public function testSimulationNeModifieRienSurDiscord(): void
    {
        $this->linkedUser('800000000000000001');
        $this->linkedUser('800000000000000002');
        $api = new FakeDiscordApi([self::GUILD => ['800000000000000001' => [], '800000000000000002' => []]]);

        $report = $this->bulk($api)->run($this->guild, apply: false);

        $this->assertSame(2, $report->processed);
        $this->assertCount(2, $report->changes);
        $this->assertSame([], $api->members[self::GUILD]['800000000000000001']);
        $this->assertSame([], $api->members[self::GUILD]['800000000000000002']);
    }

    public function testApplyAjouteLesRolesPuisLaRelanceEstSansEffet(): void
    {
        $this->linkedUser('800000000000000001');
        $this->linkedUser('800000000000000002'); // pas sur le serveur Discord
        $api = new FakeDiscordApi([self::GUILD => ['800000000000000001' => []]]);

        $report = $this->bulk($api)->run($this->guild, apply: true);

        $this->assertSame([self::ROLE_MEMBRE], $api->members[self::GUILD]['800000000000000001']);
        $this->assertCount(1, $report->changes);
        $this->assertSame(1, $report->absent);

        $again = $this->bulk($api)->run($this->guild, apply: true);

        $this->assertSame([], $again->changes);
        $this->assertSame(1, $again->upToDate);
    }

    public function testLesComptesSontTraitesParPages(): void
    {
        $members = [];
        foreach (['01', '02', '03'] as $suffix) {
            $this->linkedUser('8000000000000000' . $suffix);
            $members['8000000000000000' . $suffix] = [];
        }
        $api = new FakeDiscordApi([self::GUILD => $members]);

        $first = $this->bulk($api)->run($this->guild, apply: true, limit: 2);
        $this->assertSame(2, $first->processed);
        $this->assertSame(3, $first->total);
        $this->assertSame(2, $first->nextOffset);

        $second = $this->bulk($api)->run($this->guild, apply: true, limit: 2, offset: 2);
        $this->assertSame(1, $second->processed);
        $this->assertNull($second->nextOffset);
    }

    public function testArretApresPlusieursErreursConsecutives(): void
    {
        for ($i = 1; $i <= 7; ++$i) {
            $this->linkedUser('80000000000000010' . $i);
        }

        $report = $this->bulk(new FakeDiscordApi([], fail: true))->run($this->guild, apply: true);

        $this->assertTrue($report->aborted);
        $this->assertSame(5, $report->errors);
        $this->assertNull($report->nextOffset);
    }

    public function testPageAdminDeSynchronisationReserveeAuxAdminsAvecJetonCsrf(): void
    {
        $this->client->disableReboot();
        static::getContainer()->set(DiscordApiInterface::class, new FakeDiscordApi([self::GUILD => ['800000000000000001' => []]]));
        $this->linkedUser('800000000000000001');
        $url = \sprintf('/admin/discord/guilde/%d/synchroniser', $this->guild->getId());

        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));
        $this->client->request('POST', $url, ['mode' => 'simulation']);
        $this->assertResponseRedirects('/access_denied');

        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $this->client->request('POST', $url, ['mode' => 'simulation']);
        $this->assertResponseRedirects('/access_denied');

        $crawler = $this->client->request('GET', \sprintf('/admin/discord/guilde/%d', $this->guild->getId()));
        $token = (string) $crawler->filter('form[action$="synchroniser"] input[name=_token]')->first()->attr('value');
        $this->client->request('POST', $url, ['_token' => $token, 'mode' => 'simulation']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-info', 'Simulation');
    }

    private function bulk(FakeDiscordApi $api): DiscordBulkSyncService
    {
        $resolver = static::getContainer()->get(DiscordRoleResolver::class);
        $sync = new DiscordSyncService($resolver, $api, static::getContainer()->get(DiscordGuildRepository::class));

        return new DiscordBulkSyncService($sync, $this->em, 0);
    }

    private function linkedUser(string $discordId): User
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $user->setDiscordId($discordId);
        $this->em->flush();

        return $user;
    }
}
