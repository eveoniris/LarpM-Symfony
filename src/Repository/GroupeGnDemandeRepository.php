<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GroupeGn;
use App\Entity\GroupeGnDemande;
use App\Entity\Participant;
use App\Enum\GroupeGnDemandeType;

class GroupeGnDemandeRepository extends BaseRepository
{
    /**
     * Toutes les demandes en attente pour une session de groupe.
     *
     * @return array<int, GroupeGnDemande>
     */
    public function findByGroupeGn(GroupeGn $groupeGn): array
    {
        /** @var array<int, GroupeGnDemande> */
        return $this->findBy(['groupeGn' => $groupeGn], ['date' => 'ASC']);
    }

    /**
     * Les candidatures en attente pour une session (émises par des joueurs).
     *
     * @return array<int, GroupeGnDemande>
     */
    public function findCandidaturesByGroupeGn(GroupeGn $groupeGn): array
    {
        /** @var array<int, GroupeGnDemande> */
        return $this->findBy(
            ['groupeGn' => $groupeGn, 'type' => GroupeGnDemandeType::CANDIDATURE],
            ['date' => 'ASC'],
        );
    }

    /**
     * Les invitations en attente reçues par un participant (émises par des chefs).
     *
     * @return array<int, GroupeGnDemande>
     */
    public function findInvitationsForParticipant(Participant $participant): array
    {
        /** @var array<int, GroupeGnDemande> */
        return $this->findBy(
            ['participant' => $participant, 'type' => GroupeGnDemandeType::INVITATION],
            ['date' => 'ASC'],
        );
    }

    public function findOneByParticipantAndGroupeGn(Participant $participant, GroupeGn $groupeGn): ?GroupeGnDemande
    {
        /** @var GroupeGnDemande|null */
        return $this->findOneBy(['participant' => $participant, 'groupeGn' => $groupeGn]);
    }
}
