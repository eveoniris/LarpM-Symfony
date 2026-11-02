<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\DiscordGuild;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Enum\DiscordRoleType;
use App\Service\Discord\DiscordMappingService;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Configuration des guildes Discord et du mapping des rôles (page admin + service).
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordAdminTest extends WebTestCase
{
    private const string ROLE_A = '100000000000000001';
    private const string ROLE_B = '100000000000000002';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testNonAdminEstRefuse(): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ORGA']]));
        $this->client->request('GET', '/admin/discord');

        $this->assertResponseRedirects('/access_denied');
    }

    public function testAdminVoitLaListe(): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));
        $this->client->request('GET', '/admin/discord');

        $this->assertResponseIsSuccessful();
    }

    public function testApplyCreeMetAJourEtSupprimeLesCorrespondances(): void
    {
        $guild = $this->createGuild();
        $group = $this->createGroup();
        $service = static::getContainer()->get(DiscordMappingService::class);

        $this->assertSame([], $service->apply($guild, self::ROLE_A, self::ROLE_B, [$group->getId() => '100000000000000003']));
        $this->assertCount(3, $guild->getMappings());

        // Mise à jour du rôle membre, suppression du rôle orga et du groupe (champs vides).
        $this->assertSame([], $service->apply($guild, '100000000000000009', '', [$group->getId() => '']));
        $this->em->refresh($guild);

        $this->assertCount(1, $guild->getMappings());
        $mapping = $guild->getMappings()->first();
        $this->assertSame(DiscordRoleType::MEMBRE, $mapping->getType());
        $this->assertSame('100000000000000009', $mapping->getRoleId());
    }

    public function testApplyRefuseUnIdentifiantInvalideSansRienEnregistrer(): void
    {
        $guild = $this->createGuild();
        $service = static::getContainer()->get(DiscordMappingService::class);

        $errors = $service->apply($guild, self::ROLE_A, 'pas-un-id', []);

        $this->assertNotSame([], $errors);
        $this->assertCount(0, $guild->getMappings());
    }

    public function testApplyRefuseUnRoleUtiliseDeuxFois(): void
    {
        $guild = $this->createGuild();
        $group = $this->createGroup();
        $service = static::getContainer()->get(DiscordMappingService::class);

        $errors = $service->apply($guild, self::ROLE_A, '', [$group->getId() => self::ROLE_A]);

        $this->assertNotSame([], $errors);
        $this->assertCount(0, $guild->getMappings());
    }

    private function createGuild(): DiscordGuild
    {
        $guild = (new DiscordGuild())
            ->setGuildId('900000000000000001')
            ->setNom('Serveur test');
        $this->em->persist($guild);
        $this->em->flush();

        return $guild;
    }

    private function createGroup(): SecondaryGroup
    {
        $type = new SecondaryGroupType();
        $type->setLabel('Type test');
        $this->em->persist($type);

        $group = new SecondaryGroup();
        $group->setLabel('Groupe test');
        $group->setSecondaryGroupType($type);
        $this->em->persist($group);
        $this->em->flush();

        return $group;
    }
}
