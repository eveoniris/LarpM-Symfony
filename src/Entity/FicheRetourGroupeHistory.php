<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FicheRetourGroupeHistoryRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: FicheRetourGroupeHistoryRepository::class)]
#[ORM\Table(name: 'fiche_retour_groupe_history')]
#[ORM\Index(columns: ['fiche_retour_groupe_id'], name: 'idx_fiche_history_fiche')]
#[ORM\Index(columns: ['user_id'], name: 'idx_fiche_history_user')]
class FicheRetourGroupeHistory
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_IMPORT = 'IMPORT';
    public const ACTION_CORRECTION = 'CORRECTION';
    public const ACTION_MISE_A_JOUR = 'MISE_A_JOUR';
    public const ACTION_CONSOMMATION = 'CONSOMMATION';

    public const MOTIF_TYPES = [
        self::ACTION_CREATE => 'Création',
        self::ACTION_IMPORT => 'Import',
        self::ACTION_CORRECTION => 'Correction',
        self::ACTION_MISE_A_JOUR => 'Mise à jour',
        self::ACTION_CONSOMMATION => 'Consommation',
    ];

    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FicheRetourGroupe::class, inversedBy: 'histories')]
    #[JoinColumn(name: 'fiche_retour_groupe_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FicheRetourGroupe $ficheRetourGroupe;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $action_date;

    #[Column(type: Types::STRING, length: 20)]
    private string $action_type;

    #[Column(type: Types::STRING, length: 512, nullable: true)]
    private ?string $import_file_path = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $import_original_filename = null;

    #[Column(type: Types::JSON, nullable: true)]
    private ?array $data_before = null;

    #[Column(type: Types::JSON)]
    private array $data_after = [];

    #[Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $motif_type = null;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    public function __construct()
    {
        $this->action_date = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFicheRetourGroupe(): FicheRetourGroupe
    {
        return $this->ficheRetourGroupe;
    }

    public function setFicheRetourGroupe(FicheRetourGroupe $ficheRetourGroupe): static
    {
        $this->ficheRetourGroupe = $ficheRetourGroupe;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getActionDate(): DateTimeInterface
    {
        return $this->action_date;
    }

    public function setActionDate(DateTimeInterface $action_date): static
    {
        $this->action_date = $action_date;

        return $this;
    }

    public function getActionType(): string
    {
        return $this->action_type;
    }

    public function setActionType(string $action_type): static
    {
        $this->action_type = $action_type;

        return $this;
    }

    public function getImportFilePath(): ?string
    {
        return $this->import_file_path;
    }

    public function setImportFilePath(?string $import_file_path): static
    {
        $this->import_file_path = $import_file_path;

        return $this;
    }

    public function getImportOriginalFilename(): ?string
    {
        return $this->import_original_filename;
    }

    public function setImportOriginalFilename(?string $import_original_filename): static
    {
        $this->import_original_filename = $import_original_filename;

        return $this;
    }

    public function getDataBefore(): ?array
    {
        return $this->data_before;
    }

    public function setDataBefore(?array $data_before): static
    {
        $this->data_before = $data_before;

        return $this;
    }

    public function getDataAfter(): array
    {
        return $this->data_after;
    }

    public function setDataAfter(array $data_after): static
    {
        $this->data_after = $data_after;

        return $this;
    }

    public function getMotifType(): ?string
    {
        return $this->motif_type;
    }

    public function setMotifType(?string $motif_type): static
    {
        $this->motif_type = $motif_type;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getMotifTypeLabel(): string
    {
        return self::MOTIF_TYPES[$this->motif_type] ?? $this->motif_type ?? '';
    }
}
