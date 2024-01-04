<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\TitreRepository.
 *
 * @author kevin
 */
class TitreRepository extends BaseRepository
{
    /**
     * Trouve tous les titres classé par renommé.
     *
     * @return ArrayCollection $sorts
     */
    public function findByRenomme()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Titre t ORDER BY t.renomme ASC')
            ->getResult();
    }
}
