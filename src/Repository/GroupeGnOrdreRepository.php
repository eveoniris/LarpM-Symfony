<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GroupeGnOrdreRepository.
 *
 * @author Kevin F.
 */
class GroupeGnOrdreRepository extends BaseRepository
{
    /**
     * Trouve un groupe en fonction de son code.
     *
     * @return App\Entity\GroupeGnOrdre $groupeGnOrdre
     */
    public function findByGn($gnId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGnOrdre g JOIN g.groupeGn ggn JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
    }
}
