<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'token')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseToken', 'extended' => 'Token'])]
abstract class BaseToken
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $tag = '';

    #[OneToMany(mappedBy: 'token', targetEntity: PersonnageHasToken::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'token_id', nullable: 'false')]
    protected ArrayCollection $personnageHasTokens;

    public function __construct()
    {
        $this->personnageHasTokens = new ArrayCollection();
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
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

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
     * Set the value of tag.
     */
    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get the value of tag.
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * Add PersonnageHasToken entity to collection (one to many).
     */
    public function addPersonnageHasToken(PersonnageHasToken $personnageHasToken): static
    {
        $this->personnageHasTokens[] = $personnageHasToken;

        return $this;
    }

    /**
     * Remove PersonnageHasToken entity from collection (one to many).
     */
    public function removePersonnageHasToken(PersonnageHasToken $personnageHasToken): static
    {
        $this->personnageHasTokens->removeElement($personnageHasToken);

        return $this;
    }

    /**
     * Get PersonnageHasToken entity collection (one to many).
     */
    public function getPersonnageHasTokens(): ArrayCollection
    {
        return $this->personnageHasTokens;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'tag'];
    }
}
