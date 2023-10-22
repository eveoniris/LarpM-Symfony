<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\IntrigueHasDocument.
 *
 * @Table(name="intrigue_has_document", indexes={@Index(name="fk_intrigue_has_document_intrigue1_idx", columns={"intrigue_id"}), @Index(name="fk_intrigue_has_document_document1_idx", columns={"document_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseIntrigueHasDocument", "extended":"IntrigueHasDocument"})
 */
class BaseIntrigueHasDocument
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Intrigue", inversedBy="intrigueHasDocuments", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="intrigue_id", referencedColumnName="id", nullable=false)
     */
    protected $intrigue;

    /**
     * @ManyToOne(targetEntity="Document", inversedBy="intrigueHasDocuments")
     *
     * @JoinColumn(name="document_id", referencedColumnName="id", nullable=false)
     */
    protected $document;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\IntrigueHasDocument
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Intrigue entity (many to one).
     *
     * @return \App\Entity\IntrigueHasDocument
     */
    public function setIntrigue(Intrigue $intrigue = null)
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Intrigue entity (many to one).
     *
     * @return \App\Entity\Intrigue
     */
    public function getIntrigue()
    {
        return $this->intrigue;
    }

    /**
     * Set Document entity (many to one).
     *
     * @return \App\Entity\IntrigueHasDocument
     */
    public function setDocument(Document $document = null)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get Document entity (many to one).
     *
     * @return \App\Entity\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    public function __sleep()
    {
        return ['id', 'intrigue_id', 'document_id'];
    }
}
