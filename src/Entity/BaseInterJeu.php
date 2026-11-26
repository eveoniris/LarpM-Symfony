<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InterJeuEtat;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'inter_jeu')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseInterJeu', 'extended' => 'InterJeu'])]
abstract class BaseInterJeu
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 255)]
    protected string $nom = '';

    #[Column(name: 'annee_jeu', type: Types::INTEGER)]
    protected int $anneeJeu = 0;

    #[Column(name: 'date_reel', type: Types::DATE_MUTABLE)]
    protected DateTimeInterface $dateReel;

    #[Column(type: Types::STRING, length: 50)]
    protected string $etat = InterJeuEtat::A_VALIDER->value;

    #[Column(name: 'information_complementaire', type: Types::TEXT, nullable: true)]
    protected ?string $informationComplementaire = null;

    #[Column(name: 'chronologie_generee', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $chronologieGeneree = false;

    /** @var Collection<int, InterJeuPersonnage> */
    #[OneToMany(targetEntity: InterJeuPersonnage::class, mappedBy: 'interJeu', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $participations;

    /** @var Collection<int, InterJeuOrga> */
    #[OneToMany(targetEntity: InterJeuOrga::class, mappedBy: 'interJeu', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $orgas;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->orgas = new ArrayCollection();
        $this->dateReel = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAnneeJeu(): int
    {
        return $this->anneeJeu;
    }

    public function setAnneeJeu(int $anneeJeu): static
    {
        $this->anneeJeu = $anneeJeu;

        return $this;
    }

    public function getDateReel(): DateTimeInterface
    {
        return $this->dateReel;
    }

    public function setDateReel(DateTimeInterface $dateReel): static
    {
        $this->dateReel = $dateReel;

        return $this;
    }

    public function getEtat(): InterJeuEtat
    {
        return InterJeuEtat::from($this->etat);
    }

    public function setEtat(InterJeuEtat $etat): static
    {
        $this->etat = $etat->value;

        return $this;
    }

    public function getInformationComplementaire(): ?string
    {
        return $this->informationComplementaire;
    }

    public function setInformationComplementaire(?string $informationComplementaire): static
    {
        $this->informationComplementaire = $informationComplementaire;

        return $this;
    }

    /**
     * Toutes les participations, quel que soit leur statut (liste de gestion).
     *
     * @return Collection<int, InterJeuPersonnage>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    /** @return Collection<int, InterJeuOrga> */
    public function getOrgas(): Collection
    {
        return $this->orgas;
    }

    public function isDateReelPassed(): bool
    {
        return $this->dateReel <= new DateTime('today');
    }

    public function isChronologieGeneree(): bool
    {
        return $this->chronologieGeneree;
    }

    public function setChronologieGeneree(bool $chronologieGeneree): static
    {
        $this->chronologieGeneree = $chronologieGeneree;

        return $this;
    }
}
