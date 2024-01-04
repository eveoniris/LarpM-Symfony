<?php


namespace App\Repository;

use App\Entity\Participant;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\QuestionRepository.
 *
 * @author kevin
 */
class QuestionRepository extends BaseRepository
{
    /**
     * Trouve toutes les questions auquel le participant n'a pas répondu.
     *
     * @return ArrayCollection $classes
     */
    public function findByParticipant($participant)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT q FROM App\Entity\Question q ORDER BY q.date DESC')
            ->getResult();
    }
}
