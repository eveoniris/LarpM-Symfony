<?php

declare(strict_types=1);

namespace App\Trait;

use App\Entity\Participant;

/**
 * Gestion du droit de responsable administratif d'une session (GroupeGn).
 */
trait GroupeGnResponsableTrait
{
    /**
     * Retire le droit de responsable (session et groupe) si le participant le détient.
     */
    public function retirerResponsableSi(Participant $participant): bool
    {
        $groupe = $this->getGroupe();
        $userId = $participant->getUser()?->getId();
        $estResponsable = $this->getParticipant()?->getId() === $participant->getId();
        $estResponsableGroupe = null !== $userId && $groupe?->getUserRelatedByResponsableId()?->getId() === $userId;

        $estResponsable && $this->setResponsableNull();
        $estResponsableGroupe && $groupe->setUserRelatedByResponsableId(null);

        return $estResponsable || $estResponsableGroupe;
    }

    /**
     * Le groupe a-t-il un responsable administratif (session ou groupe) ?
     */
    public function hasResponsable(): bool
    {
        return null !== $this->getParticipant() || null !== $this->getGroupe()?->getUserRelatedByResponsableId();
    }

    /**
     * Le responsable du groupe suit celui de la session (le groupe peut ne pas être encore défini).
     */
    protected function synchroniserResponsableGroupe(?Participant $participant): void
    {
        ($this->groupe ?? null)?->setUserRelatedByResponsableId($participant?->getUser());
    }
}
