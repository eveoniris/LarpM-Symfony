<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'ressource')]
#[Index(columns: ['rarete_id'], name: 'fk_ressource_rarete1_idx')]
#[InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRessource', 'extended' => 'Ressource'])]
class BaseRessource
{
    #[Id]
    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    protected ?string $label = null;

    /**
     * @var Collection<GroupeHasRessource>
     */
    #[OneToMany(mappedBy: 'ressource', targetEntity: 'GroupeHasRessource')]
    #[JoinColumn(name: 'id', referencedColumnName: 'ressource_id', nullable: false)]
    protected Collection $groupeHasRessources;

    /**
     * @var Collection<PersonnageRessource>
     */
    #[OneToMany(mappedBy: 'ressource', targetEntity: 'PersonnageRessource')]
    #[JoinColumn(name: 'id', referencedColumnName: 'ressource_id', nullable: false)]
    protected Collection $personnageRessources;

    /**
     * @var Collection<TechnologiesRessources>
     */
    #[OneToMany(mappedBy: 'ressource', targetEntity: 'TechnologiesRessources')]
    #[JoinColumn(name: 'id', referencedColumnName: 'ressource_id', nullable: false)]
    protected Collection $technologiesRessources;

    #[ManyToOne(targetEntity: 'Rarete', inversedBy: 'ressources')]
    #[JoinColumn(name: 'rarete_id', referencedColumnName: 'id', nullable: false)]
    protected Rarete $rarete;

    /**
     * @var Collection<Territoire>
     */
    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'exportations')]
    protected Collection $exportateurs;

    /**
     * @var Collection<Territoire>
     */
    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'importations')]
    protected Collection $importateurs;

    public function __construct()
    {
        $this->groupeHasRessources = new ArrayCollection();
        $this->personnageRessources = new ArrayCollection();
        $this->technologiesRessources = new ArrayCollection();
        $this->exportateurs = new ArrayCollection();
        $this->importateurs = new ArrayCollection();
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
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
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
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Add GroupeHasRessource entity to collection (one to many).
     */
    public function addGroupeHasRessource(GroupeHasRessource $groupeHasRessource): static
    {
        $this->groupeHasRessources[] = $groupeHasRessource;

        return $this;
    }

    /**
     * Remove GroupeHasRessource entity from collection (one to many).
     */
    public function removeGroupeHasRessource(GroupeHasRessource $groupeHasRessource): static
    {
        $this->groupeHasRessources->removeElement($groupeHasRessource);

        return $this;
    }

    /**
     * Get GroupeHasRessource entity collection (one to many).
     */
    public function getGroupeHasRessources(): Collection
    {
        return $this->groupeHasRessources;
    }

    /**
     * Add PersonnageRessource entity to collection (one to many).
     */
    public function addPersonnageRessource(PersonnageRessource $personnageRessource): static
    {
        $this->personnageRessources[] = $personnageRessource;

        return $this;
    }

    /**
     * Remove PersonnageRessource entity from collection (one to many).
     */
    public function removePersonnageRessource(PersonnageRessource $personnageRessource): static
    {
        $this->personnageRessources->removeElement($personnageRessource);

        return $this;
    }

    /**
     * Get PersonnageRessource entity collection (one to many).
     */
    public function getPersonnageRessources(): Collection
    {
        return $this->personnageRessources;
    }

    /**
     * Add TechnologiesRessources entity to collection (one to many).
     */
    public function addTechnologieRessource(TechnologiesRessources $technologieRessource): static
    {
        $this->technologiesRessources[] = $technologieRessource;

        return $this;
    }

    /**
     * Remove TechnologiesRessources entity from collection (one to many).
     */
    public function removeTechnologieRessource(TechnologiesRessources $technologieRessource): static
    {
        $this->technologiesRessources->removeElement($technologieRessource);

        return $this;
    }

    /**
     * Get TechnologiesRessources entity collection (one to many).
     */
    public function getTechnologiesRessources(): Collection
    {
        return $this->technologiesRessources;
    }

    /**
     * Set Rarete entity (many to one).
     */
    public function setRarete(Rarete $rarete = null): static
    {
        $this->rarete = $rarete;

        return $this;
    }

    /**
     * Get Rarete entity (many to one).
     */
    public function getRarete(): Rarete
    {
        return $this->rarete;
    }

    public function getExportateurs(): Collection
    {
        return $this->exportateurs;
    }

    public function getImportateurs(): Collection
    {
        return $this->importateurs;
    }

    public function addImportateur($territoire): static
    {
        $this->importateurs[] = $territoire;

        return $this;
    }

    public function removeImportateur($territoire): static
    {
        $this->importateurs->removeElement($territoire);

        return $this;
    }

    public function addExportateur($territoire): static
    {
        $this->exportateurs[] = $territoire;

        return $this;
    }

    public function removeExportateur($territoire): static
    {
        $this->exportateurs->removeElement($territoire);

        return $this;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'rarete_id'];
    } */
}
