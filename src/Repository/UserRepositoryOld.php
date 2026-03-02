<?php

declare(strict_types=1);

namespace App\Repository;

class UserRepositoryOld extends BaseRepository
{
    /**
     * Fourni la liste des derniers utilisateurs connectés.
     */
    /** @return array<int, \App\Entity\User> */
    public function lastConnected(): array
    {
        $query = $this->getEntityManager()->createQuery('SELECT u FROM App\Entity\User u WHERE u.lastConnectionDate > CURRENT_TIMESTAMP() - 720 ORDER BY u.lastConnectionDate DESC');

        return $query->getArrayResult();
    }

    /**
     * Utilisateurs sans etat-civil.
     */
    /** @return array<int, \App\Entity\User> */
    public function findWithoutEtatCivil(): array
    {
        $query = $this->getEntityManager()->createQuery('SELECT u FROM App\Entity\User u WHERE IDENTITY(u.etatCivil) IS NULL ORDER BY u.email ASC');

        return $query->getResult();
    }

    /**
     * Trouve le nombre d'utilisateurs correspondant aux critères de recherche.
     *
     * @param array<string, mixed> $criteria
     */
    public function findCount(array $criteria = []): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('u'));
        $qb->from(\App\Entity\User::class, 'u');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les utilisateurs correspondant aux critères de recherche.
     */
    /**
     * @param array<string, string> $order
     *
     * @return array<int, \App\Entity\User>
     */
    public function findList(string $type, ?string $value, int $limit, int $offset, array $order = []): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u');
        $qb->from(\App\Entity\User::class, 'u');

        if ($type && $value) {
            switch ($type) {
                case 'Username':
                    $qb->andWhere('u.Username LIKE :value');
                    $qb->setParameter('value', '%' . $value . '%');
                    break;
                case 'nom':
                    $qb->join('u.etatCivil', 'ec');
                    $qb->andWhere('ec.nom LIKE :value');
                    $qb->setParameter('value', '%' . $value . '%');
                    break;
                case 'email':
                    $qb->andWhere('u.email LIKE :value');
                    $qb->setParameter('value', '%' . $value . '%');
                    break;
                case 'id':
                    $qb->andWhere('u.id = :value');
                    $qb->setParameter('value', $value);
                    break;
            }
        }

        $qb->setFirstResult((int) $offset);
        $qb->setMaxResults((int) $limit);
        $qb->orderBy('u.' . $order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }
}
