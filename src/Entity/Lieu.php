<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: LieuRepository::class)]
class Lieu extends BaseLieu implements Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Fourni la description du document au bon format pour impression.
     */
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }
}
