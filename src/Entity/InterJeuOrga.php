<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Accès d'un orga externe (autre association) à un inter-jeu, validé par le rôle INTER_JEU.
 *
 * L'orga consulte les participants et les statistiques, et propose des changements de liste
 * que le rôle INTER_JEU valide. Il n'écrit jamais directement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'inter_jeu_orga')]
#[ORM\UniqueConstraint(name: 'uniq_inter_jeu_orga', columns: ['inter_jeu_id', 'user_id'])]
class InterJeuOrga
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER, options: ['unsigned' => true]), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BaseInterJeu::class, inversedBy: 'orgas')]
    #[ORM\JoinColumn(name: 'inter_jeu_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BaseInterJeu $interJeu;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'valide_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $validePar = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(BaseInterJeu $interJeu, User $user, ?User $validePar = null)
    {
        $this->interJeu = $interJeu;
        $this->user = $user;
        $this->validePar = $validePar;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterJeu(): BaseInterJeu
    {
        return $this->interJeu;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
