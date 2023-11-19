<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'lignees')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseLignee', 'extended' => 'Lignee'])]
abstract class BaseLignee
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $nom;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected $description;

    /**
     * @OneToMany(targetEntity="PersonnageLignee", mappedBy="lignee")
     *
     * @JoinColumn(name="id", referencedColumnName="lignee_id", nullable=false)
     */
    #[OneToMany(mappedBy: 'lignee', targetEntity: PersonnageLignee::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'lignee_id', nullable: 'false')]
    protected ArrayCollection $personnageLignees;

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
     * Set the value of nom.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     */
    public function getNom(): string
    {
        return $this->nom ?? '';
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function getPersonnageLignees(): ArrayCollection
    {
        return $this->personnageLignees;
    }

    public function setPersonnageLignees(ArrayCollection $personnageLignees): static
    {
        $this->personnageLignees = $personnageLignees;

        return $this;
    }

    public function __sleep()
    {
        return ['id', 'nom', 'description'];
    }
}
