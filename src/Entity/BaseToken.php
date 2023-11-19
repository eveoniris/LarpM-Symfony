<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Token.
 *
 * @Table(name="token")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseToken", "extended":"Token"})
 */
class BaseToken
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
     * @Column(type="string", length=45)
     */
    protected $tag;

    /**
     * @OneToMany(targetEntity="PersonnageHasToken", mappedBy="token")
     *
     * @JoinColumn(name="id", referencedColumnName="token_id", nullable=false)
     */
    protected $personnageHasTokens;

    public function __construct()
    {
        $this->personnageHasTokens = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Token
     */
    public function setId($id): static
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
     * @return \App\Entity\Token
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
     * @return \App\Entity\Token
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of tag.
     *
     * @param string $tag
     *
     * @return \App\Entity\Token
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get the value of tag.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Add PersonnageHasToken entity to collection (one to many).
     *
     * @return \App\Entity\Token
     */
    public function addPersonnageHasToken(PersonnageHasToken $personnageHasToken)
    {
        $this->personnageHasTokens[] = $personnageHasToken;

        return $this;
    }

    /**
     * Remove PersonnageHasToken entity from collection (one to many).
     *
     * @return \App\Entity\Token
     */
    public function removePersonnageHasToken(PersonnageHasToken $personnageHasToken)
    {
        $this->personnageHasTokens->removeElement($personnageHasToken);

        return $this;
    }

    /**
     * Get PersonnageHasToken entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageHasTokens()
    {
        return $this->personnageHasTokens;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'tag'];
    }
}
