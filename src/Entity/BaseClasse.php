<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'classe')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseClasse', 'extended' => 'Classe'])]
abstract class BaseClasse
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label_masculin', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $label_masculin = null;

    #[Column(name: 'label_feminin', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $label_feminin = null;

    #[Column(name: 'description', type: \Doctrine\DBAL\Types\Types::STRING, length: 450, nullable: true)]
    protected ?string $description = null;

    #[Column(name: 'image_m', type: \Doctrine\DBAL\Types\Types::STRING, length: 90, nullable: true)]
    protected ?string $image_m = null;

    #[Column(name: 'image_f', type: \Doctrine\DBAL\Types\Types::STRING, length: 90, nullable: true)]
    protected ?string $image_f = null;

    #[Column(name: 'creation', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected bool $creation = false;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\GroupeClasse>|\App\Entity\GroupeClasse[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: GroupeClasse::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected ArrayCollection $groupeClasses;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Personnage>|\App\Entity\Personnage[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: Personnage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected ArrayCollection $personnages;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\PersonnageSecondaire>|\App\Entity\PersonnageSecondaire[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: PersonnageSecondaire::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected ArrayCollection $personnageSecondaires;

    public function __construct()
    {
        $this->groupeClasses = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->personnageSecondaires = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabelMasculin(string $label_masculin): self
    {
        $this->label_masculin = $label_masculin;

        return $this;
    }

    public function getLabelMasculin(): string
    {
        return $this->label_masculin;
    }

    public function setLabelFeminin(string $label_feminin): self
    {
        $this->label_feminin = $label_feminin;

        return $this;
    }

    public function getLabelFeminin(): string
    {
        return $this->label_feminin;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setImageM(string $image_m): self
    {
        $this->image_m = $image_m;

        return $this;
    }

    public function getImageM(): string
    {
        return $this->image_m;
    }

    public function setImageF(string $image_f): self
    {
        $this->image_f = $image_f;

        return $this;
    }

    public function getImageF(): string
    {
        return $this->image_f;
    }

    public function setCreation(string $creation): self
    {
        $this->creation = $creation;

        return $this;
    }

    public function getCreation(): bool
    {
        return $this->creation;
    }

    public function addGroupeClasse(GroupeClasse $groupeClasse): self
    {
        $this->groupeClasses[] = $groupeClasse;

        return $this;
    }

    public function removeGroupeClasse(GroupeClasse $groupeClasse): self
    {
        $this->groupeClasses->removeElement($groupeClasse);

        return $this;
    }

    public function getGroupeClasses(): ArrayCollection
    {
        return $this->groupeClasses;
    }

    public function addPersonnage(Personnage $personnage): self
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function removePersonnage(Personnage $personnage): self
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getPersonnages(): ArrayCollection
    {
        return $this->personnages;
    }

    public function addPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): self
    {
        $this->personnageSecondaires[] = $personnageSecondaire;

        return $this;
    }

    public function removePersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): self
    {
        $this->personnageSecondaires->removeElement($personnageSecondaire);

        return $this;
    }

    public function getPersonnageSecondaires(): ArrayCollection
    {
        return $this->personnageSecondaires;
    }

    public function __sleep()
    {
        return ['id', 'label_masculin', 'label_feminin', 'description', 'image_m', 'image_f', 'creation'];
    }
}
