<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GnTimelineVisibilite;
use App\Repository\GnTimelineStepRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Étape de la chronologie d'un GN (jalon d'information ou, plus tard, action planifiée).
 */
#[ORM\Entity(repositoryClass: GnTimelineStepRepository::class)]
#[ORM\Table(name: 'gn_timeline_step')]
#[ORM\Index(name: 'idx_gn_timeline_step_date', columns: ['date_prevue'])]
class GnTimelineStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Gn $gn = null;

    #[ORM\Column(length: 150)]
    private string $libelle = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_prevue', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $datePrevue;

    #[ORM\Column(length: 20, enumType: GnTimelineVisibilite::class)]
    private GnTimelineVisibilite $visibilite = GnTimelineVisibilite::PUBLIC;

    public function __construct()
    {
        $this->datePrevue = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function setGn(?Gn $gn): static
    {
        $this->gn = $gn;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDatePrevue(): DateTimeImmutable
    {
        return $this->datePrevue;
    }

    public function setDatePrevue(DateTimeImmutable $datePrevue): static
    {
        $this->datePrevue = $datePrevue;

        return $this;
    }

    public function getVisibilite(): GnTimelineVisibilite
    {
        return $this->visibilite;
    }

    public function setVisibilite(GnTimelineVisibilite $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }
}
