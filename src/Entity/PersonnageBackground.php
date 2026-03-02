<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageBackground extends BasePersonnageBackground
{
    public function __construct()
    {
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }

    public function getDescription(): string
    {
        return (string) $this->getText();
    }

    public function getLabel(): string
    {
        return $this->getGn();
    }

    public function isPrivate(): bool
    {
        return 'private' === strtolower($this->visibility);
    }
}
