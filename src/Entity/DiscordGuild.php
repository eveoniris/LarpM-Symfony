<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DiscordRoleType;
use App\Repository\DiscordGuildRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Serveur Discord (guilde) où le bot attribue des rôles.
 */
#[ORM\Entity(repositoryClass: DiscordGuildRepository::class)]
#[ORM\Table(name: 'discord_guild')]
class DiscordGuild
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /** Identifiant Discord de la guilde (snowflake). */
    #[ORM\Column(type: Types::STRING, length: 32, unique: true)]
    private string $guildId = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $nom = '';

    /** @var Collection<int, DiscordRoleMapping> */
    #[ORM\OneToMany(mappedBy: 'guild', targetEntity: DiscordRoleMapping::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $mappings;

    public function __construct()
    {
        $this->mappings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuildId(): string
    {
        return $this->guildId;
    }

    public function setGuildId(string $guildId): static
    {
        $this->guildId = $guildId;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /** @return Collection<int, DiscordRoleMapping> */
    public function getMappings(): Collection
    {
        return $this->mappings;
    }

    /** ID du rôle Discord général (membre ou orga) de la guilde, chaîne vide s'il n'est pas configuré. */
    public function roleIdOf(DiscordRoleType $type): string
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping->getType() === $type) {
                return $mapping->getRoleId();
            }
        }

        return '';
    }

    /** @return array<int, DiscordRoleMapping> correspondances des groupes transverses, indexées par id de groupe */
    public function groupMappings(): array
    {
        $byGroup = [];
        foreach ($this->mappings as $mapping) {
            $groupId = $mapping->getSecondaryGroup()?->getId();
            if (DiscordRoleType::GROUPE_SECONDAIRE === $mapping->getType() && null !== $groupId) {
                $byGroup[$groupId] = $mapping;
            }
        }

        return $byGroup;
    }

    public function addMapping(DiscordRoleMapping $mapping): static
    {
        if (!$this->mappings->contains($mapping)) {
            $this->mappings->add($mapping);
        }

        return $this;
    }

    public function removeMapping(DiscordRoleMapping $mapping): static
    {
        $this->mappings->removeElement($mapping);

        return $this;
    }
}
