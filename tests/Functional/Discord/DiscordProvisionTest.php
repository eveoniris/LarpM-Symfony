<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\DiscordGuild;
use App\Entity\DiscordRoleMapping;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Enum\DiscordProvisionStatus;
use App\Enum\DiscordRoleType;
use App\Service\Discord\DiscordApiInterface;
use App\Service\Discord\DiscordProvisioningService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Provisionnement Discord : numéro libre, rôle, catégorie privée et salon par groupe transverse.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordProvisionTest extends WebTestCase
{
    private const string GUILD = '900000000000000001';
    private const string BOT = '800000000000000009';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private DiscordGuild $guild;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->guild = (new DiscordGuild())
            ->setGuildId(self::GUILD)
            ->setNom('Serveur test');
        $this->em->persist($this->guild);
        $this->em->flush();
    }

    public function testNomsSimplifiesSansAccentsLimitesACentCaracteres(): void
    {
        $this->assertSame('012', DiscordProvisioningService::roleName(12));
        $this->assertSame('012-ordre-de-l-epee-noire', DiscordProvisioningService::channelName(12, 'Ordre de l\'Épée Noire'));
        $this->assertSame('1233 Ordre de l\'Épée Noire', DiscordProvisioningService::categoryName(1233, "  Ordre de   l'Épée Noire "));
        $this->assertLessThanOrEqual(100, mb_strlen(DiscordProvisioningService::categoryName(1, str_repeat('long ', 40))));
        $this->assertLessThanOrEqual(100, mb_strlen(DiscordProvisioningService::channelName(1, str_repeat('long ', 40))));
        $this->assertStringEndsNotWith('-', DiscordProvisioningService::channelName(1, str_repeat('long ', 40)));
    }

    public function testSimulationNeCreeRienEtChoisitLeNumeroSuivant(): void
    {
        $this->createGroup('Gardiens du Nord');
        $api = new FakeDiscordApi([], roles: [self::GUILD => [['id' => '1', 'name' => '005']]]);

        $lines = $this->service($api)->provision($this->guild, apply: false);

        $this->assertCount(1, $lines);
        $this->assertSame(DiscordProvisionStatus::PLANIFIE, $lines[0]->status);
        $this->assertSame(6, $lines[0]->numero);
        $this->assertSame('006-gardiens-du-nord', $lines[0]->channelName);
        $this->assertSame([], $api->created);
        $this->assertCount(0, $this->guild->getMappings());
    }

    public function testApplyCreeLeRoleLaCategoriePriveeEtSonSalon(): void
    {
        $group = $this->createGroup('Gardiens du Nord');
        $api = new FakeDiscordApi([], roles: [self::GUILD => [['id' => '1', 'name' => '005']]]);

        $lines = $this->service($api)->provision($this->guild, apply: true);

        $this->assertSame(DiscordProvisionStatus::CREE, $lines[0]->status);
        $this->assertSame(['role', 'category', 'channel'], array_column($api->created, 'type'));
        $this->assertSame('006', $api->created[0]['name']);
        $this->assertSame('006 Gardiens du Nord', $api->created[1]['name'], 'La catégorie garde la casse, comme sur le serveur.');
        $this->assertSame('006-gardiens-du-nord', $api->created[2]['name']);

        $mapping = $this->guild->getMappings()->first();
        $this->assertInstanceOf(DiscordRoleMapping::class, $mapping);
        $this->assertSame($group->getId(), $mapping->getSecondaryGroup()?->getId());
        $this->assertSame(6, $mapping->getNumero());
        $this->assertNotNull($mapping->getCategoryId());
        $this->assertNotNull($mapping->getChannelId());
        $this->assertSame($mapping->getCategoryId(), $api->created[2]['parent_id']);

        // Catégorie privée : @everyone (id de la guilde) ne voit pas, le rôle du groupe voit.
        $overwrites = array_column($api->created[1]['overwrites'], null, 'id');
        $this->assertSame('1024', $overwrites[self::GUILD]['deny']);
        $this->assertSame(0, $overwrites[$mapping->getRoleId()]['type']);
        $this->assertNotSame('0', $overwrites[$mapping->getRoleId()]['allow']);
        $this->assertArrayHasKey(self::BOT, $overwrites);
    }

    public function testRelancerNeCreeRienDeNouveau(): void
    {
        $this->createGroup('Gardiens du Nord');
        $api = new FakeDiscordApi([]);
        $service = $this->service($api);

        $service->provision($this->guild, apply: true);
        $createdAfterFirstRun = \count($api->created);
        $lines = $service->provision($this->guild, apply: true);

        $this->assertSame([], $lines);
        $this->assertCount($createdAfterFirstRun, $api->created);
    }

    public function testUneErreurArreteLeTraitementEtLaRepriseContinueSansDoublon(): void
    {
        $this->createGroup('Gardiens du Nord');
        $this->createGroup('Ordre du Sud');
        $failing = new FakeDiscordApi([], failChannelCreation: true);

        $lines = $this->service($failing)->provision($this->guild, apply: true);

        $this->assertCount(1, $lines, 'Le traitement s\'arrête à la première erreur.');
        $this->assertSame(DiscordProvisionStatus::ERREUR, $lines[0]->status);
        $mapping = $this->guild->getMappings()->first();
        $this->assertNotSame('', $mapping->getRoleId(), 'Le rôle créé est conservé.');
        $this->assertNull($mapping->getCategoryId());

        $working = new FakeDiscordApi([], roles: $failing->roles, channels: $failing->channels);
        $lines = $this->service($working)->provision($this->guild, apply: true);

        $this->assertSame([DiscordProvisionStatus::CREE, DiscordProvisionStatus::CREE], array_map(static fn ($line) => $line->status, $lines));
        // Le groupe interrompu est repris d'abord (sans recréer son rôle), puis le suivant est traité en entier.
        $this->assertSame(['category', 'channel', 'role', 'category', 'channel'], array_column($working->created, 'type'));
        $this->assertCount(2, $this->guild->getMappings());
    }

    public function testUneCorrespondanceSaisieALaMainEstLaissee(): void
    {
        $group = $this->createGroup('Gardiens du Nord');
        $mapping = (new DiscordRoleMapping())
            ->setGuild($this->guild)
            ->setType(DiscordRoleType::GROUPE_SECONDAIRE)
            ->setSecondaryGroup($group)
            ->setRoleId('100000000000000001');
        $this->guild->addMapping($mapping);
        $this->em->flush();
        $api = new FakeDiscordApi([]);

        $lines = $this->service($api)->provision($this->guild, apply: true);

        $this->assertSame([], $lines);
        $this->assertSame([], $api->created);
    }

    public function testLaLimiteBorneLeNombreDeGroupesTraites(): void
    {
        $this->createGroup('Groupe A');
        $this->createGroup('Groupe B');
        $this->createGroup('Groupe C');

        $lines = $this->service(new FakeDiscordApi([]))->provision($this->guild, apply: true, limit: 2);

        $this->assertCount(2, $lines);
    }

    public function testPageDeSimulationReserveeAuxAdminsAvecJetonCsrf(): void
    {
        $this->createGroup('Gardiens du Nord');
        // Plusieurs requêtes dans ce test : sans cela le noyau redémarre et l'API factice injectée est perdue.
        $this->client->disableReboot();
        static::getContainer()->set(DiscordApiInterface::class, new FakeDiscordApi([]));
        $url = \sprintf('/admin/discord/guilde/%d/provisionner', $this->guild->getId());

        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));
        $this->client->request('POST', $url, ['mode' => 'simulation']);
        $this->assertResponseRedirects('/access_denied');

        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $this->client->request('POST', $url, ['mode' => 'simulation']);
        $this->assertResponseRedirects('/access_denied');

        $crawler = $this->client->request('GET', \sprintf('/admin/discord/guilde/%d', $this->guild->getId()));
        $token = (string) $crawler->filter('form[action$="provisionner"] input[name=_token]')->first()->attr('value');
        $crawler = $this->client->request('POST', $url, ['_token' => $token, 'mode' => 'simulation']);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Gardiens du Nord', $crawler->filter('table')->text());
        $this->assertStringContainsString('001-gardiens-du-nord', $crawler->filter('table')->text());
    }

    public function testUnGroupeDejaPresentSurDiscordEstRattacheSansRienCreer(): void
    {
        $group = $this->createGroup('La Confrérie du Savoir');
        $this->createGroup('Ordre du Sud');
        $api = $this->apiWithExistingGroup();

        $simulation = $this->service($api)->provision($this->guild, apply: false);

        $this->assertSame(DiscordProvisionStatus::A_RATTACHER, $simulation[0]->status);
        $this->assertSame(1232, $simulation[0]->numero);
        $this->assertSame(DiscordProvisionStatus::PLANIFIE, $simulation[1]->status);
        $this->assertSame(1233, $simulation[1]->numero, 'Le nouveau groupe prend le numéro suivant.');

        $this->service($api)->provision($this->guild, apply: true);

        $mapping = $this->guild->getMappings()->first();
        $this->assertSame($group->getId(), $mapping->getSecondaryGroup()?->getId());
        $this->assertSame(1232, $mapping->getNumero());
        $this->assertSame('500000000000000001', $mapping->getRoleId());
        $this->assertSame('500000000000000002', $mapping->getCategoryId());
        $this->assertSame('500000000000000003', $mapping->getChannelId());
        $this->assertSame(['role', 'category', 'channel'], array_column($api->created, 'type'), 'Seul le groupe absent de Discord est créé.');
    }

    public function testUnGroupeIgnoreNEstNiCreeNiRattache(): void
    {
        $this->createGroup('La Confrérie du Savoir')->setDiscordIgnore(true);
        $this->createGroup('Ordre du Sud')->setDiscordIgnore(true);
        $this->em->flush();
        $api = $this->apiWithExistingGroup();

        $this->assertSame([], $this->service($api)->provision($this->guild, apply: true));
        $this->assertSame([], $api->created);
    }

    public function testLesCategoriesSansGroupeCorrespondantSontSignalees(): void
    {
        $this->createGroup('Ordre du Sud');
        $api = $this->apiWithExistingGroup();

        $unmatched = $this->service($api)->unmatchedCategories($this->guild);

        $this->assertSame([['numero' => 1232, 'name' => '1232-confrerie-du-savoir']], $unmatched);
    }

    public function testLaCaseIgnorerDeLaPageDeLaGuildeEstEnregistree(): void
    {
        $group = $this->createGroup('Ordre du Sud');
        $this->client->disableReboot();
        static::getContainer()->set(DiscordApiInterface::class, new FakeDiscordApi([]));
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $crawler = $this->client->request('GET', \sprintf('/admin/discord/guilde/%d', $this->guild->getId()));
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['ignores[' . $group->getId() . ']']->tick();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->em->clear();
        $this->assertTrue($this->em->find(SecondaryGroup::class, $group->getId())->isDiscordIgnore());
    }

    /** Serveur où le groupe « Confrérie du Savoir » existe déjà : rôle 1232, catégorie et salon 1232-confrerie-du-savoir. */
    private function apiWithExistingGroup(): FakeDiscordApi
    {
        return new FakeDiscordApi(
            [],
            roles: [self::GUILD => [['id' => '500000000000000001', 'name' => '1232']]],
            channels: [
                self::GUILD => [
                    ['id' => '500000000000000002', 'name' => '1232-confrerie-du-savoir', 'type' => 4, 'parent_id' => null],
                    ['id' => '500000000000000003', 'name' => '1232-confrerie-du-savoir', 'type' => 0, 'parent_id' => '500000000000000002'],
                ],
            ],
        );
    }

    private function service(FakeDiscordApi $api): DiscordProvisioningService
    {
        return new DiscordProvisioningService($api, $this->em, self::BOT);
    }

    private function createGroup(string $label): SecondaryGroup
    {
        $type = $this->em->getRepository(SecondaryGroupType::class)->findOneBy(['label' => 'Type test']);
        if (null === $type) {
            $type = new SecondaryGroupType();
            $type->setLabel('Type test');
            $this->em->persist($type);
        }

        $group = new SecondaryGroup();
        $group->setLabel($label);
        $group->setSecondaryGroupType($type);
        $this->em->persist($group);
        $this->em->flush();

        return $group;
    }
}
