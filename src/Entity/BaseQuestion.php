<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Question.
 *
 * @Table(name="question", indexes={@Index(name="fk_question_User1_idx", columns={"User_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseQuestion", "extended":"Question"})
 */
class BaseQuestion
{
    /**
     * @Id
     *
     * @Column(type="integer", options={"unsigned":true})
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(name="`text`", type="text")
     */
    protected $text;

    /**
     * @Column(name="`date`", type="datetime")
     */
    protected $date;

    /**
     * @Column(type="text")
     */
    protected $choix;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @OneToMany(targetEntity="Reponse", mappedBy="question")
     *
     * @JoinColumn(name="id", referencedColumnName="question_id", nullable=false)
     */
    protected $reponses;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="questions")
     *
     * @JoinColumn(name="User_id", referencedColumnName="id", nullable=false)
     */
    protected $User;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Question
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of text.
     *
     * @param string $text
     *
     * @return \App\Entity\Question
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\Question
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of choix.
     *
     * @param string $choix
     *
     * @return \App\Entity\Question
     */
    public function setChoix($choix)
    {
        $this->choix = $choix;

        return $this;
    }

    /**
     * Get the value of choix.
     *
     * @return string
     */
    public function getChoix()
    {
        return $this->choix;
    }

    /**
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Question
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Add Reponse entity to collection (one to many).
     *
     * @return \App\Entity\Question
     */
    public function addReponse(Reponse $reponse)
    {
        $this->reponses[] = $reponse;

        return $this;
    }

    /**
     * Remove Reponse entity from collection (one to many).
     *
     * @return \App\Entity\Question
     */
    public function removeReponse(Reponse $reponse)
    {
        $this->reponses->removeElement($reponse);

        return $this;
    }

    /**
     * Get Reponse entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReponses()
    {
        return $this->reponses;
    }

    /**
     * Set User entity (many to one).
     *
     * @return \App\Entity\Question
     */
    public function setUser(User $User = null)
    {
        $this->User = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->User;
    }

    public function __sleep()
    {
        return ['id', 'text', 'date', 'User_id', 'choix', 'label'];
    }
}