<?php

namespace App\Entity;

use App\Repository\PersonnageApprentissageRepository;
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
    #[JoinColumn(nullable: false)]
    private ?Personnage $personnage = null;

    #[ORM\ManyToOne(targetEntity: Competence::class, inversedBy: 'personnageApprentissage')]
    #[JoinColumn(nullable: false)]
    private Competence $competence;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $date_enseignement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_usage = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'apprentissageEnseignants')]
    #[JoinColumn(nullable: false)]
    private ?Personnage $enseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deleted_at = null;

    public function getCompetence(): ?Competence
    {
        return $this->competence;
    }

    public function setCompetence(?Competence $competence): static
    {
        $this->competence = $competence;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    /** Annee en jeu */
    public function getDateEnseignement(): ?int
    {
        return $this->date_enseignement;
    }

    public function setDateEnseignement(?int $date_enseignement): static
    {
        $this->date_enseignement = $date_enseignement;

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

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getDescription(): string
    {
        return sprintf(
            '%s enseigné par %s pour %s le %s%s',
            $this->getCompetence()?->getLabel(),
            $this->getEnseignant()?->getIdName(),
            $this->getPersonnage()?->getIdName(),
            $this->getDateEnseignement(),
            !$this->getDateUsage() ?: '. Utilisé le '.$this->getDateUsage()->format('d/m/Y')
        );
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->created_at = $createdAt;

        return $this;
    }

    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'enseignant_id' => $this->enseignant->getId(),
        ];
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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }
}
