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
#[ORM\Table(name: 'billet')]
#[ORM\Index(columns: ['createur_id'], name: 'fk_billet_user1')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_billet_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseBillet', 'extended' => 'Billet'])]
abstract class BaseBillet
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $update_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $fedegn = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $gn_id = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Participant>|\App\Entity\Participant[]
     */
    #[OneToMany(mappedBy: 'billet', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'billet_id', nullable: 'false')]
    protected ?Collection $participants = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'billets')]
    #[JoinColumn(name: 'createur_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    #[ManyToOne(targetEntity: Gn::class, inversedBy: 'billets')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Gn $gn = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabel(string $label): string
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setDescription(?string $description): self
    {
        $this->description = (string) $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setCreationDate(\DateTime $creation_date): self
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(\DateTime $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setFedegn(bool $fedegn): self
    {
        $this->fedegn = $fedegn;

        return $this;
    }

    public function getFedegn(): bool
    {
        return $this->fedegn;
    }

    public function addParticipant(Participant $participant): self
    {
        $this->participants[] = $participant;

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function setUser(User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setGn(Gn $gn = null): self
    {
        $this->gn = $gn;
        $this->gn_id = $gn?->getId();

        return $this;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description', 'creation_date', 'update_date', 'createur_id', 'gn_id', 'fedegn'];
    } */
}
