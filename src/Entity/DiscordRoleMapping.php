<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DiscordRoleType;
use App\Repository\DiscordRoleMappingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Correspondance entre un rôle LarpManager (membre, orga/scénariste, groupe transverse) et un rôle Discord d'une guilde.
 *
 * Pour un groupe transverse, la catégorie et le salon texte associés (créés par le provisionnement) sont mémorisés ici.
 * Les mappings « membre » et « orga » n'ont pas de groupe : leur unicité par guilde est garantie par DiscordMappingService.
 */
#[ORM\Entity(repositoryClass: DiscordRoleMappingRepository::class)]
#[ORM\Table(name: 'discord_role_mapping')]
#[ORM\UniqueConstraint(name: 'uniq_discord_mapping_guild_groupe', columns: ['guild_id', 'secondary_group_id'])]
class DiscordRoleMapping
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DiscordGuild::class, inversedBy: 'mappings')]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private DiscordGuild $guild;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: DiscordRoleType::class)]
    private DiscordRoleType $type;

    #[ORM\ManyToOne(targetEntity: SecondaryGroup::class)]
    #[ORM\JoinColumn(name: 'secondary_group_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?SecondaryGroup $secondaryGroup = null;

    /** Identifiant Discord du rôle (snowflake). */
    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $roleId = '';

    /** Numéro du groupe, qui est aussi le nom du rôle Discord (provisionnement). */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numero = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $categoryId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $channelId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuild(): DiscordGuild
    {
        return $this->guild;
    }

    public function setGuild(DiscordGuild $guild): static
    {
        $this->guild = $guild;

        return $this;
    }

    public function getType(): DiscordRoleType
    {
        return $this->type;
    }

    public function setType(DiscordRoleType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSecondaryGroup(): ?SecondaryGroup
    {
        return $this->secondaryGroup;
    }

    public function setSecondaryGroup(?SecondaryGroup $secondaryGroup): static
    {
        $this->secondaryGroup = $secondaryGroup;

        return $this;
    }

    public function getRoleId(): string
    {
        return $this->roleId;
    }

    public function setRoleId(string $roleId): static
    {
        $this->roleId = $roleId;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): static
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(?string $channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }
}
