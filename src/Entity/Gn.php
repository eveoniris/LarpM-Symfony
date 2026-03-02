<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GnRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: GnRepository::class)]
class Gn extends BaseGn implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel() ?: (string) $this->id ?: '';
    }

    /**
     * Fourni la liste de tous les personnages prévu sur un jeu.
     *
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        $personnages = new ArrayCollection();
        $participants = $this->getParticipants();

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage) {
                $personnages->add($personnage);
            }
        }

        return $personnages;
    }

    public function isInter(): bool
    {
        // maybe later
        return false;
    }

    /**
     * Fourni la liste de tous les personnages ayant une certaine renommé prévu sur un jeu.
     */
    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnagesRenom(int $renom = 0): Collection
    {
        $personnages = new ArrayCollection();
        $participants = $this->getParticipants();

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage && $personnage->getRenomme() > $renom) {
                $personnages->add($personnage);
            }
        }

        return $personnages;
    }

    /**
     * Fourni la liste des groupes de PJ prévu sur un jeu.
     *
     * @return Collection<int, GroupeGn>
     */
    public function getGroupeGnsPj(): Collection
    {
        $groupeGns = new ArrayCollection();

        foreach ($this->getGroupeGns() as $groupeGn) {
            if (!$groupeGn->getGroupe()->getPj()) {
                continue;
            }

            $groupeGns->add($groupeGn);
        }

        return $groupeGns;
    }

    /**
     * Fourni la liste des groupes prévu sur un jeu.
     */
    /**
     * @return Collection<int, Groupe>
     */
    public function getGroupes(): Collection
    {
        $groupes = new ArrayCollection();

        foreach ($this->getGroupeGns() as $groupeGn) {
            $groupes->add($groupeGn->getGroupe());
        }

        return $groupes;
    }

    /**
     * Fourni la liste des groupes réservés sur le jeu.
     *
     * @return Collection<int, Groupe>
     */
    public function getGroupesReserves(): Collection
    {
        /** @var ArrayCollection<int, Groupe> $groupes */
        $groupes = new ArrayCollection();

        /** @var GroupeGn $groupeGn */
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getFree()) {
                continue;
            }

            $groupes->add($groupeGn->getGroupe());
        }

        return $groupes;
    }

    /**
     * Fourni la liste des tous les participants pour la FédéGN.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsFedeGn(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getBillets() as $billet) {
            if (true != $billet->getFedegn()) {
                continue;
            }

            $participants = new ArrayCollection(array_merge($participants->toArray(), $billet->getParticipants()->toArray()));
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN mais n'ayant pas encore acheté de billets.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsWithoutBillet(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet()) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN avec un billet.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsWithBillet(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!$participant->getBillet()) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN ayant un billet mais n'étant pas encore dans un groupe.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsWithoutGroup(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!($participant->getBillet() && !$participant->getGroupeGn())) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants à un GN ayant un billet mais n'ayant pas de perso.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsWithoutPerso(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!($participant->getBillet() && !$participant->getPersonnage())) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }

    /**
     * Fourni la liste de tous les participants inscrit en tant que PNJ.
     */
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsPnj(): Collection
    {
        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!$participant->isPnj()) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }

    /**
     * Indique si le jeu est passé ou pas.
     */
    public function isPast(): bool
    {
        $now = new DateTime('NOW');

        return $this->getDateFin() < $now;
    }

    /**
     * Fourni le nombre de billet vendu pour ce jeu.
     */
    public function getBilletCount(): int
    {
        $count = 0;
        foreach ($this->getParticipants() as $participant) {
            if (!$participant->getBillet()) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    /**
     * Fourni la liste des utilisateurs de ce GN.
     */
    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        $result = new ArrayCollection();

        foreach ($this->getBillets() as $billet) {
            foreach ($billet->getParticipants() as $participant) {
                $result->add($participant->getUser());
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
    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsInterGN(): Collection
    {
        $personnage_ids = [
            497,
            1312,
            3136,
            2927,
            1889,
            3356,
            3154,
            77,
            3134,
            2631,
            2005,
            3209,
            3127,
            3688,
            2430,
            57,
            951,
            400,
            2839,
            1103,
            3870,
            3614,
            3860,
            3873,
            3626,
            2079,
            2899,
            3387,
            3868,
            1823,
            3265,
            2051,
            2970,
            2544,
            2030,
            2817,
            3254,
            1344,
            3426,
            2783,
            2047,
            3322,
            3097,
            3062,
            278,
            3246,
            2048,
            3390,
            2806,
            2309,
            2801,
            2843,
            124,
            3280,
            1345,
            3407,
            742,
            1332,
            1340,
            1587,
            3296,
            581,
            3452,
            1769,
            3282,
            3545,
            3577,
            3428,
            2993,
            3073,
            3855,
            2213,
            687,
            3227,
            3350,
            3370,
            1189,
            181,
            3194,
            3459,
            3400,
            191,
            2964,
            112,
            2200,
            2297,
            1969,
            2762,
            2893,
            1224,
            2878,
            2224,
            2350,
            2100,
        ];

        $participants = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if (!(null !== $participant->getPersonnage() && \in_array($participant->getPersonnage()->getId(), $personnage_ids))) {
                continue;
            }

            $participants->add($participant);
        }

        return $participants;
    }
}
