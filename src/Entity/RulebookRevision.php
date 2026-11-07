<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RulebookStatut;
use App\Repository\RulebookRevisionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Instantané d'une règle à chaque enregistrement : permet de relire une ancienne version et de la restaurer.
 * Le journal d'actions renvoie vers (règle, version).
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: RulebookRevisionRepository::class)]
#[ORM\Table(name: 'rulebook_revision')]
#[ORM\UniqueConstraint(name: 'uniq_rulebook_revision_regle_version', columns: ['regle_id', 'version'])]
class RulebookRevision
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RulebookRegle::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(name: 'regle_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RulebookRegle $regle;

    #[ORM\Column(type: Types::INTEGER)]
    private int $version;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $titre;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texteClair = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailTechnique = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: RulebookStatut::class)]
    private RulebookStatut $statut;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $publiee = false;

    /** Motif de la modification, saisi par l'auteur. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'auteur_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $auteur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /** Capture l'état courant de la règle (à appeler avant ou après modification selon le besoin du service appelant). */
    public static function depuis(RulebookRegle $regle, ?User $auteur, ?string $motif = null): self
    {
        $revision = new self();
        $revision->regle = $regle;
        $revision->version = $regle->getVersion();
        $revision->titre = $regle->getTitre();
        $revision->texteClair = $regle->getTexteClair();
        $revision->detailTechnique = $regle->getDetailTechnique();
        $revision->statut = $regle->getStatut();
        $revision->publiee = $regle->isPubliee();
        $revision->auteur = $auteur;
        $revision->motif = $motif;

        return $revision;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegle(): RulebookRegle
    {
        return $this->regle;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getTexteClair(): ?string
    {
        return $this->texteClair;
    }

    public function getDetailTechnique(): ?string
    {
        return $this->detailTechnique;
    }

    public function getStatut(): RulebookStatut
    {
        return $this->statut;
    }

    public function isPubliee(): bool
    {
        return $this->publiee;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
