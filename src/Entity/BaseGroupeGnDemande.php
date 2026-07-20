<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GroupeGnDemandeType;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'groupe_gn_demande')]
#[ORM\Index(columns: ['participant_id'], name: 'fk_groupe_gn_demande_participant1_idx')]
#[ORM\Index(columns: ['groupe_gn_id'], name: 'fk_groupe_gn_demande_groupe_gn1_idx')]
#[ORM\UniqueConstraint(name: 'uniq_groupe_gn_demande', columns: ['participant_id', 'groupe_gn_id'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeGnDemande', 'extended' => 'GroupeGnDemande'])]
abstract class BaseGroupeGnDemande
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $date;

    #[Column(type: Types::STRING, length: 20, enumType: GroupeGnDemandeType::class)]
    protected GroupeGnDemandeType $type;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $message = null;

    #[ManyToOne(targetEntity: Participant::class)]
    #[JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false)]
    protected Participant $participant;

    #[ManyToOne(targetEntity: GroupeGn::class)]
    #[JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id', nullable: false)]
    protected GroupeGn $groupeGn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getType(): GroupeGnDemandeType
    {
        return $this->type;
    }

    public function setType(GroupeGnDemandeType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isInvitation(): bool
    {
        return GroupeGnDemandeType::INVITATION === $this->type;
    }

    public function isCandidature(): bool
    {
        return GroupeGnDemandeType::CANDIDATURE === $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message = null): static
    {
        $this->message = $message;

        return $this;
    }

    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    public function setParticipant(Participant $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getGroupeGn(): GroupeGn
    {
        return $this->groupeGn;
    }

    public function setGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
    }
}
