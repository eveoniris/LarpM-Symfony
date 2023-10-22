<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: DocumentRepository::class)]
class Document extends BaseDocument implements \Stringable
{
    public function __toString(): string
    {
        return (string) $this->getIdentity();
    }

    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('now'));
        $this->setUpdateDate(new \DateTime('now'));
        $this->setImpression(false);
    }

    /**
     * Fourni la description du document au bon format pour impression.
     */
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }

    /**
     * Fourni l'identité d'un document (code + titre).
     */
    public function getIdentity(): string
    {
        return $this->getCode().' '.$this->getTitre();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getCode()));
    }
}
