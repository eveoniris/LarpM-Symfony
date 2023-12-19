<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'evenement')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseEvenement', 'extended' => 'Evenement'])]
abstract class BaseEvenement
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'text', type: \Doctrine\DBAL\Types\Types::STRING, length: 450)]
    protected string $text;

    #[Column(name: 'date', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $date_creation;

    protected \DateTime $date_update;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\IntrigueHasEvenement>|\App\Entity\IntrigueHasEvenement[]
     */
    #[OneToMany(mappedBy: 'evenement', targetEntity: IntrigueHasEvenement::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'evenement_id', nullable: 'false')]
    protected Collection $intrigueHasEvenements;

    public function __construct()
    {
        $this->intrigueHasEvenements = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text ?? '';
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateCreation(): \DateTime
    {
        return $this->date_creation;
    }

    public function setDateUpdate(\DateTime $date_update): static
    {
        $this->date_update = $date_update;

        return $this;
    }

    public function getDateUpdate(): \DateTime
    {
        return $this->date_update;
    }

    public function addIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement): static
    {
        $this->intrigueHasEvenements[] = $intrigueHasEvenement;

        return $this;
    }

    public function removeIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement): static
    {
        $this->intrigueHasEvenements->removeElement($intrigueHasEvenement);

        return $this;
    }

    public function getIntrigueHasEvenements(): Collection
    {
        return $this->intrigueHasEvenements;
    }

    /* public function __sleep()
    {
        return ['id', 'text', 'date', 'date_creation', 'date_update'];
    } */
}
