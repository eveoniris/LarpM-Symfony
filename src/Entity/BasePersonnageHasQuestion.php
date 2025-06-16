<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_has_question')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_has_question_personnage1_idx')]
#[ORM\Index(columns: ['question_id'], name: 'fk_personnage_has_question_question1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageHasQuestion', 'extended' => 'PersonnageHasQuestion'])]
abstract class BasePersonnageHasQuestion
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $reponse;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageHasQuestions')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: Question::class, inversedBy: 'personnageHasQuestions')]
    #[JoinColumn(name: 'question_id', referencedColumnName: 'id', nullable: false)]
    protected Question $question;

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
     * Set the value of reponse.
     */
    public function setReponse(bool $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    /**
     * Get the value of reponse.
     */
    public function getReponse(): bool
    {
        return $this->reponse;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Question entity (many to one).
     */
    public function setQuestion(Question $question = null): static
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get Question entity (many to one).
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    /* public function __sleep()
    {
        return ['id', 'reponse', 'personnage_id', 'question_id'];
    } */
}
