<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Enum\Role;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ROLE_BETA_TEST : accès aux développements en cours, attribué par le super admin uniquement.
 */
#[Group('functional')]
class RoleBetaTestTest extends WebTestCase
{
    private function roles(int $id): array
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->clear();

        return $entityManager->find(User::class, $id)->getRoles();
    }

    public function testHierarchieSeulLeSuperAdminEtLeDevOntLeRoleDOffice(): void
    {
        self::bootKernel();
        $authorization = static::getContainer()->get('security.role_hierarchy');

        static::assertContains(Role::BETA_TEST->value, $authorization->getReachableRoleNames([Role::SUPER_ADMIN->value]));
        static::assertContains(Role::BETA_TEST->value, $authorization->getReachableRoleNames([Role::DEV->value]));
        static::assertNotContains(Role::BETA_TEST->value, $authorization->getReachableRoleNames([Role::ADMIN->value]));
        static::assertNotContains(Role::BETA_TEST->value, $authorization->getReachableRoleNames([Role::ORGA->value]));
    }

    public function testUnAdminSimpleNePeutPasAttribuerLeRole(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('POST', '/user/' . $cible->getId() . '/edit', [
            'email' => (string) $cible->getEmail(),
            'roles' => ['ROLE_USER', 'ROLE_ORGA', 'ROLE_BETA_TEST', 'ROLE_SUPER_ADMIN'],
        ]);

        $roles = $this->roles($cible->getId());
        static::assertContains('ROLE_ORGA', $roles);
        static::assertNotContains('ROLE_BETA_TEST', $roles);
        static::assertNotContains('ROLE_SUPER_ADMIN', $roles);
    }

    public function testUnAdminSimpleConserveLeRoleDejaAttribue(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        // modification d'un autre rôle : le rôle réservé n'est ni proposé ni retiré
        $client->request('POST', '/user/' . $cible->getId() . '/edit', [
            'email' => (string) $cible->getEmail(),
            'roles' => ['ROLE_USER', 'ROLE_STOCK'],
        ]);

        $roles = $this->roles($cible->getId());
        static::assertContains('ROLE_STOCK', $roles);
        static::assertContains('ROLE_BETA_TEST', $roles);
    }

    public function testLeSuperAdminAttribueEtRetireLeRole(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SUPER_ADMIN']]));

        $client->request('POST', '/user/' . $cible->getId() . '/edit', ['email' => (string) $cible->getEmail(), 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        static::assertContains('ROLE_BETA_TEST', $this->roles($cible->getId()));

        $client->request('POST', '/user/' . $cible->getId() . '/edit', ['email' => (string) $cible->getEmail(), 'roles' => ['ROLE_USER']]);
        static::assertNotContains('ROLE_BETA_TEST', $this->roles($cible->getId()));
    }

    public function testCaseProposeeSeulementAuSuperAdmin(): void
    {
        $client = static::createClient();
        $cible = UserFactory::createOne(['roles' => ['ROLE_USER']]);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));
        $admin = $client->request('GET', '/user/' . $cible->getId() . '/edit');
        static::assertCount(0, $admin->filter('input[name="roles[]"][value="ROLE_BETA_TEST"]'));

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SUPER_ADMIN']]));
        $super = $client->request('GET', '/user/' . $cible->getId() . '/edit');
        static::assertCount(1, $super->filter('input[name="roles[]"][value="ROLE_BETA_TEST"]'));
    }
}
