<?php

namespace App\Entity;

use App\Repository\RestaurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RestaurationRepository::class)]
class Restauration extends BaseRestauration
{
    /**
     * Fourni la liste des utilisateurs classé par GN.
     */
    public function getUserByGn(): Collection
    {
        $result = new ArrayCollection();
        foreach ($this->getParticipantHasRestaurations() as $participantHasRestauration) {
            if ($result->containsKey($participantHasRestauration->getParticipant()->getGn()->getId())) {
                $gn = $result->get($participantHasRestauration->getParticipant()->getGn()->getId());
                ++$gn['count'];
                $gn['users'][] = $participantHasRestauration->getParticipant()->getUser();
                $result[$participantHasRestauration->getParticipant()->getGn()->getId()] = $gn;
            } else {
                $result[$participantHasRestauration->getParticipant()->getGn()->getId()] = [
                    'gn' => $participantHasRestauration->getParticipant()->getGn(),
                    'count' => 1,
                    'users' => [$participantHasRestauration->getParticipant()->getUser()],
                ];
            }
        }

        return $result;
    }

    /**
     * Fourni la liste des restrictions classé par GN.
     */
    public function getRestrictionByGn(): Collection
    {
        $result = new ArrayCollection();
        foreach ($this->getParticipantHasRestaurations() as $participantHasRestauration) {
            if ($participantHasRestauration->getParticipant()->getUser()->getRestrictions()->count() > 0) ;

            if ($result->containsKey($participantHasRestauration->getParticipant()->getGn()->getId())) {
                $gn = $result->get($participantHasRestauration->getParticipant()->getGn()->getId());

                foreach ($participantHasRestauration->getParticipant()->getUser()->getRestrictions() as $restriction) {
                    if (!$gn['restrictions']->containsKey($restriction->getId())) {
                        $gn['restrictions'][$restriction->getId()] = [
                            'restriction' => $restriction,
                            'count' => 1,
                        ];
                    } else {
                        $r = $gn['restrictions']->get($restriction->getId());
                        ++$r['count'];
                        $gn['restrictions'][$restriction->getId()] = $r;
                    }
                }

                $result[$participantHasRestauration->getParticipant()->getGn()->getId()] = $gn;
            } else {
                $result[$participantHasRestauration->getParticipant()->getGn()->getId()] = [
                    'gn' => $participantHasRestauration->getParticipant()->getGn(),
                    'restrictions' => new ArrayCollection(),
                ];

                foreach ($participantHasRestauration->getParticipant()->getUser()->getRestrictions() as $restriction) {
                    $result[$participantHasRestauration->getParticipant()->getGn()->getId()]['restrictions'][$restriction->getId()] = [
                        'restriction' => $restriction,
                        'count' => 1,
                    ];
                }
            }
        }

        return $result;
    }

    public function isVisibleOnMaterielEnveloppe(): bool
    {
        if (str_ends_with(strtoupper(trim($this->label)), 'REPAS ENFANTS')) {
            return true;
        }

        if (str_ends_with(strtoupper(trim($this->label)), 'REPAS CARNE')) {
            return true;
        }

        if (str_ends_with(strtoupper(trim($this->label)), 'REPAS CARNÉ')) {
            return true;
        }

        if (str_ends_with(strtoupper(trim($this->label)), 'REPAS CARNé')) {
            return true;
        }

        if (str_ends_with(strtoupper(trim($this->label)), 'CARTE DE BOISSONS')) {
            return true;
        }

        return false;
    }
}
