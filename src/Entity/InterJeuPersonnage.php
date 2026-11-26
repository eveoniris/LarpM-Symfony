<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InterJeuParticipantStatut;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Participation d'un personnage à un inter-jeu, avec son statut.
 *
 * Une proposition d'un orga externe (statutPropose non nul) attend la validation du rôle INTER_JEU :
 * le statut réel ne change qu'à la validation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'inter_jeu_personnage')]
class InterJeuPersonnage
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: BaseInterJeu::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'inter_jeu_id', referencedColumnName: 'id', nullable: false)]
    private BaseInterJeu $interJeu;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    private Personnage $personnage;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'inscrit'])]
    private string $statut = InterJeuParticipantStatut::INSCRIT->value;

    #[ORM\Column(name: 'statut_propose', type: Types::STRING, length: 20, nullable: true)]
    private ?string $statutPropose = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'propose_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $proposePar = null;

    /** Ligne créée par une proposition d'ajout d'un orga externe, pas encore validée : supprimée si refusée. */
    #[ORM\Column(name: 'ajout_propose', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $ajoutPropose = false;

    public function __construct(BaseInterJeu $interJeu, Personnage $personnage, InterJeuParticipantStatut $statut = InterJeuParticipantStatut::INSCRIT)
    {
        $this->interJeu = $interJeu;
        $this->personnage = $personnage;
        $this->statut = $statut->value;
    }

    public function getInterJeu(): BaseInterJeu
    {
        return $this->interJeu;
    }

    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    public function getStatut(): InterJeuParticipantStatut
    {
        return InterJeuParticipantStatut::tryFrom($this->statut) ?? InterJeuParticipantStatut::INSCRIT;
    }

    public function setStatut(InterJeuParticipantStatut $statut): static
    {
        $this->statut = $statut->value;

        return $this;
    }

    public function isInscrit(): bool
    {
        return InterJeuParticipantStatut::INSCRIT === $this->getStatut();
    }

    public function getStatutPropose(): ?InterJeuParticipantStatut
    {
        return null !== $this->statutPropose ? InterJeuParticipantStatut::tryFrom($this->statutPropose) : null;
    }

    public function getProposePar(): ?User
    {
        return $this->proposePar;
    }

    public function hasProposition(): bool
    {
        return null !== $this->getStatutPropose();
    }

    public function proposer(InterJeuParticipantStatut $statut, User $user): static
    {
        $this->statutPropose = $statut->value;
        $this->proposePar = $user;

        return $this;
    }

    /**
     * Proposition d'ajout : le personnage entre « en attente » et l'inscription attend la validation.
     */
    public function proposerAjout(User $user): static
    {
        $this->statut = InterJeuParticipantStatut::EN_ATTENTE->value;
        $this->ajoutPropose = true;

        return $this->proposer(InterJeuParticipantStatut::INSCRIT, $user);
    }

    public function isAjoutPropose(): bool
    {
        return $this->ajoutPropose;
    }

    public function validerProposition(): static
    {
        $propose = $this->getStatutPropose();
        if (null !== $propose) {
            $this->statut = $propose->value;
        }

        return $this->annulerProposition();
    }

    /**
     * Retire la proposition en cours. Pour un ajout proposé, l'appelant supprime la ligne (voir isAjoutPropose).
     */
    public function annulerProposition(): static
    {
        $this->statutPropose = null;
        $this->proposePar = null;
        $this->ajoutPropose = false;

        return $this;
    }
}
