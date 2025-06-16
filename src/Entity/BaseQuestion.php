<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'question')]
#[ORM\Index(columns: ['user_id'], name: 'fk_question_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseQuestion', 'extended' => 'Question'])]
abstract class BaseQuestion
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'text', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $text;

    #[Column(name: 'date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $date;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $choix = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[OneToMany(mappedBy: 'question', targetEntity: Reponse::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'question_id', nullable: false)]
    protected Collection $reponses;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'questions')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    #[OneToMany(mappedBy: 'question', targetEntity: PersonnageHasQuestion::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'question_id', nullable: false)]
    protected Collection $personnageHasQuestions;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
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
     * Get the value of text.
     */
    public function getText(): string
    {
        return $this->text ?? '';
    }

    /**
     * Set the value of text.
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set the value of date.
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of choix.
     */
    public function getChoix(): string
    {
        return $this->choix;
    }

    /**
     * Set the value of choix.
     */
    public function setChoix(string $choix): static
    {
        $this->choix = $choix;

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
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Add Reponse entity to collection (one to many).
     */
    public function addReponse(Reponse $reponse): static
    {
        $this->reponses[] = $reponse;

        return $this;
    }

    /**
     * Remove Reponse entity from collection (one to many).
     */
    public function removeReponse(Reponse $reponse): static
    {
        $this->reponses->removeElement($reponse);

        return $this;
    }

    /**
     * Get Reponse entity collection (one to many).
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /* public function __sleep()
    {
        return ['id', 'text', 'date', 'User_id', 'choix', 'label'];
    } */

    public function getPersonnageHasQuestions(): Collection
    {
        return $this->personnageHasQuestions;
    }

    public function setPersonnageHasQuestions(Collection $personnageHasQuestions): static
    {
        $this->personnageHasQuestions = $personnageHasQuestions;

        return $this;
    }
}
