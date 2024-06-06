<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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

    public function emailExists(User $user): bool
    {
        return $this->propertyExists($user, 'email');
    }

    public function usernameExists(User $user): bool
    {
        return $this->propertyExists($user, 'username');
    }

    protected function propertyExists(User $user, string $property): bool
    {
        $qb = $this->createQueryBuilder('u');
        $result = $qb->where(
            $qb->expr()->eq('u.'.$property, ':'.$property)
        )->andWhere(
            $qb->expr()->notIn('u.id', ':id')
        )
            ->setParameters(
                [
                    ':'.$property => $user->{'get'.ucfirst($property)}(),
                    ':id' => $user->getId(),
                ]
            )->getQuery();

        return !empty($result->getScalarResult());
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
                DQL
            );

        return $query->getResult();
    }

    public function findOneByConfirmationToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
                ->andWhere('u.confirmationToken = :val')
                ->setParameter('val', $token)
                ->getQuery()
                ->getOneOrNullResult();
    }


    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
