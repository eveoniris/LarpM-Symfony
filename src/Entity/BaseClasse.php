<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\InverseJoinColumn;

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
    protected ?bool $creation = false;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\GroupeClasse>|\App\Entity\GroupeClasse[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: GroupeClasse::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected Collection $groupeClasses;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Personnage>|\App\Entity\Personnage[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: Personnage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected Collection $personnages;

    /**
     * @var Collection<int, PersonnageSecondaire>
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: PersonnageSecondaire::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected Collection $personnageSecondaires;

    /**
     * @var Collection<int, CultureHasClasse>
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: CultureHasClasse::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: 'false')]
    protected Collection $cultureHasClasses;

    #[JoinTable(name: 'classe_competence_family_favorite')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    protected Collection $competenceFamilyFavorites;

    #[JoinTable(name: 'classe_competence_family_normale')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    protected Collection $competenceFamilyNormales;

    #[JoinTable(name: 'classe_competence_family_creation')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    protected Collection $competenceFamilyCreations;

    public function __construct()
    {
        $this->groupeClasses = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->personnageSecondaires = new ArrayCollection();

        $this->competenceFamilyFavorites = new ArrayCollection();
        $this->competenceFamilyNormales = new ArrayCollection();
        $this->competenceFamilyCreations = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabelMasculin(string $label_masculin): static
    {
        $this->label_masculin = $label_masculin;

        return $this;
    }

    public function getLabelMasculin(): string
    {
        return $this->label_masculin;
    }

    public function setLabelFeminin(string $label_feminin): static
    {
        $this->label_feminin = $label_feminin;

        return $this;
    }

    public function getLabelFeminin(): string
    {
        return $this->label_feminin;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setImageM(string $image_m): static
    {
        $this->image_m = $image_m;

        return $this;
    }

    public function getImageM(): string
    {
        return $this->image_m ?? '';
    }

    public function setImageF(string $image_f): static
    {
        $this->image_f = $image_f;

        return $this;
    }

    public function getImageF(): string
    {
        return $this->image_f ?? '';
    }

    public function setCreation(string $creation): static
    {
        $this->creation = $creation;

        return $this;
    }

    public function getCreation(): bool
    {
        return $this->creation;
    }

    public function addGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses[] = $groupeClasse;

        return $this;
    }

    public function removeGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses->removeElement($groupeClasse);

        return $this;
    }

    public function getGroupeClasses(): Collection
    {
        return $this->groupeClasses;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): static
    {
        $this->personnageSecondaires[] = $personnageSecondaire;

        return $this;
    }

    public function removePersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): static
    {
        $this->personnageSecondaires->removeElement($personnageSecondaire);

        return $this;
    }

    public function getPersonnageSecondaires(): Collection
    {
        return $this->personnageSecondaires;
    }

    public function addCultureHasClasses(CultureHasClasse $cultureHasClasses): static
    {
        $this->cultureHasClasses[] = $cultureHasClasses;

        return $this;
    }

    public function removeCultureHasClasse(CultureHasClasse $cultureHasClasses): static
    {
        $this->cultureHasClasses->removeElement($cultureHasClasses);

        return $this;
    }

    public function getCultureHasClasse(): Collection
    {
        return $this->cultureHasClasses;
    }

    public function addCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseFavorite($this);
        $this->competenceFamilyFavorites[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseFavorite($this);
        $this->competenceFamilyFavorites->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyFavorites(): Collection
    {
        return $this->competenceFamilyFavorites;
    }

    public function addCompetenceFamilyNormale(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseNormale($this);
        $this->competenceFamilyNormales[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyNormale(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseNormale($this);
        $this->competenceFamilyNormales->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyNormales(): Collection
    {
        return $this->competenceFamilyNormales;
    }

    public function addCompetenceFamilyCreation(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseCreation($this);
        $this->competenceFamilyCreations[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyCreation(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseCreation($this);
        $this->competenceFamilyCreations->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyCreations(): Collection
    {
        return $this->competenceFamilyCreations;
    }
}
