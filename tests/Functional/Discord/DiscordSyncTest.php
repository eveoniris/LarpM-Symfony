<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Entity\DiscordGuild;
use App\Entity\Membre;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Entity\User;
use App\Service\Discord\DiscordMappingService;
use App\Service\Discord\DiscordRoleResolver;
use App\Service\Discord\DiscordSyncService;
use App\Repository\DiscordGuildRepository;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Calcul des rôles Discord d'un utilisateur et synchronisation (ajouts et retraits) avec une API simulée.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class DiscordSyncTest extends KernelTestCase
{
    private const string GUILD = '900000000000000001';
    private const string DISCORD_USER = '800000000000000001';
    private const string ROLE_MEMBRE = '100000000000000001';
    private const string ROLE_ORGA = '100000000000000002';
    private const string ROLE_GROUPE = '100000000000000003';
    private const string ROLE_MANUEL = '100000000000000099';

    private EntityManagerInterface $em;
    private DiscordGuild $guild;
    private SecondaryGroup $group;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->guild = (new DiscordGuild())
            ->setGuildId(self::GUILD)
            ->setNom('Serveur test');
        $this->em->persist($this->guild);

        $type = new SecondaryGroupType();
        $type->setLabel('Ordre');
        $this->em->persist($type);
        $this->group = new SecondaryGroup();
        $this->group->setLabel('Confrérie');
        $this->group->setSecondaryGroupType($type);
        $this->em->persist($this->group);
        $this->em->flush();

        $errors = static::getContainer()->get(DiscordMappingService::class)->apply($this->guild, self::ROLE_MEMBRE, self::ROLE_ORGA, [$this->group->getId() => self::ROLE_GROUPE]);
        $this->assertSame([], $errors);
    }

    public function testMembreSeulRecoitLeRoleMembre(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);

        $this->assertSame([self::ROLE_MEMBRE], $this->resolver()->desiredRoleIds($user, $this->guild));
    }

    public function testPersonnageVivantMembreDuGroupeDonneLeRoleDuGroupe(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $this->joinGroup($user, vivant: true);

        $desired = $this->resolver()->desiredRoleIds($user, $this->guild);

        $this->assertEqualsCanonicalizing([self::ROLE_MEMBRE, self::ROLE_GROUPE], $desired);
    }

    public function testPersonnageMortNeDonnePlusLeRoleDuGroupe(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $this->joinGroup($user, vivant: false);

        $this->assertSame([self::ROLE_MEMBRE], $this->resolver()->desiredRoleIds($user, $this->guild));
    }

    public function testUnPersonnageVivantSuffitParmiPlusieurs(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $this->joinGroup($user, vivant: false);
        $this->joinGroup($user, vivant: true);

        $this->assertContains(self::ROLE_GROUPE, $this->resolver()->desiredRoleIds($user, $this->guild));
    }

    public function testAdminScenaristeEtOrgaRecoiventLeRoleOrga(): void
    {
        foreach (['ROLE_ADMIN', 'ROLE_SCENARISTE', 'ROLE_ORGA'] as $index => $role) {
            $user = $this->createLinkedUser(['ROLE_USER', $role], (string) (800000000000000100 + $index));

            $this->assertContains(self::ROLE_ORGA, $this->resolver()->desiredRoleIds($user, $this->guild), $role);
        }
    }

    public function testSyncAjouteLesRolesManquantsEtRetireLesRolesGeresPerimes(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $this->joinGroup($user, vivant: true);
        // Rôle orga géré mais injustifié : retiré ; rôle manuel du serveur : intact.
        $api = new FakeDiscordApi([self::GUILD => [self::DISCORD_USER => [self::ROLE_ORGA, self::ROLE_MANUEL]]]);

        $result = $this->syncService($api)->sync($user, $this->guild);

        $this->assertTrue($result->isOk());
        $this->assertEqualsCanonicalizing([self::ROLE_MEMBRE, self::ROLE_GROUPE], $result->added);
        $this->assertSame([self::ROLE_ORGA], $result->removed);
        $this->assertEqualsCanonicalizing(['Membre', 'Confrérie'], $result->addedLabels);
        $this->assertSame(['Orga / scénariste'], $result->removedLabels);
        $this->assertStringContainsString('retiré(s) : Orga / scénariste', $result->summary());
        $this->assertEqualsCanonicalizing([self::ROLE_MEMBRE, self::ROLE_GROUPE, self::ROLE_MANUEL], $api->members[self::GUILD][self::DISCORD_USER]);
    }

    public function testSyncRetireLeRoleDuGroupeQuandLePersonnageMeurt(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $this->joinGroup($user, vivant: false);
        $api = new FakeDiscordApi([self::GUILD => [self::DISCORD_USER => [self::ROLE_MEMBRE, self::ROLE_GROUPE]]]);

        $result = $this->syncService($api)->sync($user, $this->guild);

        $this->assertSame([], $result->added);
        $this->assertSame([self::ROLE_GROUPE], $result->removed);
    }

    public function testSyncSansMembreSurLeServeur(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);

        $result = $this->syncService(new FakeDiscordApi([]))->sync($user, $this->guild);

        $this->assertFalse($result->inGuild);
        $this->assertFalse($result->isOk());
    }

    public function testSyncSansCompteLie(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $result = $this->syncService(new FakeDiscordApi([]))->sync($user, $this->guild);

        $this->assertNotNull($result->error);
    }

    public function testSyncRemonteUneErreurDeLApiSansLever(): void
    {
        $user = $this->createLinkedUser(['ROLE_USER']);
        $api = new FakeDiscordApi([self::GUILD => [self::DISCORD_USER => []]], fail: true);

        $result = $this->syncService($api)->sync($user, $this->guild);

        $this->assertSame('Discord indisponible', $result->error);
    }

    private function resolver(): DiscordRoleResolver
    {
        return static::getContainer()->get(DiscordRoleResolver::class);
    }

    private function syncService(FakeDiscordApi $api): DiscordSyncService
    {
        return new DiscordSyncService($this->resolver(), $api, static::getContainer()->get(DiscordGuildRepository::class));
    }

    /** @param list<string> $roles */
    private function createLinkedUser(array $roles, string $discordId = self::DISCORD_USER): User
    {
        $user = UserFactory::createOne(['roles' => $roles]);
        $user->setDiscordId($discordId);
        $this->em->flush();

        return $user;
    }

    private function joinGroup(User $user, bool $vivant): void
    {
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $personnage->setVivant($vivant);

        $membre = new Membre();
        $membre->setPersonnage($personnage);
        $membre->setSecondaryGroup($this->group);
        $membre->setSecret(false);
        $this->em->persist($membre);
        $this->em->flush();
    }
}
