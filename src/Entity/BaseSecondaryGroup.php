<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'secondary_group')]
#[ORM\Index(columns: ['secondary_group_type_id'], name: 'fk_secondary_groupe_secondary_group_type1_idx')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_secondary_group_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseSecondaryGroup', 'extended' => 'SecondaryGroup'])]
abstract class BaseSecondaryGroup
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description_secrete = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $secret = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $materiel = null;

    #[OneToMany(mappedBy: 'secondaryGroup', targetEntity: IntrigueHasGroupeSecondaire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'secondary_group_id', nullable: false)]
    protected Collection $intrigueHasGroupeSecondaires;

    #[OneToMany(mappedBy: 'secondaryGroup', targetEntity: Membre::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'secondary_group_id', nullable: false)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    protected Collection $membres;

    #[OneToMany(mappedBy: 'secondaryGroup', targetEntity: Postulant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'secondary_group_id', nullable: false)]
    protected Collection $postulants;

    #[ManyToOne(targetEntity: SecondaryGroupType::class, fetch: 'EAGER', inversedBy: 'secondaryGroups')]
    #[JoinColumn(name: 'secondary_group_type_id', referencedColumnName: 'id', nullable: false)]
    protected SecondaryGroupType $secondaryGroupType;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'secondaryGroups')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected ?Personnage $personnage = null;

    #[Column(length: 255, nullable: true)]
    private ?string $discord = null;

    #[Column(nullable: true)]
    private ?bool $private = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'secondaryGroups')]
    #[JoinColumn(name: 'scenariste_id', referencedColumnName: 'id', options: ['unsigned' => true])]
    private ?User $scenariste = null;

    #[ORM\Column(nullable: true)]
    private ?bool $show_discord = null;

    public function __construct()
    {
        $this->intrigueHasGroupeSecondaires = new ArrayCollection();
        $this->membres = new ArrayCollection();
        $this->postulants = new ArrayCollection();
    }

    /**
     * Add IntrigueHasGroupeSecondaire entity to collection (one to many).
     */
    public function addIntrigueHasGroupeSecondaire(IntrigueHasGroupeSecondaire $intrigueHasGroupeSecondaire): static
    {
        $this->intrigueHasGroupeSecondaires[] = $intrigueHasGroupeSecondaire;

        return $this;
    }

    /**
     * Add Membre entity to collection (one to many).
     */
    public function addMembre(Membre $membre): static
    {
        $this->membres[] = $membre;

        return $this;
    }

    /**
     * Add Postulant entity to collection (one to many).
     */
    public function addPostulant(Postulant $postulant): static
    {
        $this->postulants[] = $postulant;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description_secrete.
     */
    public function getDescriptionSecrete(): string
    {
        return $this->description_secrete ?? '';
    }

    /**
     * Set the value of description_secrete.
     */
    public function setDescriptionSecrete(string $description_secrete): static
    {
        $this->description_secrete = $description_secrete;

        return $this;
    }

    public function getDiscord(): ?string
    {
        return $this->discord;
    }

    public function setDiscord(?string $discord): static
    {
        $this->discord = $discord;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get IntrigueHasGroupeSecondaire entity collection (one to many).
     */
    public function getIntrigueHasGroupeSecondaires(): Collection
    {
        return $this->intrigueHasGroupeSecondaires;
    }

    /**
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of materiel.
     */
    public function getMateriel(): string
    {
        return $this->materiel ?? '';
    }

    /**
     * Set the value of materiel.
     */
    public function setMateriel(string $materiel): static
    {
        $this->materiel = $materiel;

        return $this;
    }

    /**
     * Get Membre entity collection (one to many).
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Postulant entity collection (one to many).
     */
    public function getPostulants(): Collection
    {
        return $this->postulants;
    }

    public function getScenariste(): ?User
    {
        return $this->scenariste;
    }

    public function setScenariste(?User $scenariste): static
    {
        $this->scenariste = $scenariste;

        return $this;
    }

    /**
     * Get SecondaryGroupType entity (many to one).
     */
    public function getSecondaryGroupType(): SecondaryGroupType
    {
        return $this->secondaryGroupType;
    }

    /**
     * Set SecondaryGroupType entity (many to one).
     */
    public function setSecondaryGroupType(?SecondaryGroupType $secondaryGroupType = null): static
    {
        $this->secondaryGroupType = $secondaryGroupType;

        return $this;
    }

    /**
     * Get the value of secret.
     */
    public function getSecret(): bool
    {
        return $this->secret ?? false;
    }

    /**
     * Set the value of secret.
     */
    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->private;
    }

    public function isSecret(): ?bool
    {
        return (bool) $this->secret;
    }

    public function isShowDiscord(): ?bool
    {
        return $this->show_discord;
    }

    /**
     * Remove IntrigueHasGroupeSecondaire entity from collection (one to many).
     */
    public function removeIntrigueHasGroupeSecondaire(IntrigueHasGroupeSecondaire $intrigueHasGroupeSecondaire): static
    {
        $this->intrigueHasGroupeSecondaires->removeElement($intrigueHasGroupeSecondaire);

        return $this;
    }

    /**
     * Remove Membre entity from collection (one to many).
     */
    public function removeMembre(Membre $membre): static
    {
        $this->membres->removeElement($membre);

        return $this;
    }

    /**
     * Remove Postulant entity from collection (one to many).
     */
    public function removePostulant(Postulant $postulant): static
    {
        $this->postulants->removeElement($postulant);

        return $this;
    }

    public function setPrivate(?bool $private): static
    {
        $this->private = $private;

        return $this;
    }

    public function setShowDiscord(?bool $show_discord): static
    {
        $this->show_discord = $show_discord;

        return $this;
    }
}
