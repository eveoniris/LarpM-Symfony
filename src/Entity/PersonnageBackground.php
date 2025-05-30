<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageBackground extends BasePersonnageBackground
{
    public function __construct()
    {
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }

    public function getDescription(): string
    {
        return $this->getText() ?? '';
    }

    public function getLabel(): string
    {
        return $this->getGn();
    }

    public function isPrivate(): bool
    {
        return strtolower($this->visibility) === 'private';
    }
}
