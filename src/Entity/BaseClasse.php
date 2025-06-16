<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'classe')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseClasse', 'extended' => 'Classe'])]
abstract class BaseClasse
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label_masculin', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $label_masculin = null;

    #[Column(name: 'label_feminin', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $label_feminin = null;

    #[Column(name: 'description', type: Types::STRING, length: 450, nullable: true)]
    protected ?string $description = null;

    #[Column(name: 'image_m', type: Types::STRING, length: 90, nullable: true)]
    protected ?string $image_m = null;

    #[Column(name: 'image_f', type: Types::STRING, length: 90, nullable: true)]
    protected ?string $image_f = null;

    #[Column(name: 'creation', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $creation = false;

    /**
     * @var Collection<int, GroupeClasse>|GroupeClasse[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: GroupeClasse::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: false)]
    protected Collection $groupeClasses;

    /**
     * @var Collection<int, Personnage>|Personnage[]
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: Personnage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: false)]
    protected Collection $personnages;

    /**
     * @var Collection<int, PersonnageSecondaire>
     */
    #[OneToMany(mappedBy: 'classe', targetEntity: PersonnageSecondaire::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'classe_id', nullable: false)]
    protected Collection $personnageSecondaires;

    #[JoinTable(name: 'classe_competence_family_favorite')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    #[ORM\OrderBy(['label' => 'ASC', 'id' => 'ASC'])]
    protected Collection $competenceFamilyFavorites;

    #[JoinTable(name: 'classe_competence_family_normale')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    #[ORM\OrderBy(['label' => 'ASC', 'id' => 'ASC'])]
    protected Collection $competenceFamilyNormales;

    #[JoinTable(name: 'classe_competence_family_creation')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: CompetenceFamily::class)]
    #[ORM\OrderBy(['label' => 'ASC', 'id' => 'ASC'])]
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

    public function addCompetenceFamilyCreation(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseCreation($this);
        $this->competenceFamilyCreations[] = $competenceFamily;

        return $this;
    }

    public function addCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseFavorite($this);
        $this->competenceFamilyFavorites[] = $competenceFamily;

        return $this;
    }

    public function addCompetenceFamilyNormale(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->addClasseNormale($this);
        $this->competenceFamilyNormales[] = $competenceFamily;

        return $this;
    }

    public function addGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses[] = $groupeClasse;

        return $this;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function addPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): static
    {
        $this->personnageSecondaires[] = $personnageSecondaire;

        return $this;
    }

    public function getCompetenceFamilyCreations(): Collection
    {
        return $this->competenceFamilyCreations;
    }

    public function getCompetenceFamilyFavorites(): Collection
    {
        return $this->competenceFamilyFavorites;
    }

    public function getCompetenceFamilyNormales(): Collection
    {
        return $this->competenceFamilyNormales;
    }

    public function getCreation(): bool
    {
        return $this->creation;
    }

    public function setCreation(string $creation): static
    {
        $this->creation = $creation;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getGroupeClasses(): Collection
    {
        return $this->groupeClasses;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getImageF(): string
    {
        return $this->image_f ?? '';
    }

    public function setImageF(string $image_f): static
    {
        $this->image_f = $image_f;

        return $this;
    }

    public function getImageM(): string
    {
        return $this->image_m ?? '';
    }

    public function setImageM(string $image_m): static
    {
        $this->image_m = $image_m;

        return $this;
    }

    public function getLabelFeminin(): string
    {
        return $this->label_feminin;
    }

    public function setLabelFeminin(string $label_feminin): static
    {
        $this->label_feminin = $label_feminin;

        return $this;
    }

    public function getLabelMasculin(): string
    {
        return $this->label_masculin;
    }

    public function setLabelMasculin(string $label_masculin): static
    {
        $this->label_masculin = $label_masculin;

        return $this;
    }

    public function getPersonnageSecondaires(): Collection
    {
        return $this->personnageSecondaires;
    }

    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function removeCompetenceFamilyCreation(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseCreation($this);
        $this->competenceFamilyCreations->removeElement($competenceFamily);

        return $this;
    }

    public function removeCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseFavorite($this);
        $this->competenceFamilyFavorites->removeElement($competenceFamily);

        return $this;
    }

    public function removeCompetenceFamilyNormale(CompetenceFamily $competenceFamily): static
    {
        $competenceFamily->removeClasseNormale($this);
        $this->competenceFamilyNormales->removeElement($competenceFamily);

        return $this;
    }

    public function removeGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses->removeElement($groupeClasse);

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function removePersonnageSecondaire(PersonnageSecondaire $personnageSecondaire): static
    {
        $this->personnageSecondaires->removeElement($personnageSecondaire);

        return $this;
    }
}
