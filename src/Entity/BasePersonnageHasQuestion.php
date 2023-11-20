<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageHasQuestion.
 *
 * @Table(name="personnage_has_question", indexes={@Index(name="fk_personnage_has_question_personnage1_idx", columns={"personnage_id"}), @Index(name="fk_personnage_has_question_question1_idx", columns={"question_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageHasQuestion", "extended":"PersonnageHasQuestion"})
 */
class BasePersonnageHasQuestion
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="boolean")
     */
    protected $reponse;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageHasQuestions")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Question", inversedBy="personnageHasQuestions")
     *
     * @JoinColumn(name="question_id", referencedColumnName="id", nullable=false)
     */
    protected $question;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageHasQuestion
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
     * Set the value of reponse.
     *
     * @param bool $reponse
     *
     * @return \App\Entity\PersonnageHasQuestion
     */
    public function setReponse($reponse)
    {
        $this->reponse = $reponse;

        return $this;
    }

    /**
     * Get the value of reponse.
     *
     * @return bool
     */
    public function getReponse()
    {
        return $this->reponse;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageHasQuestion
     */
    public function setPersonnage(Personnage $personnage = null)
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getPersonnage()
    {
        return $this->personnage;
    }

    /**
     * Set Question entity (many to one).
     *
     * @return \App\Entity\PersonnageHasQuestion
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get Question entity (many to one).
     *
     * @return \App\Entity\Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    public function __sleep()
    {
        return ['id', 'reponse', 'personnage_id', 'question_id'];
    }
}
