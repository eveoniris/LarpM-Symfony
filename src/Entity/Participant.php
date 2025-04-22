<?php

namespace App\Entity;

use App\Entity\Groupe;
use Doctrine\ORM\Mapping\Entity;
use App\Repository\ParticipantRepository;

#[Entity(repositoryClass: ParticipantRepository::class)]
class Participant extends BaseParticipant implements \Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setSubscriptionDate(new \DateTime('NOW'));
    }

    public function __toString(): string
    {
        return (string) $this->getUser()?->getDisplayName();
    }

    /**
     * Verifie si le participant a répondu à cette question.
     */
    public function asAnswser(Question $q): bool
    {
        foreach ($this->getReponses() as $reponse) {
            if ($reponse->getQuestion() == $q) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le joueur est responsable du groupe.
     */
    public function isResponsable(Groupe $groupe, GroupeGn $groupeGn): bool
    {
        foreach ($groupe->getGroupeGns() as $session) {
            if ($groupeGn->getId() === $session->getId() && $this->getGroupeGns()->contains($session)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la session de jeu auquel participe l'utilisateur.
     */
    public function getSession()
    {
        return $this->getGroupeGn();
    }

    /**
     * Retire un participant d'un groupe.
     */
    public function setGroupeGnNull(): static
    {
        $this->setGroupeGn(null);

        return $this;
    }

    /**
     * Retire un personnage du participant.
     */
    public function setPersonnageNull(): static
    {
        $this->setPersonnage(null);

        return $this;
    }

    public function getUserIdentity(): string
    {
        return $this->getUser()?->getDisplayName().' '.$this->getUser()?->getEmail();
    }

    public function getBesoinValidationCi(): bool
    {
        return $this->getGn()->getBesoinValidationCi() && null == $this->getValideCiLe();
    }

    /**
     * Retourne le groupe du groupe gn associé.
     */
    public function getGroupe(): ?Groupe
    {
        if (null !== $this->getGroupeGn()) {
            return $this->getGroupeGn()->getGroupe();
        }

        return null;
    }

    /**
     * Retourne true si le participant a un billet PNJ, false sinon.
     */
    public function isPnj(): bool
    {
        if ($this->getBillet()) {
            return $this->getBillet()->isPnj();
        }

        return false;
    }

    /**
     * Retourne le nom complet de l'utilisateur (nom prénom).
     */
    public function getUserFullName(): string
    {
        return $this->getUser()->getFullName();
    }

    public function getAgeJoueur(): int
    {
        $gn_date = $this->getGn()->getDateFin();
        $naissance = $this->getUser()->getEtatCivil()->getDateNaissance();
        $interval = date_diff($gn_date, $naissance);

        return (int) $interval->format('%y');
    }

    public function hasPotionsDepartByLevel(int $niveau = 1): ?Potion
    {
        foreach ($this->getPotionsDepart() as $potion) {
            if ($potion->getNiveau() === $niveau) {
                return $potion;
            }
        }

        return null;
    }

    public function getPotionsDepartByLevel($niveau = 1)
    {
        $return = false;
        foreach ($this->getPotionsDepart() as $potion) {
            if ($potion->getNiveau() == $niveau) {
                $return = $potion;
            }
        }

        if (false === $return) {
            $return = $this->getPotionsRandomByLevel($niveau);
        }

        return $return;
    }

    public function hasPotionsDepart(Potion $potionDepart): bool
    {
        /** @var Potion $potion */
        foreach ($this->getPotionsDepart() as $potion) {
            if ($potion->getNumero() === $potionDepart->getNumero()) {
                return true;
            }
        }

        return false;
    }

    public function getPotionsRandomByLevel($niveau = 1)
    {
        $potions = [];
        foreach ($this->getPersonnage()->getPotions() as $potion) {
            if ($potion->getNiveau() == $niveau) {
                $potions[] = $potion;
            }
        }

        return $potions[rand(0, count($potions) - 1)];
    }

    /**
     * @return mixed[]
     */
    public function getPotionsEnveloppe(): array
    {
        $niveauMax = $this->getPersonnage()->getCompetenceNiveau('Alchimie');
        $i = 1;
        $potions = [];
        while ($i <= $niveauMax) {
            $potions[] = $this->getPotionsDepartByLevel($i);
            ++$i;
        }

        return $potions;
    }
}
