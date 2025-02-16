<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[ORM\Table(name: 'connaissance')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseConnaissance', 'extended' => 'Connaissance'])]
abstract class BaseConnaissance
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected string $label = '';

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $contraintes = null;

    #[Column(name: 'documentUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl;

    #[Column(type: Types::INTEGER, )]
    protected int $niveau;

    #[Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected bool $secret = false;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'connaissances')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
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

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
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

    public function setContraintes(string $contraintes): static
    {
        $this->contraintes = $contraintes;

        return $this;
    }

    public function getContraintes(): string
    {
        return $this->contraintes ?? '';
    }

    public function setDocumentUrl(string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    public function getDocumentUrl(): ?string
    {
        return $this->documentUrl;
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

    public function setNiveau(int $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getNiveau(): int
    {
        return $this->niveau;
    }

    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function getSecret(): bool
    {
        return $this->secret;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description', 'documentUrl', 'niveau', 'secret'];
    } */
}
