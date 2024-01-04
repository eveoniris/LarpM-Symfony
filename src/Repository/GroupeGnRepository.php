<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GroupeGnRepository.
 *
 * @author kevin
 */
class GroupeGnRepository extends BaseRepository
{
    /**
     * Trouve un groupe en fonction de son code.
     *
     * @param string $code
     *
     * @return App\Entity\GroupeGn $groupeGn
     */
    public function findOneByCode($code)
    {
        $groupeGns = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGn g WHERE g.code = :code')
            ->setParameter('code', $code)
            ->getResult();

        return reset($groupeGns);
    }

    public function findByGn($gnId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGn g JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
    }
}
