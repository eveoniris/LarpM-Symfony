<?php

namespace App\Entity;

use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity]
#[ORM\Table(name: 'bonus')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_competence_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseBonus', 'extended' => 'Bonus'])]
abstract class BaseBonus
{
    #[ORM\OneToOne(targetEntity: Competence::class, cascade: ['persist'])]
    protected Competence $competence;
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
    /**
     * @var Collection<int, Territoire>
     */
    #[ORM\OneToMany(mappedBy: 'bonus', targetEntity: OrigineBonus::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'origine_bonus')]
    #[JoinColumn(name: 'bonus_id', referencedColumnName: 'id', nullable: false)]
    private Collection $originesBonus;

    /**
     * @var Collection<int, Personnage>|null
     */
    #[ORM\OneToMany(mappedBy: 'bonus', targetEntity: PersonnageBonus::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'personnage_bonus')]
    #[JoinColumn(name: 'bonus_id', referencedColumnName: 'id', nullable: false)]
    private ?Collection $personnageBonus;

    /**
     * @var Collection<int, GroupeBonus>|null
     */
    #[ORM\OneToMany(mappedBy: 'bonus', targetEntity: GroupeBonus::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'groupe_bonus')]
    #[JoinColumn(name: 'bonus_id', referencedColumnName: 'id', nullable: false)]
    private ?Collection $groupeBonus;

    /**
     * @var Collection<int, Merveille>
     */
    #[ORM\OneToMany(mappedBy: 'bonus', targetEntity: Merveille::class)]
    private Collection $merveilles;

    public function __construct()
    {
        $this->personnageBonus = new ArrayCollection();
        $this->groupeBonus = new ArrayCollection();
        $this->originesBonus = new ArrayCollection();
        $this->merveilles = new ArrayCollection();
    }

    public function addGroupeBonus(GroupeBonus $groupeBonus): static
    {
        if (!$this->groupeBonus->contains($groupeBonus)) {
            $this->groupeBonus->add($groupeBonus);
            $groupeBonus->setBonus($this);
        }

        return $this;
    }

    public function addMerveille(Merveille $merveille): static
    {
        if (!$this->merveilles->contains($merveille)) {
            $this->merveilles->add($merveille);
            $merveille->setBonus($this);
        }

        return $this;
    }


    public function addOrigineBonus(OrigineBonus $origine): static
    {
        if (!$this->originesBonus->contains($origine)) {
            $this->originesBonus->add($origine);
            $origine->setBonus($this);
        }

        return $this;
    }

    public function addPersonnageBonus(PersonnageBonus $personnageBonus): static
    {
        if (!$this->personnageBonus->contains($personnageBonus)) {
            $this->personnageBonus->add($personnageBonus);
            $personnageBonus->setBonus($this);
        }

        return $this;
    }

    public function getApplication(): ?BonusApplication
    {
        return BonusApplication::tryFrom($this->application);
    }

    public function setApplication(string|BonusApplication|null $application): static
    {
        if ($application instanceof BonusApplication) {
            $this->application = $application->value;

            return $this;
        }

        $this->application = $application;

        return $this;
    }

    public function getCompetence(): ?Competence
    {
        return $this->competence ?? null;
    }

    public function setCompetence(?Competence $competence): static
    {
        $this->competence = $competence;

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

    /**
     * @return Collection<int, GroupeBonus>
     */
    public function getGroupeBonus(): Collection
    {
        return $this->groupeBonus;
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

    public function getJsonData(): ?array
    {
        return $this->json_data;
    }

    public function setJsonData(?array $json_data): static
    {
        $this->json_data = $json_data;

        return $this;
    }

    /**
     * @return Collection<int, Merveille>
     */
    public function getMerveilles(): Collection
    {
        return $this->merveilles;
    }

    /**
     * @return Collection<int, Territoire>
     */
    public function getOrigineBonus(): Collection
    {
        return $this->originesBonus;
    }

    public function getPeriode(): ?BonusPeriode
    {
        return $this->periode ? BonusPeriode::tryFrom($this->periode) : null;
    }

    public function setPeriode(string|BonusPeriode|null $periode): static
    {
        if ($periode instanceof BonusPeriode) {
            $this->periode = $periode->value;

            return $this;
        }

        $this->periode = $periode;

        return $this;
    }

    /**
     * @return Collection<int, PersonnageBonus>
     */
    public function getPersonnageBonus(): Collection
    {
        return $this->personnageBonus;
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

    public function getType(): ?BonusType
    {
        return BonusType::tryFrom($this->type);
    }

    public function setType(string|BonusType|null $type): static
    {
        if ($type instanceof BonusType) {
            $this->type = $type->value;

            return $this;
        }

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

    public function removeGroupeBonus(GroupeBonus $groupeBonus): static
    {
        if ($this->groupeBonus->removeElement($groupeBonus)) {
            // set the owning side to null (unless already changed)
            if ($groupeBonus->getGroupe() === $this) {
                $groupeBonus->setBonus(null);
            }
        }

        return $this;
    }

    public function removeMerveille(Merveille $merveille): static
    {
        if ($this->merveilles->removeElement($merveille)) {
            // set the owning side to null (unless already changed)
            if ($merveille->getBonus() === $this) {
                $merveille->setBonus(null);
            }
        }

        return $this;
    }

    public function removeOrigineBonus(OrigineBonus $origine): static
    {
        $this->originesBonus->removeElement($origine);
        if ($origine->getBonus() === $this) {
            $origine->setBonus(null);
        }

        return $this;
    }

    public function removePersonnageBonus(PersonnageBonus $personnageBonus): static
    {
        // set the owning side to null (unless already changed)
        if ($this->groupeBonus->removeElement($personnageBonus) && $personnageBonus->getPersonnage() === $this) {
            $personnageBonus->setBonus(null);
        }

        return $this;
    }
}
