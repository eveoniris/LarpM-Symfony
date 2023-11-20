<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\SecondaryGroupType.
 *
 * @Table(name="secondary_group_type")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseSecondaryGroupType", "extended":"SecondaryGroupType"})
 */
class BaseSecondaryGroupType
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;
    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="SecondaryGroup", mappedBy="secondaryGroupType")
     *
     * @JoinColumn(name="id", referencedColumnName="secondary_group_type_id", nullable=false)
     */
    protected $secondaryGroups;

    public function __construct()
    {
        $this->secondaryGroups = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\SecondaryGroupType
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\SecondaryGroupType
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\SecondaryGroupType
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Add SecondaryGroup entity to collection (one to many).
     *
     * @return \App\Entity\SecondaryGroupType
     */
    public function addSecondaryGroup(SecondaryGroup $secondaryGroup)
    {
        $this->secondaryGroups[] = $secondaryGroup;

        return $this;
    }

    /**
     * Remove SecondaryGroup entity from collection (one to many).
     *
     * @return \App\Entity\SecondaryGroupType
     */
    public function removeSecondaryGroup(SecondaryGroup $secondaryGroup)
    {
        $this->secondaryGroups->removeElement($secondaryGroup);

        return $this;
    }

    /**
     * Get SecondaryGroup entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecondaryGroups()
    {
        return $this->secondaryGroups;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description'];
    }
}
