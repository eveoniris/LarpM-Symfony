<?php

declare(strict_types=1);

namespace App\Entity;

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

    // Todo check encoding
    /** @return array<string, mixed> */
    public function getExportValue(): array
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'tag' => $this->getTag(),
            'description' => mb_convert_encoding((string) $this->getDescriptionRaw(), 'ISO-8859-1'),
        ];
    }
}
