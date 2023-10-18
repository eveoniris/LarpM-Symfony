<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\ChronologieRepository;

#[Entity(repositoryClass: ChronologieRepository::class)]
class Chronologie extends BaseChronologie implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->getYear(),
            'month' => $this->getMonth(),
            'day' => $this->getDay(),
            'description' => $this->getDescription(),
            'visibilite' => $this->getVisibilite(),
            'territoire_id' => ($this->getTerritoire()) ? $this->getTerritoire()->getId() : '',
        ];
    }

    public function jsonUnserialize($payload): void
    {
        $this->setYear($payload->year);
        $this->setMonth($payload->month);
        $this->setDay($payload->day);
        $this->setDescription($payload->description);
        $this->setVisibilite($payload->visibilite);
    }
}
