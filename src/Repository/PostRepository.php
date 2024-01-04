<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\PostRepository.
 *
 * @author kevin
 */
class PostRepository extends BaseRepository
{
    /**
     * trouve tous les derniers posts classé par date de publication (en prennant en compte les réponses).
     *
     * @return ArrayCollection $religionLevels
     */
    public function findOrderByCreationDate()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM App\Entity\Post p ORDER BY p.creationDate DESC')
            ->getResult();
    }
}
