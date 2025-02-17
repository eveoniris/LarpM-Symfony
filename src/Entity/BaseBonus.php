<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity]
#[ORM\Table(name: 'bonus')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_personnage_groupe1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseBonus', 'extended' => 'Bonus'])]
abstract class BaseBonus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $valeur = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $periode = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $application = null;

    #[ORM\Column(nullable: true)]
    private ?array $json_data = null;

    #[ORM\OneToOne(targetEntity: Competence::class, cascade: ['persist'])]
    protected Competence $competence;

    /**
     * @var Collection<int, Territoire>
     */
    #[ORM\ManyToMany(targetEntity: Territoire::class, inversedBy: 'origineBonus', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'origine_bonus')]
    #[JoinColumn(name: 'bonus_id', referencedColumnName: 'id', nullable: false)]
    private Collection $origines;

    /**
     * @return Collection<int, Territoire>
     */
    public function getOrigines(): Collection
    {
        return $this->origines;
    }

    public function addOrigine(Territoire $origine): static
    {
        if (!$this->origines->contains($origine)) {
            $this->origines->add($origine);
        }

        return $this;
    }

    public function removeOrigine(Territoire $origine): static
    {
        $this->origines->removeElement($origine);

        return $this;
    }

    public function __construct()
    {
        $this->origines = new ArrayCollection();
    }

    public function getCompetence(): ?Competence
    {
        return $this->competence ?? null;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getValeur(): ?int
    {
        return $this->valeur;
    }

    public function setValeur(?int $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(?string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getApplication(): ?string
    {
        return $this->application;
    }

    public function setApplication(?string $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getJsonData(): ?array
    {
        return $this->json_data;
    }

    public function setJsonData(?array $json_data): static
    {
        $this->json_data = $json_data;

        return $this;
    }
}
