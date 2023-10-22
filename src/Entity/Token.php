<?php

namespace App\Entity;

use App\Repository\GroupeLangueRepository;
use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TokenRepository::class)]
class Token extends BaseToken
{
    /**
     * Fourni la description sans les tags html de mise en forme.
     */
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }

    public function getExportValue(): array
    {
        return [
            $this->getId(),
            $this->getLabel(),
            $this->getTag(),
            mb_convert_encoding((string) $this->getDescriptionRaw(), 'ISO-8859-1'),
        ];
    }
}
