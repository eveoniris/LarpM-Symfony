<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * App\Entity\Rumeur.
 *
 * @Table(name="rumeur", indexes={@Index(name="fk_rumeur_territoire1_idx", columns={"territoire_id"}), @Index(name="fk_rumeur_User1_idx", columns={"User_id"}), @Index(name="fk_rumeur_gn1_idx", columns={"gn_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseRumeur", "extended":"Rumeur"})
 */
class BaseRumeur
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`text`", type="text")
     */
    protected $text;

    /**
     * @Column(type="datetime")
     */
    protected $creation_date;

    /**
     * @Column(type="datetime")
     */
    protected $update_date;

    /**
     * @Column(type="string", length=45)
     */
    protected $visibility;

    /**
     * @ManyToOne(targetEntity="Gn", inversedBy="rumeurs")
     *
     * @JoinColumn(name="gn_id", referencedColumnName="id", nullable=false)
     */
    protected $gn;

    /**
     * @ManyToOne(targetEntity="Territoire", inversedBy="rumeurs")
     *
     * @JoinColumn(name="territoire_id", referencedColumnName="id")
     */
    protected $territoire;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'rumeurs')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Rumeur
     */
    public function setId($id)
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
     * @return \App\Entity\Rumeur
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
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return \App\Entity\Rumeur
     */
    public function setCreationDate($creation_date)
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
     *
     * @param \DateTime $update_date
     *
     * @return \App\Entity\Rumeur
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get the value of update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set the value of visibility.
     *
     * @param string $visibility
     *
     * @return \App\Entity\Rumeur
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get the value of visibility.
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set Gn entity (many to one).
     *
     * @return \App\Entity\Rumeur
     */
    public function setGn(Gn $gn = null)
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     *
     * @return \App\Entity\Gn
     */
    public function getGn()
    {
        return $this->gn;
    }

    /**
     * Set Territoire entity (many to one).
     *
     * @return \App\Entity\Rumeur
     */
    public function setTerritoire(Territoire $territoire = null)
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (many to one).
     *
     * @return \App\Entity\Territoire
     */
    public function getTerritoire()
    {
        return $this->territoire;
    }

    /**
     * Set User entity (many to one).
     *
     * @return \App\Entity\Rumeur
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
        return $this->user;
    }

    public function __sleep()
    {
        return ['id', 'text', 'gn_id', 'territoire_id', 'User_id', 'creation_date', 'update_date', 'visibility'];
    }
}
