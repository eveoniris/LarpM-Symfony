<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Restriction.
 *
 * @Table(name="restriction", indexes={@Index(name="fk_restriction_User1_idx", columns={"auteur_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseRestriction", "extended":"Restriction"})
 */
class BaseRestriction
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
     * @Column(type="string", length=90)
     */
    protected $label;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $creation_date;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $update_date;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="restrictionRelatedByAuteurIds")
     *
     * @JoinColumn(name="auteur_id", referencedColumnName="id", nullable=false)
     */
    protected $UserRelatedByAuteurId;

    /**
     * @ManyToMany(targetEntity="User", mappedBy="restrictions")
     */
    protected $Users;

    public function __construct()
    {
        $this->Users = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Restriction
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
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Restriction
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
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return \App\Entity\Restriction
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
     * @return \App\Entity\Restriction
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
     * Set User entity related by `auteur_id` (many to one).
     *
     * @return \App\Entity\Restriction
     */
    public function setUserRelatedByAuteurId(User $User = null)
    {
        $this->UserRelatedByAuteurId = $User;

        return $this;
    }

    /**
     * Get User entity related by `auteur_id` (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUserRelatedByAuteurId()
    {
        return $this->UserRelatedByAuteurId;
    }

    /**
     * Add User entity to collection.
     *
     * @return \App\Entity\Restriction
     */
    public function addUser(User $User)
    {
        $this->Users[] = $User;

        return $this;
    }

    /**
     * Remove User entity from collection.
     *
     * @return \App\Entity\Restriction
     */
    public function removeUser(User $User)
    {
        $this->Users->removeElement($User);

        return $this;
    }

    /**
     * Get User entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->Users;
    }

    public function __sleep()
    {
        return ['id', 'label', 'creation_date', 'update_date', 'auteur_id'];
    }
}