<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeRepository::class)]
class Groupe extends BaseGroupe implements \Stringable
{
    /**
     * Contructeur.
     *
     * Défini le nombre de place disponible à 0
     */
    public function __construct()
    {
        $this->setClasseOpen(0);
        $this->setLock(false);
        parent::__construct();
    }

    /**
     * méthode magique transtypage en string.
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Fourni la session d'un groupe relatif à un GN.
     */
    public function getSession(Gn $gn)
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGn() == $gn) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Fourni la prochaine session de jeu.
     */
    public function getNextSession()
    {
        return $this->getGroupeGns()->last();
    }

    /**
     * Fourni les informations pour une session de jeu.
     */
    public function getGroupeGn(Gn $gn)
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGn() == $gn) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Fourni les informations pour une session de jeu.
     */
    public function getGroupeGnById($gnId)
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGn()->getId() == $gnId) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Toutes les importations du groupe.
     */
    public function getImportations(): ArrayCollection
    {
        $ressources = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(array_merge($ressources->toArray(), $territoire->getImportations()->toArray()));
        }

        return $ressources;
    }

    /**
     * Toutes les exporations du groupe.
     */
    public function getExportations(): ArrayCollection
    {
        $ressources = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(array_merge($ressources->toArray(), $territoire->getExportations()->toArray()));
        }

        return $ressources;
    }

    /**
     * Fourni tous les ingrédients obtenu par le groupe grace à ses territoires.
     */
    public function getIngredients(): ArrayCollection
    {
        $ingredients = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ingredients = new ArrayCollection(array_merge($ingredients->toArray(), $territoire->getIngredients()->toArray()));
        }

        return $ingredients;
    }

    /**
     * Fourni une version imprimable du matériel.
     */
    public function getMaterielRaw(): string
    {
        return html_entity_decode(strip_tags($this->getMateriel()));
    }

    /**
     * Fourni la liste des ressources necessaires à un groupe.
     *
     * @param unknown $rarete
     */
    public function getRessourcesNeeded($rarete = null): ArrayCollection
    {
        $ressources = new ArrayCollection();

        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(
                array_unique(
                    array_merge($ressources->toArray(), $territoire->getImportations($rarete)->toArray())
                )
            );
        }

        return $ressources;
    }

    /**
     * Vérifie si un groupe dispose de ressources.
     */
    public function hasRessource(): bool
    {
        foreach ($this->getTerritoires() as $territoire) {
            if ($territoire->getExportations()->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un groupe dispose de richesses.
     */
    public function hasRichesse(): bool
    {
        foreach ($this->getTerritoires() as $territoire) {
            if ($territoire->getTresor() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la richesse totale du groupe (territoire + richesse perso).
     */
    public function getRichesseTotale()
    {
        $richesse = $this->getRichesse();
        foreach ($this->getTerritoires() as $territoire) {
            $richesse += 3 * $territoire->getRichesse();
        }

        return $richesse;
    }

    /**
     * Vérifie si un groupe dispose d'ingrédients.
     */
    public function hasIngredient(): bool
    {
        foreach ($this->getTerritoires() as $territoire) {
            if ($territoire->getIngredients()->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni les backgrounds du groupe en fonction de la visibilitée.
     *
     * @param unknown $visibility
     */
    public function getBacks($visibility = null): ArrayCollection
    {
        $backgrounds = new ArrayCollection();
        foreach ($this->getBackgrounds() as $background) {
            if (null != $visibility) {
                if ($background->getVisibility() == $visibility) {
                    $backgrounds[] = $background;
                }
            } else {
                $backgrounds[] = $background;
            }
        }

        return $backgrounds;
    }

    /**
     * Determine si un groupe est allié avec ce groupe.
     */
    public function isAllyTo(Groupe $groupe): bool
    {
        foreach ($this->getAlliances() as $alliance) {
            if ($alliance->getGroupe() == $groupe
                || $alliance->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine si un groupe est en attente d'alliance avec ce groupe.
     */
    public function isWaitingAlliance(Groupe $groupe): bool
    {
        foreach ($this->getWaitingAlliances() as $alliance) {
            if ($alliance->getGroupe() == $groupe
                || $alliance->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine si un groupe est ennemi avec ce groupe.
     */
    public function isEnemyTo(Groupe $groupe): bool
    {
        foreach ($this->getEnnemies() as $war) {
            if ($war->getGroupe() == $groupe
                || $war->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine si un groupe est ennemi avec ce groupe.
     */
    public function isWaitingPeaceTo(Groupe $groupe): bool
    {
        foreach ($this->getWaitingPeace() as $war) {
            if ($war->getGroupe() == $groupe
                || $war->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste des toutes les alliances de ce groupe.
     */
    public function getAlliances(): ArrayCollection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if ($alliance->getGroupeAccepted() && $alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if ($alliance->getGroupeAccepted() && $alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        return $alliances;
    }

    /**
     * Fourni la liste de toutes les alliances en cours de négotiation.
     */
    public function getWaitingAlliances(): ArrayCollection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if (!$alliance->getGroupeAccepted() || !$alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if (!$alliance->getGroupeAccepted() || !$alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        return $alliances;
    }

    /**
     * Fourni la liste de toutes les demandes d'alliances.
     */
    public function getRequestedAlliances(): ArrayCollection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if (!$alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        return $alliances;
    }

    /**
     * Fourni la liste de toutes les alliances demandés.
     */
    public function getSelfRequestedAlliances(): ArrayCollection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if (!$alliance->getGroupeAllieAccepted()) {
                $alliances[] = $alliance;
            }
        }

        return $alliances;
    }

    /**
     * Fourni tous les ennemis du groupe.
     */
    public function getEnnemies(): ArrayCollection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if (false == $enemy->getGroupePeace() || false == $enemy->getGroupeEnemyPeace()) {
                $enemies[] = $enemy;
            }
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if (false == $enemy->getGroupePeace() || false == $enemy->getGroupeEnemyPeace()) {
                $enemies[] = $enemy;
            }
        }

        return $enemies;
    }

    /**
     * Fourni la liste des anciens ennemis.
     */
    public function getOldEnemies(): ArrayCollection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if (true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()) {
                $enemies[] = $enemy;
            }
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if (true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()) {
                $enemies[] = $enemy;
            }
        }

        return $enemies;
    }

    /**
     * Fournie toutes les negociation de paix en cours.
     */
    public function getWaitingPeace(): ArrayCollection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if ((true == $enemy->getGroupePeace() || true == $enemy->getGroupeEnemyPeace())
                && (!(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()))) {
                $enemies[] = $enemy;
            }
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if ((true == $enemy->getGroupePeace() || true == $enemy->getGroupeEnemyPeace())
                && (!(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()))) {
                $enemies[] = $enemy;
            }
        }

        return $enemies;
    }

    /**
     * Trouve le personnage de l'utilisateur dans ce groupe.
     */
    public function getPersonnage(User $User)
    {
        $participant = $User->getParticipantByGroupe($this);
        if ($participant) {
            return $participant->getPersonnage();
        }

        return null;
    }

    /**
     * Fourni le nombre de place disponible pour un groupe
     * en fonction des territoires qu'il controle.
     */
    public function getPlaceTotal(): int
    {
        return 12 + (2 * count($this->getTerritoires()));
    }

    /**
     * Vérifie si le groupe dispose de suffisement de place disponible.
     */
    public function hasEnoughPlace(): bool
    {
        return $this->getClasseOpen() > count($this->getPersonnages());
    }

    /**
     * Vérifie si le groupe dispose de suffisement de classe disponible.
     */
    public function hasEnoughClasse(Gn $gn): bool
    {
        return count($this->getAvailableClasses($gn)) > 0;
    }

    /**
     * Fourni la liste des classes disponibles (non actuellement utilisé par un personnage)
     * Ce type de liste est utile pour le formulaire de création d'un personnage.
     *
     * @return Collection App\Entity\Classe
     */
    public function getAvailableClasses(Gn $gn): array
    {
        $groupeGn = $this->getGroupeGn($gn);
        $groupeClasses = $this->getGroupeClasses();
        $base = clone $groupeClasses;

        foreach ($groupeGn->getPersonnages() as $personnage) {
            $id = $personnage->getClasse()->getId();

            foreach ($base as $key => $groupeClasse) {
                if ($groupeClasse->getClasse()->getId() == $id) {
                    unset($base[$key]);
                    break;
                }
            }
        }

        $availableClasses = [];

        foreach ($base as $groupeClasse) {
            $availableClasses[] = $groupeClasse->getClasse();
        }

        return $availableClasses;
    }

    /**
     * Get User entity related by `scenariste_id` (many to one).
     *
     * @return \App\Entity\User
     */
    public function getScenariste()
    {
        return $this->getUserRelatedByScenaristeId();
    }

    /**
     * Set User entity related by `scenariste_id` (many to one).
     *
     * @return \App\Entity\Groupe
     */
    public function setScenariste(User $User)
    {
        return $this->setUserRelatedByScenaristeId($User);
    }

    /**
     * Fourni la liste des classes.
     *
     * @return array App\Entity\Classe
     */
    public function getClasses(): array
    {
        $classes = [];
        $groupeClasses = $this->getGroupeClasses();
        foreach ($groupeClasses as $groupeClasse) {
            $classes[] = $groupeClasse->getClasse();
        }

        return $classes;
    }

    /**
     * Ajoute une classe dans le groupe.
     */
    public function addGroupeClass(GroupeClasse $groupeClasse)
    {
        return $this->addGroupeClasse($groupeClasse);
    }

    /**
     * Retire une classe du groupe.
     */
    public function removeGroupeClass(GroupeClasse $groupeClasse)
    {
        return $this->removeGroupeClasse($groupeClasse);
    }
}
