<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: QuestionRepository::class)]
class Question extends BaseQuestion
{
    /**
     * Fourni la réponse à une question en fonction de son hash.
     *
     * @param unknown $hash
     */
    public function getReponse($hash): string|bool
    {
        foreach (preg_split('/[;]+/', $this->getChoix()) as $reponse) {
            if (sha1($reponse) === $hash) {
                return $reponse;
            }
        }

        return false;
    }

    /**
     * Compte les réponse à une question.
     *
     * @param unknown $reponse
     */
    public function getReponsesCount($reponse): int
    {
        $count = 0;
        foreach ($this->getReponses() as $rep) {
            if ($rep->getReponse() == sha1($reponse)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Obtient la liste des participants ayant répondu (en fonction de la réponse).
     *
     * @param unknown $rep
     */
    public function getParticipants($rep): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getReponses() as $reponse) {
            if ($reponse->getReponse() == sha1($rep)) {
                $participants[] = $reponse->getParticipant();
            }
        }

        return $participants;
    }
}
