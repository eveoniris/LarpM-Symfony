<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageLignee extends BasePersonnageLignee
{
    /**
     * Fourni la liste des descendants directs.
     */
    public function getDescendants(): null
    {
        // TODO: getDescendants

        return null;
    }

    public function getLabel(): ?string
    {
        return $this->getLignee()?->getLabel() ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->getLignee()?->getDescription() ?? '';
    }
}
