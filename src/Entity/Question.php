<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: QuestionRepository::class)]
class Question extends BaseQuestion
{
    /**
     * Fourni la réponse à une question en fonction de son hash.
     */
    public function getReponse(string $hash): string|bool
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
     */
    public function getReponsesCount(string $reponse): int
    {
        $count = 0;
        foreach ($this->getReponses() as $rep) {
            if ($rep->getReponse() != sha1($reponse)) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    /**
     * Obtient la liste des participants ayant répondu (en fonction de la réponse).
     */
    /** @return Collection<int, Participant> */
    public function getParticipants(string $rep): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getReponses() as $reponse) {
            if ($reponse->getReponse() != sha1($rep)) {
                continue;
            }

            $participants->add($reponse->getParticipant());
        }

        return $participants;
    }
}
