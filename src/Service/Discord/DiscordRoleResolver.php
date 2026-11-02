<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\DiscordGuild;
use App\Entity\Membre;
use App\Entity\User;
use App\Enum\DiscordRoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Détermine les rôles Discord que doit avoir un utilisateur dans une guilde.
 *
 * - membre : tout compte lié ;
 * - orga / scénariste : ROLE_ORGA, ROLE_SCENARISTE ou ROLE_ADMIN (hiérarchie comprise) ;
 * - groupe transverse : au moins un personnage vivant de l'utilisateur est membre du groupe.
 */
final readonly class DiscordRoleResolver
{
    private const array STAFF_ROLES = ['ROLE_ORGA', 'ROLE_SCENARISTE', 'ROLE_ADMIN'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    /**
     * Rôles Discord souhaités pour l'utilisateur dans la guilde (parmi ceux de la correspondance).
     *
     * @return list<string>
     */
    public function desiredRoleIds(User $user, DiscordGuild $guild): array
    {
        $isStaff = $this->isStaff($user);
        $groupIds = $this->groupIdsOf($user);

        $desired = [];
        foreach ($guild->getMappings() as $mapping) {
            $wanted = match ($mapping->getType()) {
                DiscordRoleType::MEMBRE => true,
                DiscordRoleType::ORGA => $isStaff,
                DiscordRoleType::GROUPE_SECONDAIRE => \in_array($mapping->getSecondaryGroup()?->getId(), $groupIds, strict: true),
            };
            if ($wanted) {
                $desired[] = $mapping->getRoleId();
            }
        }

        return $desired;
    }

    /**
     * Tous les rôles Discord que LarpManager gère dans la guilde : les autres rôles du serveur ne sont jamais touchés.
     *
     * @return list<string>
     */
    public function managedRoleIds(DiscordGuild $guild): array
    {
        $ids = [];
        foreach ($guild->getMappings() as $mapping) {
            $ids[] = $mapping->getRoleId();
        }

        return $ids;
    }

    /**
     * Groupes transverses dont l'utilisateur est membre avec au moins un personnage vivant.
     *
     * @return list<int>
     */
    public function groupIdsOf(User $user): array
    {
        /** @var list<array{id: int|string}> $rows */
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('DISTINCT IDENTITY(m.secondaryGroup) AS id')
            ->from(Membre::class, 'm')
            ->join('m.personnage', 'p')
            ->where('p.user = :user')
            ->andWhere('p.vivant = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    public function isStaff(User $user): bool
    {
        $reachable = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

        return [] !== array_intersect(self::STAFF_ROLES, $reachable);
    }
}
