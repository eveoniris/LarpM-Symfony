<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DepouillementPhotoRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Photo du contenu d'une enveloppe, prise par le dépouilleur.
 *
 * Le fichier est stocké hors du dossier public (voir DepouillementPhotoService) et servi par le contrôleur après contrôle
 * des droits. Une photo peut être partagée avec le propriétaire de l'enveloppe (joueur ou chef de groupe) comme preuve.
 */
#[ORM\Entity(repositoryClass: DepouillementPhotoRepository::class)]
#[ORM\Table(name: 'depouillement_photo')]
#[ORM\Index(columns: ['depouillement_id'], name: 'idx_depouillement_photo_depouillement')]
class DepouillementPhoto
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Depouillement::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'depouillement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Depouillement $depouillement;

    /** Chemin du fichier relatif au dossier de stockage (nom aléatoire, jamais le nom d'origine). */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $chemin;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nomOriginal;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $mimeType;

    #[ORM\Column(type: Types::INTEGER)]
    private int $taille;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $televerseAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'televerse_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $televersePar = null;

    /** Visible par le propriétaire de l'enveloppe (joueur ou chef de groupe). */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $partage = false;

    public function __construct()
    {
        $this->televerseAt = new DateTime();
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

    public function getChemin(): string
    {
        return $this->chemin;
    }

    public function setChemin(string $chemin): static
    {
        $this->chemin = $chemin;

        return $this;
    }

    public function getNomOriginal(): string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(string $nomOriginal): static
    {
        $this->nomOriginal = $nomOriginal;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getTaille(): int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getTeleverseAt(): DateTimeInterface
    {
        return $this->televerseAt;
    }

    public function getTeleversePar(): ?User
    {
        return $this->televersePar;
    }

    public function setTeleversePar(?User $televersePar): static
    {
        $this->televersePar = $televersePar;

        return $this;
    }

    public function isPartage(): bool
    {
        return $this->partage;
    }

    public function setPartage(bool $partage): static
    {
        $this->partage = $partage;

        return $this;
    }
}
