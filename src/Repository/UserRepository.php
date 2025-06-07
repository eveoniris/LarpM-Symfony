<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\OrderBy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends BaseRepository implements PasswordUpgraderInterface
{
    public function emailExists(User $user): bool
    {
        return $this->propertyExists($user, 'email');
    }

    protected function propertyExists(User $user, string $property): bool
    {
        $qb = $this->createQueryBuilder('u');
        $result = $qb->where(
            $qb->expr()->eq('u.'.$property, ':'.$property),
        )->andWhere(
            $qb->expr()->notIn('u.id', ':id'),
        )
            ->setParameters(
                [
                    ':'.$property => $user->{'get'.ucfirst($property)}(),
                    ':id' => $user->getId(),
                ],
            )->getQuery();

        return !empty($result->getScalarResult());
    }

    public function findAll(): array
    {
        return $this->findBy([], ['username' => 'ASC']);
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias.'.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.email',
            $alias.'.roles',
            $alias.'.username',
            'etatCivil.nom as nom',
            'etatCivil.prenom as prenom',
            "CONCAT(etatCivil.nom, ' ', etatCivil.prenom) AS HIDDEN nomPrenom",
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.username' => [
                OrderBy::ASC => [$alias.'.username' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.username' => OrderBy::DESC],
            ],
            $alias.'.email' => [
                OrderBy::ASC => [$alias.'.email' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.email' => OrderBy::DESC],
            ],
            'etatCivil.nom' => [
                OrderBy::ASC => ['etatCivil.nom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.nom' => OrderBy::DESC],
            ],
            'etatCivil.prenom' => [
                OrderBy::ASC => ['etatCivil.prenom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.prenom' => OrderBy::DESC],
            ],
            'nomPrenom' => [
                OrderBy::ASC => ['nomPrenom' => OrderBy::ASC],
                OrderBy::DESC => ['nomPrenom' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            'email' => $this->translator->trans('Email', domain: 'repository'),
            'roles' => $this->translator->trans('Roles', domain: 'repository'),
            'username' => $this->translator->trans('Pseudo', domain: 'repository'),
            'etatCivil.nom',
            'nom' => $this->translator->trans('Nom', domain: 'repository'),
            'etatCivil.prenom',
            'prenom' => $this->translator->trans('Prenom', domain: 'repository'),
            'nomPrenom',
            'HIDDEN nomPrenom' => $this->translator->trans('Nom prénom', domain: 'repository'),
        ];
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByConfirmationToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.confirmationToken = :val')
            ->setParameter('val', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[Deprecated]
    public function findWithoutEtatCivil()
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT u 
                FROM App\Entity\User u 
                WHERE IDENTITY(u.etatCivil) IS NULL 
                ORDER BY u.email ASC
                DQL,
            );

        return $query->getResult();
    }

    public function getPersonnagesIds(User $user): array
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT p.id
                FROM App\Entity\User u 
                INNER JOIN App\Entity\Personnage p
                WHERE u.id = :uid
                DQL,
            );

        return $query->setParameter('uid', $user->getId())->getScalarResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function usernameExists(User $user): bool
    {
        return $this->propertyExists($user, 'username');
    }
}
