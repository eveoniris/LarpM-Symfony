<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[ORM\Table(name: 'relecture')]
#[ORM\Index(columns: ['etat_civil_id'], name: 'fk_user_etat_civil1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_relecture_User1_idx')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_relecture_intrigue1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRelecture', 'extended' => 'Relecture'])]
class BaseRelecture
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`date`", type="datetime")
     */
    protected $date;

    /**
     * @Column(type="string", length=45)
     */
    protected $statut;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $remarque;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'relectures')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    /**
     * @ManyToOne(targetEntity="Intrigue", inversedBy="relectures", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="intrigue_id", referencedColumnName="id", nullable=false)
     */
    protected $intrigue;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Relecture
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
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\Relecture
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
     * Set the value of statut.
     *
     * @param string $statut
     *
     * @return \App\Entity\Relecture
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get the value of statut.
     *
     * @return string
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set the value of remarque.
     *
     * @param string $remarque
     *
     * @return \App\Entity\Relecture
     */
    public function setRemarque($remarque)
    {
        $this->remarque = $remarque;

        return $this;
    }

    /**
     * Get the value of remarque.
     *
     * @return string
     */
    public function getRemarque()
    {
        return $this->remarque;
    }

    /**
     * Set User entity (many to one).
     *
     * @return \App\Entity\Relecture
     */
    public function setUser(User $User = null)
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set Intrigue entity (many to one).
     *
     * @return \App\Entity\Relecture
     */
    public function setIntrigue(Intrigue $intrigue = null)
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Intrigue entity (many to one).
     *
     * @return \App\Entity\Intrigue
     */
    public function getIntrigue()
    {
        return $this->intrigue;
    }

    public function __sleep()
    {
        return ['id', 'date', 'statut', 'remarque', 'User_id', 'intrigue_id'];
    }
}
