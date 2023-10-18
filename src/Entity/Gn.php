<?php

namespace App\Entity;

use App\Repository\GnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GnRepository::class)]
class Gn extends BaseGn implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    /**
     * Fourni la liste de tous les personnages prévu sur un jeu.
     */
    public function getPersonnages(): ArrayCollection
    {
        $personnages = new ArrayCollection();
        $participants = $this->getParticipants();

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage) {
                $personnages[] = $personnage;
            }
        }

        return $personnages;
    }

    /**
     * Fourni la liste de tous les personnages ayant une certaine renommé prévu sur un jeu.
     */
    public function getPersonnagesRenom($renom = 0): ArrayCollection
    {
        $personnages = new ArrayCollection();
        $participants = $this->getParticipants();

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage && $personnage->getRenomme() > $renom) {
                $personnages[] = $personnage;
            }
        }

        return $personnages;
    }

    /**
     * Fourni la liste des groupes de PJ prévu sur un jeu.
     */
    public function getGroupeGnsPj(): ArrayCollection
    {
        $groupeGns = new ArrayCollection();

        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGroupe()->getPj()) {
                $groupeGns[] = $groupeGn;
            }
        }

        return $groupeGns;
    }

    /**
     * Fourni la liste des groupes prévu sur un jeu.
     */
    public function getGroupes(): ArrayCollection
    {
        $groupes = new ArrayCollection();

        foreach ($this->getGroupeGns() as $groupeGn) {
            $groupes[] = $groupeGn->getGroupe();
        }

        return $groupes;
    }

    /**
     * Fourni la liste des groupes réservés sur le jeu.
     */
    public function getGroupesReserves(): ArrayCollection
    {
        $groupes = new ArrayCollection();

        foreach ($this->getGroupeGns() as $groupeGn) {
            if (!$groupeGn->getFree()) {
                $groupes[] = $groupeGn->getGroupe();
            }
        }

        return $groupes;
    }

    /**
     * Fourni la liste des tous les participants pour la FédéGN.
     */
    public function getParticipantsFedeGn(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getBillets() as $billet) {
            if (true == $billet->getFedegn()) {
                $participants = new ArrayCollection(array_merge($participants->toArray(), $billet->getParticipants()->toArray()));
            }
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN mais n'ayant pas encore acheté de billets.
     */
    public function getParticipantsWithoutBillet(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!$participant->getBillet()) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN avec un billet.
     */
    public function getParticipantsWithBillet(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet()) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN ayant un billet mais n'étant pas encore dans un groupe.
     */
    public function getParticipantsWithoutGroup(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet() && !$participant->getGroupeGn()) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN ayant un billet mais n'ayant pas de perso.
     */
    public function getParticipantsWithoutPerso(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet() && !$participant->getPersonnage()) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants inscrit en tant que PNJ.
     */
    public function getParticipantsPnj(): ArrayCollection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->isPnj()) {
                $participants->add($participant);
            }
        }

        return $participants;
    }

    /**
     * Indique si le jeu est passé ou pas.
     */
    public function isPast(): bool
    {
        $now = new \DateTime('NOW');

        return $this->getDateFin() < $now;
    }

    /**
     * Fourni le nombre de billet vendu pour ce jeu.
     */
    public function getBilletCount(): int
    {
        $count = 0;
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Fourni la liste des utilisateurs de ce GN.
     */
    public function getUsers(): ArrayCollection
    {
        $result = new ArrayCollection();

        foreach ($this->getBillets() as $billet) {
            foreach ($billet->getParticipants() as $participant) {
                $result[] = $participant->getUser();
            }
        }

        return $result;
    }

    public function getBesoinValidationCi(): bool
    {
        return '' != $this->getConditionsInscription() && null != $this->getConditionsInscription();
    }

    /**
     * Retourne le numéro du GN (à partir de son label).
     */
    public function getNumber(): int
    {
        $str = preg_replace('/[^0-9.]+/', '', $this->getLabel());

        return (int) $str;
    }

    /**
     * Fourni la liste de tous les participants à partir d'une liste d'id de personnage.
     */
    public function getParticipantsInterGN(): ArrayCollection
    {
        $personnage_ids = [497, 1312, 3136, 2927, 1889, 3356, 3154, 77, 3134, 2631, 2005, 3209, 3127, 3688, 2430, 57, 951, 400, 2839, 1103, 3870, 3614, 3860, 3873, 3626, 2079, 2899, 3387, 3868, 1823, 3265, 2051, 2970, 2544, 2030, 2817, 3254, 1344, 3426, 2783, 2047, 3322, 3097, 3062, 278, 3246, 2048, 3390, 2806, 2309, 2801, 2843, 124, 3280, 1345, 3407, 742, 1332, 1340, 1587, 3296, 581, 3452, 1769, 3282, 3545, 3577, 3428, 2993, 3073, 3855, 2213, 687, 3227, 3350, 3370, 1189, 181, 3194, 3459, 3400, 191, 2964, 112, 2200, 2297, 1969, 2762, 2893, 1224, 2878, 2224, 2350, 2100];

        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!is_null($participant->getPersonnage()) && in_array($participant->getPersonnage()->getId(), $personnage_ids)) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }
}
