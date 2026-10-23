<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Repository\DepouillementEtiquetteRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Étiquette d'objet ou d'arme trouvée dans une enveloppe.
 *
 * Le numéro (en haut à gauche) désigne un objet connu pour une étiquette d'objet ; pour une étiquette d'arme c'est un numéro de
 * stock ou une feinte. La qualité (en haut à droite, trois chiffres) donne la facture et la nature. La destination, l'action sur
 * l'objet et les anomalies sont celles calculées au moment de la saisie (elles restent telles que le dépouilleur les a vues).
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: DepouillementEtiquetteRepository::class)]
#[ORM\Table(name: 'depouillement_etiquette')]
#[ORM\Index(columns: ['depouillement_id'], name: 'idx_depouillement_etiquette_depouillement')]
class DepouillementEtiquette
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Depouillement::class, inversedBy: 'etiquettes')]
    #[ORM\JoinColumn(name: 'depouillement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Depouillement $depouillement;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: EtiquetteNature::class)]
    private EtiquetteNature $nature;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: EtiquetteCouleur::class)]
    private EtiquetteCouleur $couleur;

    /** Numéro en haut à gauche de l'étiquette. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numero = null;

    /** Qualité en haut à droite : trois chiffres (facture puis nature). Vide pour un lot d'étiquettes d'armes génériques. */
    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    private ?string $qualite = null;

    /** Nombre d'étiquettes identiques (plusieurs étiquettes d'armes génériques comptées d'un coup). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $quantite = 1;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: EtiquetteSituation::class)]
    private EtiquetteSituation $situation;

    /** Objet connu retrouvé à partir du numéro et de la qualité (étiquette d'objet uniquement). */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Item $item = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: EtiquetteDestination::class)]
    private EtiquetteDestination $destination;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: EtiquetteActionObjet::class)]
    private ?EtiquetteActionObjet $actionObjet = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $speciale = false;

    /** À mentionner dans le matériel à vérifier de l'enveloppe de l'opus suivant. */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aPrevoirProchainOpus = false;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $anomalies = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarque = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepouillement(): Depouillement
    {
        return $this->depouillement;
    }

    public function setDepouillement(Depouillement $depouillement): static
    {
        $this->depouillement = $depouillement;

        return $this;
    }

    public function getNature(): EtiquetteNature
    {
        return $this->nature;
    }

    public function setNature(EtiquetteNature $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getCouleur(): EtiquetteCouleur
    {
        return $this->couleur;
    }

    public function setCouleur(EtiquetteCouleur $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getQualite(): ?string
    {
        return $this->qualite;
    }

    public function setQualite(?string $qualite): static
    {
        $this->qualite = $qualite;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getSituation(): EtiquetteSituation
    {
        return $this->situation;
    }

    public function setSituation(EtiquetteSituation $situation): static
    {
        $this->situation = $situation;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getDestination(): EtiquetteDestination
    {
        return $this->destination;
    }

    public function setDestination(EtiquetteDestination $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getActionObjet(): ?EtiquetteActionObjet
    {
        return $this->actionObjet;
    }

    public function setActionObjet(?EtiquetteActionObjet $actionObjet): static
    {
        $this->actionObjet = $actionObjet;

        return $this;
    }

    public function isSpeciale(): bool
    {
        return $this->speciale;
    }

    public function setSpeciale(bool $speciale): static
    {
        $this->speciale = $speciale;

        return $this;
    }

    public function isAPrevoirProchainOpus(): bool
    {
        return $this->aPrevoirProchainOpus;
    }

    public function setAPrevoirProchainOpus(bool $aPrevoirProchainOpus): static
    {
        $this->aPrevoirProchainOpus = $aPrevoirProchainOpus;

        return $this;
    }

    /** @return list<string> */
    public function getAnomalies(): array
    {
        return $this->anomalies;
    }

    /** @param list<string> $anomalies */
    public function setAnomalies(array $anomalies): static
    {
        $this->anomalies = $anomalies;

        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): static
    {
        $this->remarque = $remarque;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
