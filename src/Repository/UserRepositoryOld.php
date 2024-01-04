<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\UserRepository.
 *
 * @author kevin
 */
class UserRepositoryOld extends BaseRepository
{
    /**
     * Fourni la liste des derniers utilisateurs connectés.
     */
    public function lastConnected()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT u FROM App\Entity\User u WHERE u.lastConnectionDate > CURRENT_TIMESTAMP() - 720 ORDER BY u.lastConnectionDate DESC');

        return $query->getArrayResult();
    }

    /**
     * Utilisateurs sans etat-civil.
     */
    public function findWithoutEtatCivil()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT u FROM App\Entity\User u WHERE IDENTITY(u.etatCivil) IS NULL ORDER BY u.email ASC');

        return $query->getResult();
    }

    /**
     * Trouve le nombre d'utilisateurs correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
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
     *
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, ?string $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u');
        $qb->from(\App\Entity\User::class, 'u');

        if ($type && $value) {
            switch ($type) {
                case 'Username':
                    $qb->andWhere('u.Username LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'nom':
                    $qb->join('u.etatCivil', 'ec');
                    $qb->andWhere('ec.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'email':
                    $qb->andWhere('u.email LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('u.id = :value');
                    $qb->setParameter('value', $value);
                    break;
            }
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('u.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }
}
