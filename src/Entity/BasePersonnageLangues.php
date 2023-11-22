<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_langues')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_langues_personnage1_idx')]
#[ORM\Index(columns: ['langue_id'], name: 'fk_personnage_langues_langue1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageLangues', 'extended' => 'PersonnageLangues'])]
abstract class BasePersonnageLangues
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`source`", type="string", length=45)
     */
    #[Column(name: 'source', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $source = '';

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageLangues')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    /**
     * @ManyToOne(targetEntity="Langue", inversedBy="personnageLangues")
     *
     * @JoinColumn(name="langue_id", referencedColumnName="id", nullable=false)
     *
     * @OrderBy({"secret" = "ASC", "label" = "ASC"})
     */
    #[ManyToOne(targetEntity: Langue::class, inversedBy: 'personnageLangues')]
    #[JoinColumn(name: 'langue_id', referencedColumnName: 'id', nullable: 'false')]
    #[ORM\OrderBy(['secret' => 'ASC',  'label' => 'ASC'])]
    protected Langue $langue;

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
     * Set the value of source.
     */
    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get the value of source.
     */
    public function getSource(): string
    {
        return $this->source ?? '';
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Langue entity (many to one).
     */
    public function setLangue(Langue $langue = null): static
    {
        $this->langue = $langue;

        return $this;
    }

    /**
     * Get Langue entity (many to one).
     */
    public function getLangue(): ?Langue
    {
        return $this->langue;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'langue_id', 'source'];
    }
}
