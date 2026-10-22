<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TriggerType;
use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageTrigger extends BasePersonnageTrigger
{
    /** Trigger donné à l'achat d'une compétence, absorbé par le premier GN joué */
    public const string SOURCE_ACHAT = 'ACHAT_COMPETENCE';

    public function isAchatNonRattache(): bool
    {
        return self::SOURCE_ACHAT === $this->source && null === $this->gn;
    }

    public function isDone(): bool
    {
        return (bool) $this->done;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleted_at;
    }

    /**
     * Consomme le trigger (suppression logique) en gardant ce qui a été choisi.
     */
    public function consume(?string $choiceLabel = null, ?Potion $potion = null, ?Sort $sort = null): static
    {
        $this->deleted_at ??= new DateTime();
        $this->potion = $potion;
        $this->sort = $sort;
        $this->choice_label = $choiceLabel ?? $potion?->getLabel() ?? $sort?->getLabel();

        return $this;
    }

    /** @return array<string, mixed> */
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'TAG' => $this->getTag() instanceof TriggerType ? $this->getTag()->value : $this->getTag(),
        ];
    }
}
