<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EnveloppeLigneNature;
use App\Repository\EnveloppeLigneRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne reportée du retour de jeu dans l'enveloppe du personnage pour un GN : étiquette à réinsérer ou objet à remettre au PC joueur.
 *
 * Créée à l'import d'un dépouillement validé (jamais pour une simulation). Elle disparaît avec le personnage ou le GN, pas avec le
 * dépouillement, dont elle garde seulement la référence.
 */
#[ORM\Entity(repositoryClass: EnveloppeLigneRepository::class)]
#[ORM\Table(name: 'enveloppe_ligne')]
#[ORM\Index(columns: ['personnage_id', 'gn_id'], name: 'idx_enveloppe_ligne_personnage_gn')]
class EnveloppeLigne
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Personnage $personnage;

    /** GN pour lequel la ligne est à préparer (le GN suivant le retour de jeu). */
    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Gn $gn;

    #[ORM\ManyToOne(targetEntity: Depouillement::class)]
    #[ORM\JoinColumn(name: 'depouillement_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Depouillement $depouillement = null;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: EnveloppeLigneNature::class)]
    private EnveloppeLigneNature $nature;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $libelle = '';

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $quantite = 1;

    /** Numéro de l'objet connu, quand l'étiquette l'a fait retrouver. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numeroObjet = null;

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

    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(Personnage $personnage): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getGn(): Gn
    {
        return $this->gn;
    }

    public function setGn(Gn $gn): static
    {
        $this->gn = $gn;

        return $this;
    }

    public function getDepouillement(): ?Depouillement
    {
        return $this->depouillement;
    }

    public function setDepouillement(?Depouillement $depouillement): static
    {
        $this->depouillement = $depouillement;

        return $this;
    }

    public function getNature(): EnveloppeLigneNature
    {
        return $this->nature;
    }

    public function setNature(EnveloppeLigneNature $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function getNumeroObjet(): ?int
    {
        return $this->numeroObjet;
    }

    public function setNumeroObjet(?int $numeroObjet): static
    {
        $this->numeroObjet = $numeroObjet;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
