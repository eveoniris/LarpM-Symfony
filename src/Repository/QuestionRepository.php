<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Participant;

class QuestionRepository extends BaseRepository
{
    /**
     * Trouve toutes les questions auxquelles le participant n'a pas répondu.
     */
    /** @return array<int, \App\Entity\Question> */
    public function findByParticipant(?Participant $participant): array
    {
        if (null === $participant) {
            return [];
        }

        $query = $this->getEntityManager()->createQuery(<<<DQL
                SELECT q FROM App\Entity\Question q
                WHERE q.id NOT IN (
                    SELECT IDENTITY(r.question) FROM App\Entity\Reponse r WHERE r.participant = :participant
                )
                ORDER BY q.date DESC
            DQL);
        $query->setParameter('participant', $participant->getId());

        return $query->getResult();
    }
}
