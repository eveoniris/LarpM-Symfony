<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RulebookDomaineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Domaine du rulebook (xp, territoire, religion...). Exporté en un fichier markdown par domaine dans le cache local.
 */
#[ORM\Entity(repositoryClass: RulebookDomaineRepository::class)]
#[ORM\Table(name: 'rulebook_domaine')]
#[ORM\UniqueConstraint(name: 'uniq_rulebook_domaine_code', columns: ['code'])]
class RulebookDomaine
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /** Identifiant court et stable, sert de nom de fichier du cache (ex: "xp"). */
    #[ORM\Column(type: Types::STRING, length: 40)]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $titre;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    /** @var Collection<int, RulebookRegle> */
    #[ORM\OneToMany(mappedBy: 'domaine', targetEntity: RulebookRegle::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $regles;

    public function __construct()
    {
        $this->regles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /** @return Collection<int, RulebookRegle> */
    public function getRegles(): Collection
    {
        return $this->regles;
    }
}
