<?php

namespace App\Entity;

use App\Repository\PersonnageApprentissageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: PersonnageApprentissageRepository::class)]
class PersonnageApprentissage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'apprentissages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnage $personnage = null;

    /**
     * @var Collection<int, Competence>
     */
    #[ORM\OneToMany(mappedBy: 'personnageApprentissage', targetEntity: Competence::class)]
    private Collection $competences;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $date_enseignement = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_usage = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'apprentissageEnseignants')]
    #[JoinColumn(nullable: false)]
    private ?Personnage $enseignant = null;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
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

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * @return Collection<int, Competence>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competence $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->setPersonnageApprentissage($this);
        }

        return $this;
    }

    public function removeCompetence(Competence $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            // set the owning side to null (unless already changed)
            if ($competence->getPersonnageApprentissage() === $this) {
                $competence->setPersonnageApprentissage(null);
            }
        }

        return $this;
    }

    public function getDateEnseignement(): ?int
    {
        return $this->date_enseignement;
    }

    public function setDateEnseignement(?int $date_enseignement): static
    {
        $this->date_enseignement = $date_enseignement;

        return $this;
    }

    public function getCreaytedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->created_at = $createdAt;

        return $this;
    }


    public function getDateUsage(): ?\DateTimeInterface
    {
        return $this->date_usage;
    }

    public function setDateUsage(?\DateTimeInterface $date_usage): static
    {
        $this->date_usage = $date_usage;

        return $this;
    }

    public function getEnseignant(): ?Personnage
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Personnage $enseignant): static
    {
        $this->enseignant = $enseignant;

        return $this;
    }

    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'enseignant_id' => $this->enseignant->getId(),
        ];
    }
}
