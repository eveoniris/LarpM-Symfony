<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Participant;

/**
 * LarpManager\Repository\QuestionRepository.
 *
 * @author kevin
 */
class QuestionRepository extends BaseRepository
{
    /**
     * Trouve toutes les questions auquel le participant n'a pas répondu.
     */
    /** @return array<int, \App\Entity\Question> */
    public function findByParticipant(Participant $participant): array
    {
        return $this->getEntityManager()->createQuery('SELECT q FROM App\Entity\Question q ORDER BY q.date DESC')->getResult();
    }
}
