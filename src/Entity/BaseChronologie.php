<?php

namespace App\Entity;

use App\Repository\BaseChronologieRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity(repositoryClass: BaseChronologieRepository::class)]
#[ORM\Table(name: 'chronologie')]
#[ORM\Index(columns: ['zone_politique_id'], name: 'fk_chronologie_zone_politique1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseChronologie', 'extended' => 'Chronologie'])]
class BaseChronologie
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'description', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $description = '';

    #[Column(name: 'year', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $year = 2020;

    #[Column(name: 'month', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $month = null;

    #[Column(name: 'day', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $day = null;

    #[Column(name: 'visibilite', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $visibilite = '';

    #[ManyToOne(targetEntity: Territoire::class, cascade: ['persist', 'remove'], inversedBy: 'chronologies')]
    #[JoinColumn(name: 'zone_politique_id', referencedColumnName: 'id', nullable: 'false')]
    protected Territoire $territoire;

    public function __construct()
    {
        $this->territoire = new Territoire();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getMonth(): int
    {
        return $this->month ?? 1;
    }

    public function setDay(int $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function getDay(): int
    {
        return $this->day ?? 1;
    }

    public function setVisibilite(string $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getVisibilite(): string
    {
        return $this->visibilite;
    }

    public function setTerritoire(Territoire $territoire = null): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    public function getTerritoire(): Territoire
    {
        return $this->territoire;
    }

    public function __sleep()
    {
        return ['id', 'description', 'zone_politique_id', 'year', 'month', 'day', 'visibilite'];
    }
}
