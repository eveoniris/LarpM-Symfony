<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'intrigue_has_document')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_document_intrigue1_idx')]
#[ORM\Index(columns: ['document_id'], name: 'fk_intrigue_has_document_document1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasDocument', 'extended' => 'IntrigueHasDocument'])]
abstract class BaseIntrigueHasDocument
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasDocuments')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'intrigueHasDocuments')]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    protected Document $document;

    public function __construct()
    {
    }
    
    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set Intrigue entity (many to one).
     */
    public function setIntrigue(?Intrigue $intrigue = null): static
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Intrigue entity (many to one).
     */
    public function getIntrigue(): Intrigue
    {
        return $this->intrigue;
    }

    /**
     * Set Document entity (many to one).
     */
    public function setDocument(?Document $document = null): static
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get Document entity (many to one).
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /* public function __sleep()
    {
        return ['id', 'intrigue_id', 'document_id'];
    } */
}
