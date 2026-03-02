<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: GroupeRepository::class)]
class Groupe extends BaseGroupe implements Stringable
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
    public function getSession(Gn $gn): ?GroupeGn
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGn() === $gn) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Fourni la prochaine session de jeu.
     */
    public function getNextSession(): ?GroupeGn
    {
        return $this->getGroupeGns()->last() ?: null;
    }

    public function getLabel(): string
    {
        return ($this->getNumero() ?: '?') . ' - ' . $this->getNom();
    }

    /**
     * Fourni les informations pour une session de jeu.
     */
    public function getGroupeGnById(int $gnId): ?GroupeGn
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ((int) $groupeGn->getGn()->getId() === $gnId) {
                return $groupeGn;
            }
        }

        return null;
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
     * @return Collection<int, Ressource>
     */
    public function getRessourcesNeeded(?int $rarete = null): Collection
    {
        $ressources = new ArrayCollection();

        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(array_unique(array_merge($ressources->toArray(), $territoire->getImportations($rarete)->toArray())));
        }

        return $ressources;
    }

    /**
     * Toutes les importations du groupe.
     *
     * @return Collection<int, Ressource>
     */
    public function getImportations(): Collection
    {
        $ressources = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(array_merge($ressources->toArray(), $territoire->getImportations()->toArray()));
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
     * Toutes les exporations du groupe.
     *
     * @return Collection<int, Ressource>
     */
    public function getExportations(): Collection
    {
        $ressources = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ressources = new ArrayCollection(array_merge($ressources->toArray(), $territoire->getExportations()->toArray()));
        }

        return $ressources;
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
    public function getRichesseTotale(): int|float
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
     * Fourni tous les ingrédients obtenu par le groupe grace à ses territoires.
     *
     * @return Collection<int, Ingredient>
     */
    public function getIngredients(): Collection
    {
        $ingredients = new ArrayCollection();
        foreach ($this->getTerritoires() as $territoire) {
            $ingredients = new ArrayCollection(array_merge($ingredients->toArray(), $territoire->getIngredients()->toArray()));
        }

        return $ingredients;
    }

    /**
     * Fourni les backgrounds du groupe en fonction de la visibilitée.
     *
     * @return Collection<int, object>
     */
    public function getBacks(?string $visibility = null): Collection
    {
        $backgrounds = new ArrayCollection();
        foreach ($this->getBackgrounds() as $background) {
            if (null !== $visibility) {
                if ($background->getVisibility()->value === $visibility) {
                    $backgrounds->add($background);
                }
            } else {
                $backgrounds->add($background);
            }
        }

        return $backgrounds;
    }

    /**
     * Determine si un groupe est allié avec ce groupe.
     */
    public function isAllyTo(self $groupe): bool
    {
        foreach ($this->getAlliances() as $alliance) {
            if ($alliance->getGroupe() == $groupe || $alliance->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste des toutes les alliances de ce groupe.
     *
     * @return Collection<int, object>
     */
    public function getAlliances(): Collection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if (!($alliance->getGroupeAccepted() && $alliance->getGroupeAllieAccepted())) {
                continue;
            }

            $alliances->add($alliance);
        }

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if (!($alliance->getGroupeAccepted() && $alliance->getGroupeAllieAccepted())) {
                continue;
            }

            $alliances->add($alliance);
        }

        return $alliances;
    }

    /**
     * Determine si un groupe est en attente d'alliance avec ce groupe.
     */
    public function isWaitingAlliance(self $groupe): bool
    {
        foreach ($this->getWaitingAlliances() as $alliance) {
            if ($alliance->getGroupe() == $groupe || $alliance->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste de toutes les alliances en cours de négotiation.
     *
     * @return Collection<int, object>
     */
    public function getWaitingAlliances(): Collection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if (!(!$alliance->getGroupeAccepted() || !$alliance->getGroupeAllieAccepted())) {
                continue;
            }

            $alliances->add($alliance);
        }

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if (!(!$alliance->getGroupeAccepted() || !$alliance->getGroupeAllieAccepted())) {
                continue;
            }

            $alliances->add($alliance);
        }

        return $alliances;
    }

    /**
     * Determine si un groupe est ennemi avec ce groupe.
     */
    public function isEnemyTo(self $groupe): bool
    {
        foreach ($this->getEnnemies() as $war) {
            if ($war->getGroupe() == $groupe || $war->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni tous les ennemis du groupe.
     *
     * @return Collection<int, object>
     */
    public function getEnnemies(): Collection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if (!(false == $enemy->getGroupePeace() || false == $enemy->getGroupeEnemyPeace())) {
                continue;
            }

            $enemies->add($enemy);
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if (!(false == $enemy->getGroupePeace() || false == $enemy->getGroupeEnemyPeace())) {
                continue;
            }

            $enemies->add($enemy);
        }

        return $enemies;
    }

    /**
     * Determine si un groupe est ennemi avec ce groupe.
     */
    public function isWaitingPeaceTo(self $groupe): bool
    {
        foreach ($this->getWaitingPeace() as $war) {
            if ($war->getGroupe() == $groupe || $war->getRequestedGroupe() == $groupe) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fournie toutes les negociation de paix en cours.
     *
     * @return Collection<int, object>
     */
    public function getWaitingPeace(): Collection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if (!((true == $enemy->getGroupePeace() || true == $enemy->getGroupeEnemyPeace()) && !(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()))) {
                continue;
            }

            $enemies->add($enemy);
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if (!((true == $enemy->getGroupePeace() || true == $enemy->getGroupeEnemyPeace()) && !(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace()))) {
                continue;
            }

            $enemies->add($enemy);
        }

        return $enemies;
    }

    /**
     * Fourni la liste de toutes les demandes d'alliances.
     *
     * @return Collection<int, object>
     */
    public function getRequestedAlliances(): Collection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeAllieIds as $alliance) {
            if ($alliance->getGroupeAllieAccepted()) {
                continue;
            }

            $alliances->add($alliance);
        }

        return $alliances;
    }

    /**
     * Fourni la liste de toutes les alliances demandés.
     *
     * @return Collection<int, object>
     */
    public function getSelfRequestedAlliances(): Collection
    {
        $alliances = new ArrayCollection();

        foreach ($this->groupeAllieRelatedByGroupeIds as $alliance) {
            if ($alliance->getGroupeAllieAccepted()) {
                continue;
            }

            $alliances->add($alliance);
        }

        return $alliances;
    }

    /**
     * Fourni la liste des anciens ennemis.
     *
     * @return Collection<int, object>
     */
    public function getOldEnemies(): Collection
    {
        $enemies = new ArrayCollection();

        foreach ($this->groupeEnemyRelatedByGroupeIds as $enemy) {
            if (!(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace())) {
                continue;
            }

            $enemies->add($enemy);
        }

        foreach ($this->groupeEnemyRelatedByGroupeEnemyIds as $enemy) {
            if (!(true == $enemy->getGroupePeace() && true == $enemy->getGroupeEnemyPeace())) {
                continue;
            }

            $enemies->add($enemy);
        }

        return $enemies;
    }

    /**
     * Trouve le personnage de l'utilisateur dans ce groupe.
     */
    public function getPersonnage(User $user): ?Personnage
    {
        $participant = $user->getParticipantByGroupe($this);
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
        return 12 + (2 * \count($this->getTerritoires()));
    }

    /**
     * Vérifie si le groupe dispose de suffisement de place disponible.
     */
    public function hasEnoughPlace(): bool
    {
        return $this->getClasseOpen() > \count($this->getPersonnages());
    }

    /**
     * Vérifie si le groupe dispose de suffisement de classe disponible.
     */
    public function hasEnoughClasse(Gn $gn): bool
    {
        return \count($this->getAvailableClasses($gn)) > 0;
    }

    /**
     * Fourni la liste des classes disponibles (non actuellement utilisé par un personnage)
     * Ce type de liste est utile pour le formulaire de création d'un personnage.
     */
    /**
     * @return array<int, object>
     */
    public function getAvailableClasses(Gn $gn): array
    {
        $groupeGn = $this->getGroupeGn($gn);
        $groupeClasses = $this->getGroupeClasses();
        $base = clone $groupeClasses;

        foreach ($groupeGn->getPersonnages() as $personnage) {
            $id = $personnage->getClasse()->getId();

            foreach ($base as $key => $groupeClasse) {
                if ($groupeClasse->getClasse()->getId() != $id) {
                    continue;
                }

                unset($base[$key]);
                break;
            }
        }

        $availableClasses = [];

        foreach ($base as $groupeClasse) {
            $availableClasses[] = $groupeClasse->getClasse();
        }

        return $availableClasses;
    }

    /**
     * Fourni les informations pour une session de jeu.
     */
    public function getGroupeGn(Gn $gn): ?GroupeGn
    {
        foreach ($this->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getGn() == $gn) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Get User entity related by `scenariste_id` (many to one).
     *
     * @return User
     */
    public function getScenariste()
    {
        return $this->getUserRelatedByScenaristeId();
    }

    /**
     * Set User entity related by `scenariste_id` (many to one).
     *
     * @return Groupe
     */
    public function setScenariste(User $User)
    {
        return $this->setUserRelatedByScenaristeId($User);
    }

    /**
     * Fourni la liste des classes.
     *
     * @return array<int, object>
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
    public function addGroupeClass(GroupeClasse $groupeClasse): static
    {
        return $this->addGroupeClasse($groupeClasse);
    }

    /**
     * Retire une classe du groupe.
     */
    public function removeGroupeClass(GroupeClasse $groupeClasse): static
    {
        return $this->removeGroupeClasse($groupeClasse);
    }
}
