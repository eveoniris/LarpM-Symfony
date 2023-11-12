<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'message')]
#[ORM\Index(columns: ['auteur'], name: 'fk_message_user1_idx')]
#[ORM\Index(columns: ['personnage_secondaire_id'], name: 'fk_user_personnage_secondaire1_idx')]
#[ORM\Index(columns: ['destinataire'], name: 'fk_message_user2_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseMessage', 'extended' => 'Message'])]
class BaseMessage
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $title;

    /**
     * @Column(name="`text`", type="text", nullable=true)
     */
    protected $text;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $lu;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messageRelatedByAuteurs')]
    #[ORM\JoinColumn(name: 'auteur', referencedColumnName: 'id', nullable: false)]
    protected User $userRelatedByAuteur;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messageRelatedByDestinataires')]
    #[ORM\JoinColumn(name: 'destinataire', referencedColumnName: 'id', nullable: false)]
    protected User $userRelatedByDestinataire;

    public function __construct()
    {
        $this->creation_date = new \DateTime('now');
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Message
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
     * Set the value of title.
     *
     * @param string $title
     *
     * @return \App\Entity\Message
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of text.
     *
     * @param string $text
     *
     * @return \App\Entity\Message
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
     * @return \App\Entity\Message
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
     * @return \App\Entity\Message
     */
    public function setUpdateDate($update_date): static
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
     * Set the value of lu.
     *
     * @param bool $lu
     *
     * @return \App\Entity\Message
     */
    public function setLu($lu)
    {
        $this->lu = $lu;

        return $this;
    }

    /**
     * Get the value of lu.
     *
     * @return bool
     */
    public function getLu()
    {
        return $this->lu;
    }

    public function setUserRelatedByAuteur(User $User = null): static
    {
        $this->userRelatedByAuteur = $User;

        return $this;
    }

    /**
     * Get User entity related by `auteur` (many to one).
     */
    public function getUserRelatedByAuteur(): ?User
    {
        return $this->userRelatedByAuteur;
    }

    public function setUserRelatedByDestinataire(User $User = null): static
    {
        $this->userRelatedByDestinataire = $User;

        return $this;
    }

    public function getUserRelatedByDestinataire(): ?User
    {
        return $this->userRelatedByDestinataire;
    }

    public function __sleep()
    {
        return ['id', 'title', 'text', 'creation_date', 'update_date', 'lu', 'auteur', 'destinataire'];
    }
}
