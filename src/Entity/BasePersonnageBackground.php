<?php

namespace App\Entity;

/**
 * App\Entity\PersonnageBackground.
 *
 * @Table(name="personnage_background", indexes={@Index(name="fk_personnage_background_personnage1_idx", columns={"personnage_id"}), @Index(name="fk_personnage_background_User1_idx", columns={"User_id"}), @Index(name="fk_personnage_background_gn1_idx", columns={"gn_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageBackground", "extended":"PersonnageBackground"})
 */
class BasePersonnageBackground
{
    /**
     * @Id
     *
     * @Column(type="integer")
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(name="`text`", type="text", nullable=true)
     */
    protected $text;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $visibility;

    /**
     * @Column(type="datetime")
     */
    protected $creation_date;

    /**
     * @Column(type="datetime")
     */
    protected $update_date;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageBackgrounds")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="personnageBackgrounds")
     *
     * @JoinColumn(name="User_id", referencedColumnName="id", nullable=false)
     */
    protected $User;

    /**
     * @ManyToOne(targetEntity="Gn", inversedBy="personnageBackgrounds")
     *
     * @JoinColumn(name="gn_id", referencedColumnName="id", nullable=false)
     */
    protected $gn;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageBackground
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
     * @return \App\Entity\PersonnageBackground
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
     * Set the value of visibility.
     *
     * @param string $visibility
     *
     * @return \App\Entity\PersonnageBackground
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
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return \App\Entity\PersonnageBackground
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
     * @return \App\Entity\PersonnageBackground
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
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageBackground
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
     * Set User entity (many to one).
     *
     * @return \App\Entity\PersonnageBackground
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

    /**
     * Set Gn entity (many to one).
     *
     * @return \App\Entity\PersonnageBackground
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

    public function __sleep()
    {
        return ['id', 'personnage_id', 'text', 'visibility', 'creation_date', 'update_date', 'User_id', 'gn_id'];
    }
}