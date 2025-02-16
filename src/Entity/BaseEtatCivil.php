<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'etat_civil')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseEtatCivil', 'extended' => 'EtatCivil'])]
class BaseEtatCivil
{
    public ArrayCollection $users;

    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $nom = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $prenom = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $prenom_usage = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $telephone = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $photo = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date_naissance = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $probleme_medicaux = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $personne_a_prevenir = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $tel_pap = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $fedegn = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected $update_date;

    #[ORM\OneToOne(mappedBy: 'etatCivil', targetEntity: User::class, cascade: ['persist', 'remove'])]
    protected User $user;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenomUsage(string $prenom_usage): static
    {
        $this->prenom_usage = $prenom_usage;

        return $this;
    }

    public function getPrenomUsage(): ?string
    {
        return $this->prenom_usage;
    }

    public function setTelephone(string $telephone): string
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setDateNaissance(?\DateTime $date_naissance): string
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setProblemeMedicaux(string $probleme_medicaux): static
    {
        $this->probleme_medicaux = $probleme_medicaux;

        return $this;
    }

    public function getProblemeMedicaux(): ?string
    {
        return $this->probleme_medicaux;
    }

    public function setPersonneAPrevenir(string $personne_a_prevenir): static
    {
        $this->personne_a_prevenir = $personne_a_prevenir;

        return $this;
    }

    public function getPersonneAPrevenir(): ?string
    {
        return $this->personne_a_prevenir;
    }

    public function setTelPap(string $tel_pap): static
    {
        $this->tel_pap = $tel_pap;

        return $this;
    }

    public function getTelPap(): ?string
    {
        return $this->tel_pap;
    }

    public function setFedegn($fedegn): static
    {
        $this->fedegn = $fedegn;

        return $this;
    }

    public function getFedegn(): ?string
    {
        return $this->fedegn;
    }

    public function setCreationDate(?\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(?\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setUser(User $user = null): static
    {
        if (null !== $user) {
            $user->setEtatCivil($this);
            $this->user = $user;
        }

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
