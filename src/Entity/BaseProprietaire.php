<?php

namespace App\Entity;

use App\Repository\BaseUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity(repositoryClass: BaseUserRepository::class)]
#[ORM\Table(name: 'proprietaire')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseProprietaire', 'extended' => 'Proprietaire'])]
abstract class BaseProprietaire
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected ?string $nom = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 450, nullable: true)]
    protected ?string $adresse = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected ?string $mail = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected ?string $tel = null;

    #[OneToMany(mappedBy: 'proprietaire', targetEntity: Objet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'proprietaire_id', nullable: 'false')]
    protected Collection $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of nom.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     */
    public function getNom(): string
    {
        return $this->nom ?? '';
    }

    /**
     * Set the value of adresse.
     */
    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get the value of adresse.
     */
    public function getAdresse(): string
    {
        return $this->adresse ?? '';
    }

    /**
     * Set the value of mail.
     */
    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get the value of mail.
     */
    public function getMail(): string
    {
        return $this->mail ?? '';
    }

    /**
     * Set the value of tel.
     */
    public function setTel(string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * Get the value of tel.
     */
    public function getTel(): string
    {
        return $this->tel ?? '';
    }

    /**
     * Add Objet entity to collection (one to many).
     */
    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     */
    public function getObjets(): Collection
    {
        return $this->objets;
    }

    /* public function __sleep()
    {
        return ['id', 'nom', 'adresse', 'mail', 'tel'];
    } */
}
