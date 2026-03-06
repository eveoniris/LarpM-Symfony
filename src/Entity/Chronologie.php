<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChronologieRepository;
use Doctrine\ORM\Mapping\Entity;
use JsonSerializable;

#[Entity(repositoryClass: ChronologieRepository::class)]
class Chronologie extends BaseChronologie implements JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->getYear(),
            'month' => $this->getMonth(),
            'day' => $this->getDay(),
            'description' => $this->getDescription(),
            'visibilite' => $this->getVisibilite(),
            'territoire_id' => $this->getTerritoire()->getId(),
        ];
    }

    public function jsonUnserialize(\stdClass $payload): void
    {
        $this->setYear($payload->year);
        $this->setMonth($payload->month);
        $this->setDay($payload->day);
        $this->setDescription($payload->description);
        $this->setVisibilite($payload->visibilite);
    }
}
