<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use SensitiveParameter;

#[ORM\Entity]
#[ORM\Table(name: 'competence')]
#[ORM\Index(columns: ['competence_family_id'], name: 'fk_competence_niveau_competence1_idx')]
#[ORM\Index(columns: ['level_id'], name: 'fk_competence_niveau_niveau1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseCompetence', 'extended' => 'Competence'])]
class BaseCompetence
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(name: 'documentUrl', type: Types::TEXT, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $materiel = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $secret = null;

    /**
     * @var Collection<int, CompetenceAttribute>|CompetenceAttribute[]
     */
    #[OneToMany(mappedBy: 'competence', targetEntity: CompetenceAttribute::class, cascade: ['all'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_id', nullable: false)]
    protected Collection $competenceAttributes;

    /**
     * @var Collection<int, ExperienceUsage>|ExperienceUsage[]
     */
    #[OneToMany(mappedBy: 'competence', targetEntity: ExperienceUsage::class, cascade: ['all'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_id', nullable: false)]
    protected Collection $experienceUsages;

    /**
     * @var Collection<int, PersonnageSecondaireCompetence>|PersonnageSecondaireCompetence[]
     */
    #[OneToMany(mappedBy: 'competence', targetEntity: PersonnageSecondaireCompetence::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_id', nullable: false)]
    protected Collection $personnageSecondaireCompetences;

    /** @var Collection<int, PersonnageSecondairesCompetences> */
    #[OneToMany(mappedBy: 'competence', targetEntity: PersonnageSecondairesCompetences::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_id', nullable: false)]
    protected Collection $personnageSecondairesCompetences;

    /** @var Collection<int, PersonnageSecondairesSkills> */
    #[OneToMany(mappedBy: 'competence', targetEntity: PersonnageSecondairesSkills::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_id', nullable: false)]
    protected Collection $personnageSecondairesSkills;

    #[ManyToOne(targetEntity: CompetenceFamily::class, cascade: ['persist'], inversedBy: 'competences')]
    #[JoinColumn(name: 'competence_family_id', referencedColumnName: 'id', nullable: false)]
    #[OrderBy(['competence_family_label' => Criteria::ASC])]
    protected CompetenceFamily $competenceFamily;

    #[ManyToOne(targetEntity: Level::class, cascade: ['persist'], inversedBy: 'competences')]
    #[JoinColumn(name: 'level_id', referencedColumnName: 'id')]
    protected ?Level $level = null;

    /** @var Collection<int, Personnage> */
    #[ManyToMany(targetEntity: Personnage::class, inversedBy: 'competences')]
    #[ORM\JoinTable(name: 'personnages_competences')]
    #[JoinColumn(name: 'competence_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected Collection $personnages;

    /** @var Collection<int, PersonnageApprentissage> */
    #[OneToMany(mappedBy: 'competence', targetEntity: PersonnageApprentissage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'competence_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $personnageApprentissages;

    public function __construct()
    {
        $this->competenceAttributes = new ArrayCollection();
        $this->experienceUsages = new ArrayCollection();
        $this->personnageSecondaireCompetences = new ArrayCollection();
        $this->personnageSecondairesCompetences = new ArrayCollection();
        $this->personnageSecondairesSkills = new ArrayCollection();
        $this->personnages = new ArrayCollection();
    }

    public function addCompetenceAttribute(CompetenceAttribute $competenceAttribute): static
    {
        $this->competenceAttributes[] = $competenceAttribute;

        return $this;
    }

    public function addExperienceUsage(ExperienceUsage $experienceUsage): static
    {
        $this->experienceUsages[] = $experienceUsage;

        return $this;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        /* @phpstan-ignore argument.type */
        $personnage->addCompetence($this);
        $this->personnages[] = $personnage;

        return $this;
    }

    public function addPersonnageApprentissage(PersonnageApprentissage $personnageApprentissages): static
    {
        $this->personnageApprentissages[] = $personnageApprentissages;

        return $this;
    }

    public function addPersonnageSecondaireCompetence(PersonnageSecondaireCompetence $personnageSecondaireCompetence): static
    {
        $this->personnageSecondaireCompetences[] = $personnageSecondaireCompetence;

        return $this;
    }

    /** @return Collection<int, CompetenceAttribute> */
    public function getCompetenceAttributes(): Collection
    {
        return $this->competenceAttributes;
    }

    public function getCompetenceFamily(): ?CompetenceFamily
    {
        return $this->competenceFamily;
    }

    public function setCompetenceFamily(?CompetenceFamily $competenceFamily = null): static
    {
        $this->competenceFamily = $competenceFamily;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    public function setDocumentUrl(?string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /** @return Collection<int, ExperienceUsage> */
    public function getExperienceUsages(): Collection
    {
        return $this->experienceUsages;
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

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level = null): static
    {
        $this->level = $level;

        return $this;
    }

    public function getMateriel(): string
    {
        return $this->materiel ?? '';
    }

    public function setMateriel(?string $materiel): static
    {
        $this->materiel = $materiel ?? '';

        return $this;
    }

    /** @return Collection<int, PersonnageApprentissage> */
    public function getPersonnageApprentissages(): Collection
    {
        return $this->personnageApprentissages;
    }

    /** @return Collection<int, PersonnageSecondaireCompetence> */
    public function getPersonnageSecondaireCompetences(): Collection
    {
        return $this->personnageSecondaireCompetences;
    }

    /** @return Collection<int, Personnage> */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function isSecret(): ?bool
    {
        return $this->secret;
    }

    public function removeCompetenceAttribute(CompetenceAttribute $competenceAttribute): static
    {
        $this->competenceAttributes->removeElement($competenceAttribute);

        return $this;
    }

    public function removeExperienceUsage(ExperienceUsage $experienceUsage): static
    {
        $this->experienceUsages->removeElement($experienceUsage);

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        /* @phpstan-ignore argument.type */
        $personnage->removeCompetence($this);
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function removePersonnageApprentissage(PersonnageApprentissage $personnageApprentissage): static
    {
        $this->personnageApprentissages->removeElement($personnageApprentissage);

        return $this;
    }

    public function removePersonnageSecondaireCompetence(PersonnageSecondaireCompetence $personnageSecondaireCompetence): static
    {
        $this->personnageSecondaireCompetences->removeElement($personnageSecondaireCompetence);

        return $this;
    }

    public function setSecret(#[SensitiveParameter] ?bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /* public function __sleep()
     * {
     * return ['id', 'description', 'competence_family_id', 'level_id', 'documentUrl', 'materiel'];
     * } */
}
