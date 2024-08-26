<?php

namespace App\Entity;

use App\Repository\TerritoireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

/**
 * Je définie les relations ManyToMany içi au lieu de le faire dans Mysql Workbench
 * car l'exporteur ne sait pas gérer correctement plusieurs relations ManyToMany entre
 * les mêmes entities (c'est dommage ...).
 */
#[Entity(repositoryClass: TerritoireRepository::class)]
class Territoire extends BaseTerritoire implements \JsonSerializable, \Stringable
{
    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->setOrdreSocial(3);
        parent::__construct();
    }

    /**
     * Serializer.
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'description' => $this->getDescription(),
            'capitale' => $this->getCapitale(),
            'politique' => $this->getPolitique(),
            'dirigeant' => $this->getDirigeant(),
            'population' => $this->getPopulation(),
            'symbole' => $this->getSymbole(),
            'tech_level' => $this->getTechLevel(),
            'type_racial' => $this->getTypeRacial(),
            'inspiration' => $this->getInspiration(),
            'armes_predilection' => $this->getArmesPredilection(),
            'vetements' => $this->getVetements(),
            'noms_masculin' => $this->getNomsMasculin(),
            'noms_feminin' => $this->getNomsFeminin(),
            'frontieres' => $this->getFrontieres(),
            'religion_id' => ($this->getReligion()) ? $this->getReligion()->getId() : '',
            /*'chronologies'
            'groupes'
            'langue_principale'
            'religion_principale'
            'langues'
            'religions'
            'importations'
            'exporations'*/
        ];
    }

    /**
     * Unserializer.
     *
     * @param unknown $payload
     */
    public function jsonUnserialize($payload): void
    {
        $this->setNom($payload->nom);
        $this->setDescription($payload->description);
        $this->setCapitale($payload->capitale);
        $this->setPolitique($payload->politique);
        $this->setDirigeant($payload->dirigeant);
        $this->setPopulation($payload->population);
        $this->setSymbole($payload->symbole);
        $this->setTechLevel($payload->tech_level);
        $this->setTypeRacial($payload->type_racial);
        $this->setInspiration($payload->inspiration);
        $this->setArmesPredilection($payload->armes_predilection);
        $this->setVetements($payload->vetements);
        $this->setNomsMasculin($payload->noms_masculin);
        $this->setNomsFeminin($payload->noms_feminin);
        $this->setFrontieres($payload->frontieres);
    }

    /**
     * Affichage.
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Determine si un territoire dispose d'une construction.
     *
     * @param unknown $label
     */
    public function hasConstruction($label): bool
    {
        foreach ($this->getConstructions() as $construction) {
            if ($construction->getLabel() == $label) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la richesse d'un territoire, en fonction de son statut (1/2 si instable), et des constructions.
     */
    public function getRichesse()
    {
        $tresor = parent::getTresor();
        if (0 === $tresor) {
            $tresor = 0;
        }

        // TODO ajouter les revenus des bâtiments.
        foreach ($this->getConstructions() as $construction) {
            if (6 == $construction->getId()) { /* Comptoir commercial */
                $tresor += 5;
            }

            if (23 == $construction->getId()) { /* Foyer d'orfèvre */
                $tresor += 10;
            }

            if (10 == $construction->getId()) { /* Port */
                $tresor += 5;
            }
        }

        // gestion de l'état du territoire
        return match ($this->getStatut()) {
            'Normal' => $tresor,
            'Instable' => ceil($tresor / 2),
            default => $tresor,
        };
    }

    /**
     * Fourni la culture d'un territoire ou à défaut la culture du territoire parent.
     */
    public function getCulture(): ?Culture
    {
        if (isset($this->culture)) {
            return parent::getCulture();
        }

        if ($this->getTerritoire()) {
            return $this->getTerritoire()->getCulture();
        }

        return null;
    }

    /**
     * Fourni le nombre de personnages nobles rattachés à ce territoire.
     */
    public function getNbrNoble(): array
    {
        $nobles = [];
        foreach ($this->getGroupesFull() as $groupe) {
            foreach ($groupe->getPersonnages() as $personnage) {
                if ($personnage->hasCompetence('Noblesse')) {
                    $nobles[] = $personnage->getId();
                }
            }
        }

        foreach ($this->getTerritoires() as $territoire) {
            $nobles = array_unique(array_merge($nobles, $territoire->getNbrNoble()));
            /*
            echo "<pre>";
            echo $territoire->getNom()." : ".count($territoire->getNbrNoble())."/".count($nobles);
            echo "</pre>";
            echo "<hr />";
            */
        }

        return array_unique($nobles);
    }

    /**
     * Fourni la defense d'un territoire.
     */
    public function getDefense(): int|float
    {
        $defense = 0;
        if (0 !== $this->getResistance()) {
            $defense += $this->getResistance();
        }

        foreach ($this->getConstructions() as $construction) {
            $defense += $construction->getDefense();
        }

        return $defense;
    }

    /**
     * Fourni le territoire racine.
     */
    public function getRoot()
    {
        if ($this->getTerritoire()) {
            return $this->getTerritoire()->getRoot();
        } else {
            return $this;
        }
    }

    /**
     * Fourni l'arbre des territoires'.
     */
    public function getTree()
    {
        if ($this->getTerritoire()) {
            return $this.' --- '.$this->getTerritoire().' --- '.$this->getTerritoire()->getRoot();
        } else {
            return $this;
        }
    }

    /**
     * Fourni tous les ancêtres d'un territoire.
     */
    public function getAncestors(): Collection
    {
        $ancestors = new ArrayCollection();
        if ($this->getTerritoire()) {
            $ancestors[] = $this->getTerritoire();
            $ancestors = new ArrayCollection(array_merge($ancestors->toArray(), $this->getTerritoire()->getAncestors()->toArray()));
        }

        return $ancestors;
    }

    /**
     * Calcule le nombre d'étape necessaire pour revenir au parent le plus ancien.
     */
    public function stepCount($count = 0)
    {
        if ($this->getTerritoire()) {
            return $this->getTerritoire()->stepCount($count + 1);
        }

        return $count;
    }

    /**
     * Fourni la langue principale du territoire.
     */
    public function getLanguePrincipale()
    {
        return $this->getLangue();
    }

    /**
     * Défini la langue principale du territoire.
     */
    public function setLanguePrincipale(Langue $langue)
    {
        return $this->setLangue($langue);
    }

    /**
     * Fourni la religion principale du territoire.
     */
    public function getReligionPrincipale()
    {
        return $this->getReligion();
    }

    /**
     * Défini la religion principale d'un territoire.
     */
    public function setReligionPrincipale(Religion $religion)
    {
        return $this->setReligion($religion);
    }

    /**
     * Fourni le nom complet d'un territoire.
     */
    public function getNomTree()
    {
        $string = $this->getNom();

        if (0 != $this->getTerritoires()->count()) {
            $string .= ' > ';
            $string .= implode(', ', $this->getTerritoires()->toArray());
        }

        return $string;
    }

    /**
     * Fourni le nom complet d'un territoire.
     */
    public function getNomComplet(): string
    {
        $string = $this->getNom();

        if ($this->getGroupe()) {
            $string .= ' (#'.$this->getGroupe()->getNumero().' '.$this->getGroupe()->getNom().')';
        }

        return $string;
    }

    /**
     * Add Ressource entity to collection.
     *
     * @return Territoire
     */
    public function addExportation(Ressource $ressource): static
    {
        $ressource->addExportateur($this);
        $this->exportations[] = $ressource;

        return $this;
    }

    /**
     * Remove Ressource entity from collection.
     *
     * @return Territoire
     */
    public function removeExportation(Ressource $ressource): static
    {
        $ressource->removeExportateur($this);
        $this->exportations->removeElement($ressource);

        return $this;
    }

    /**
     * Get Ressource entity collection.
     *
     * @return Collection
     */
    public function getExportations()
    {
        return $this->exportations;
    }

    /**
     * Add Ressource entity to collection.
     *
     * @return Territoire
     */
    public function addImportation(Ressource $ressource): static
    {
        $ressource->addImportateur($this);
        $this->importations[] = $ressource;

        return $this;
    }

    /**
     * Remove Ressource entity from collection.
     *
     * @return Territoire
     */
    public function removeImportation(Ressource $ressource): static
    {
        $ressource->removeImportateur($this);
        $this->importations->removeElement($ressource);

        return $this;
    }

    /**
     * Get Ressource entity collection.
     *
     * @return Collection
     */
    public function getImportations($rarete = null)
    {
        if ($rarete) {
            $importations = new ArrayCollection();
            foreach ($this->importations as $ressource) {
                if ($ressource->getRarete()->getLabel() == $rarete) {
                    $importations[] = $ressource;
                }
            }

            return $importations;
        }

        return $this->importations;
    }

    public function getGroupesFull(): array
    {
        $groupes = [];
        if ($this->getGroupe()) {
            $groupes[] = $this->getGroupe();
        }

        foreach ($this->getTerritoires() as $territoire) {
            $groupes = array_merge($groupes, $territoire->getGroupesFull());
        }

        return array_unique($groupes);
    }

    /**
     * Fourni le nom de tous les groupes présents dans ce territoire.
     */
    public function getGroupesNom(): array
    {
        $groupes = [];

        if ($this->getGroupe()) {
            $groupes[] = $this->getGroupe()->getNom();
        }

        foreach ($this->getTerritoires() as $territoire) {
            $groupes = array_merge($groupes, $territoire->getGroupes());
        }

        return array_unique($groupes);
    }

    /**
     * Fourni le nom de tous les groupes de PJ présents dans ce territoire.
     */
    public function getGroupesPj(): array
    {
        $groupes = [];
        if ($this->getGroupe() && $this->getGroupe()->getPj()) {
            $groupes[] = $this->getGroupe()->getNom();
        }

        foreach ($this->getTerritoires() as $territoire) {
            $groupes = array_merge($groupes, $territoire->getGroupesPj());
        }

        return array_unique($groupes);
    }

    /**
     * Fourni l'indicateur d'ordre/Instable.
     */
    public function getStatutIndex(): int
    {
        return match ($this->getStatut()) {
            'Normal' => 0,
            'Instable' => 1,
            default => 0,
        };
    }
}
