<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Evenement.
 *
 * @Table(name="evenement")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseEvenement", "extended":"Evenement"})
 */
class BaseEvenement
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`text`", type="string", length=450)
     */
    protected $text;

    /**
     * @Column(name="`date`", type="string", length=45)
     */
    protected $date;

    /**
     * @Column(type="datetime")
     */
    protected $date_creation;

    /**
     * @Column(type="datetime")
     */
    protected $date_update;

    /**
     * @OneToMany(targetEntity="IntrigueHasEvenement", mappedBy="evenement", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="id", referencedColumnName="evenement_id", nullable=false)
     */
    protected $intrigueHasEvenements;

    public function __construct()
    {
        $this->intrigueHasEvenements = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Evenement
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
     * @return \App\Entity\Evenement
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
     * @param string $date
     *
     * @return \App\Entity\Evenement
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date_creation.
     *
     * @param \DateTime $date_creation
     *
     * @return \App\Entity\Evenement
     */
    public function setDateCreation($date_creation)
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    /**
     * Get the value of date_creation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->date_creation;
    }

    /**
     * Set the value of date_update.
     *
     * @param \DateTime $date_update
     *
     * @return \App\Entity\Evenement
     */
    public function setDateUpdate($date_update)
    {
        $this->date_update = $date_update;

        return $this;
    }

    /**
     * Get the value of date_update.
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->date_update;
    }

    /**
     * Add IntrigueHasEvenement entity to collection (one to many).
     *
     * @return \App\Entity\Evenement
     */
    public function addIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement)
    {
        $this->intrigueHasEvenements[] = $intrigueHasEvenement;

        return $this;
    }

    /**
     * Remove IntrigueHasEvenement entity from collection (one to many).
     *
     * @return \App\Entity\Evenement
     */
    public function removeIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement)
    {
        $this->intrigueHasEvenements->removeElement($intrigueHasEvenement);

        return $this;
    }

    /**
     * Get IntrigueHasEvenement entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIntrigueHasEvenements()
    {
        return $this->intrigueHasEvenements;
    }

    public function __sleep()
    {
        return ['id', 'text', 'date', 'date_creation', 'date_update'];
    }
}
