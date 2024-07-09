<?php

namespace App\Entity;

use App\Repository\PersonnageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: PersonnageRepository::class)]
class Personnage extends BasePersonnage implements \Stringable
{
    /**
     * Constructeur.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setXp(0);
        $this->setVivant(true);
    }

    /**
     * Vérifie si un personnage connait une priere.
     */
    public function hasPriere(Priere $priere): bool
    {
        foreach ($this->getPrieres() as $p) {
            if ($p == $priere) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni tous les gns auquel participe un personnage.
     */
    public function getGns(): Collection
    {
        $gns = new ArrayCollection();

        if ($this->getUser()) {
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getBillet()) {
                    $gns[] = $participant->getGn();
                }
            }
        }

        return $gns;
    }

    /**
     * Détermine si le personnage participe à un GN.
     */
    public function participeTo(Gn $gn): bool
    {
        if ($this->getUser() && ($participant = $this->getUser()->getParticipant($gn))) {
            if ($participant->getBillet()
                && $participant->getPersonnage() == $this) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détermine si le personnage participe à un GN.
     *
     * @param unknown $gnLabel
     */
    public function participeToByLabel($gnLabel): bool
    {
        if ($this->getUser()) {
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getBillet()
                    && $participant->getGn()->getLabel() == $gnLabel
                    && $participant->getPersonnage() == $this) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retire le personnage de son groupe.
     */
    public function setGroupeNull(): static
    {
        $this->groupe = null;

        return $this;
    }

    /**
     * Affichage.
     */
    public function __toString(): string
    {
        return (string) $this->getPublicName();
    }

    /**
     * Fourni l'origine du personnage.
     */
    public function getOrigine()
    {
        return $this->getTerritoire();
    }

    /**
     * Détermine si du matériel est necessaire pour ce personnage.
     */
    public function hasMateriel(): bool
    {
        if ($this->getRenomme() > 0) {
            return true;
        }

        foreach ($this->getCompetences() as $competence) {
            if ($competence->getMateriel()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni les backgrounds du personnage en fonction de la visibilitée.
     *
     * @param unknown $visibility
     */
    public function getBackgrounds($visibility = null): Collection
    {
        $backgrounds = new ArrayCollection();
        foreach ($this->getPersonnageBackgrounds() as $background) {
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
     * Vérifie si le personnage connait cette priere.
     */
    public function isKnownPriere(Priere $p): bool
    {
        foreach ($this->getPrieres() as $priere) {
            if ($priere == $p) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage connait cette potion.
     */
    public function isKnownPotion(Potion $p): bool
    {
        foreach ($this->getPotions() as $potion) {
            if ($potion == $p) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getPotionsNiveau($niveau): array
    {
        $potions = [];
        foreach ($this->getPotions() as $potion) {
            if ($potion->getNiveau() == $niveau) {
                $potions[] = $potion;
            }
        }

        return $potions;
    }

    /**
     * Contrôle si le personnage connait le bon nombre de potion.
     *
     * @return non vide ,si il y a une anomalie
     */
    public function getPotionAnomalieMessage(): string
    {
        $countByLevel = [0, 0, 0, 0];
        foreach ($this->getPotions() as $potion) {
            ++$countByLevel[$potion->getNiveau() - 1];
        }

        $litteratureApprenti = null;
        $expectedByLevel = [0, 0, 0, 0];
        foreach ($this->getCompetences() as $competence) {
            for ($i = 0; $i < 4; ++$i) {
                $v = $competence->getAttributeValue(AttributeType::$POTIONS[$i]);
                if (null != $v) {
                    $expectedByLevel[$i] += $v;
                }
            }

            if ($competence->getCompetenceFamily()->getLabel() == CompetenceFamily::$LITTERATURE
                && 1 == $competence->getLevel()->getIndex()) {
                $litteratureApprenti = $competence;
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            // error_log($this->nom . " PA " . $expectedByLevel[$i] . " " . $countByLevel[$i]);
            if (null == $litteratureApprenti && $expectedByLevel[$i] < $countByLevel[$i]) {
                return ($countByLevel[$i] - $expectedByLevel[$i]).' potion(s) de niveau '.($i + 1).' en trop à vérifier ';
            }

            if ($expectedByLevel[$i] > $countByLevel[$i]) {
                return ($expectedByLevel[$i] - $countByLevel[$i]).' potion(s) de niveau '.($i + 1).' manquante(s)';
            }
        }

        return '';
    }

    /**
     * Vérifie si le personnage connait cette connaissance.
     */
    public function isKnownConnaissance(Connaissance $c): bool
    {
        foreach ($this->getConnaissances() as $connaissance) {
            if ($connaissance == $c) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage connait ce sort.
     */
    public function isKnownSort(Sort $s): bool
    {
        foreach ($this->getSorts() as $sort) {
            if ($sort == $s) {
                return true;
            }
        }

        return false;
    }

    public function getSortAnomalieMessage(): string
    {
        $countByLevel = [0, 0, 0, 0];
        foreach ($this->getSorts() as $sort) {
            ++$countByLevel[$sort->getNiveau() - 1];
        }

        $litteratureApprenti = null;

        // On cumule dans $expectedByLevel , le nombre de sorts attendu
        $expectedByLevel = [0, 0, 0, 0];
        foreach ($this->getCompetences() as $competence) {
            for ($i = 0; $i < 4; ++$i) {
                $v = $competence->getAttributeValue(AttributeType::$SORTS[$i]);
                if (null != $v) {
                    $expectedByLevel[$i] += $v;
                }

                if ($competence->getCompetenceFamily()->getLabel() == CompetenceFamily::$LITTERATURE
                    && 1 == $competence->getLevel()->getIndex()) {
                    $litteratureApprenti = $competence;
                }
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            if (null == $litteratureApprenti && $expectedByLevel[$i] < $countByLevel[$i]) {
                return ($countByLevel[$i] - $expectedByLevel[$i]).' sort(s) de niveau '.($i + 1).' en trop à vérifier ';
            }

            if ($expectedByLevel[$i] > $countByLevel[$i]) {
                return ($expectedByLevel[$i] - $countByLevel[$i]).' sort(s) de niveau '.($i + 1).' manquant';
            }
        }

        return '';
    }

    /**
     * Contrôle si il y a une anomalie dans le nombre de prière.
     *
     * @return non vide ,si il y a une anomalie
     */
    public function getPrieresAnomalieMessage(): string
    {
        $countByLevel = [0, 0, 0, 0];
        foreach ($this->getPrieres() as $sort) {
            ++$countByLevel[$sort->getNiveau() - 1];
        }

        // On cumule dans $expectedByLevel , le nombre de sorts attendu
        $expectedByLevel = [0, 0, 0, 0];
        foreach ($this->getCompetences() as $competence) {
            for ($i = 0; $i < 4; ++$i) {
                $v = $competence->getAttributeValue(AttributeType::$PRIERES[$i]);
                if (null != $v) {
                    $expectedByLevel[$i] += $v;
                }
            }
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($expectedByLevel[$i] < $countByLevel[$i]) {
                return ($countByLevel[$i] - $expectedByLevel[$i]).' prière(s) de niveau '.($i + 1).' en trop à vérifier ';
            }

            if ($expectedByLevel[$i] > $countByLevel[$i]) {
                return ($expectedByLevel[$i] - $countByLevel[$i]).' prière(s) de niveau '.($i + 1).' manquant';
            }
        }

        return '';
    }

    /**
     * Vérifie si le personnage connait ce domaine de magie.
     */
    public function isKnownDomaine(Domaine $d): bool
    {
        foreach ($this->getDomaines() as $domaine) {
            if ($domaine == $d) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage connait cette competence.
     *
     * @param Competence $competence
     */
    public function isKnownCompetence($competence): bool
    {
        foreach ($this->getCompetences() as $c) {
            if ($competence == $c) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage connait cette langue.
     *
     * @param unknown $langue
     */
    public function isKnownLanguage($langue): bool
    {
        foreach ($this->getPersonnageLangues() as $personnageLangue) {
            if ($personnageLangue->getLangue() === $langue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste des langues connues.
     */
    public function getLanguages(): Collection
    {
        $languages = new ArrayCollection();
        foreach ($this->getPersonnageLangues() as $personnageLangue) {
            $languages[] = $personnageLangue->getLangue();
        }

        return $languages;
    }

    /**
     * Retourne les anomalies entre le nombre de langues autorisées et le nombre de langues affectées.
     *
     * @return \Doctrine\Common\Collections\Collection|null
     */
    public function getLanguesAnomaliesMessage(): string
    {
        // On compte les langues connues
        $compteLangue = 0;
        $compteLangueAncienne = 0;
        $maxLangueConnue = 0;
        $label = '';
        foreach ($this->getPersonnageLangues() as $personnageLangue) {
            $label = $label.' '.$personnageLangue->getLangue();
            if (str_starts_with((string) $personnageLangue->getLangue(), 'Ancien')) {
                ++$compteLangueAncienne;
            } else {
                ++$compteLangue;
            }

            $source = $personnageLangue->getSource();
            $baseSources = ['ORIGINE', 'GROUPE', 'ORIGINE et GROUPE'];
            if (in_array($source, $baseSources)) {
                ++$maxLangueConnue;
            }
        }

        // On parcourt les compétences pour compter le nombre de langues & langues anciennes qui devraient être connues.
        $maxLangueAncienneConnue = 0;
        foreach ($this->getCompetences() as $competence) {
            $lc = $competence->getAttributeValue(AttributeType::$LANGUE);
            if (null != $lc) {
                $maxLangueConnue += $lc;
            }

            $lc = $competence->getAttributeValue(AttributeType::$LANGUE_ANCIENNE);
            if (null != $lc) {
                $maxLangueAncienneConnue += $lc;
            }
        }

        // On génère le message de restitution de l'anomalie.
        $return = '';
        if ($compteLangue > $maxLangueConnue) {
            $return .= ($compteLangue - $maxLangueConnue).' langue(s) en trop à vérifier';
        } elseif ($compteLangue < $maxLangueConnue) {
            $return .= ($maxLangueConnue - $compteLangue).' langue(s) manquante(s)';
        }

        if ('' != $return) {
            $return .= ', ';
        }

        if ($maxLangueAncienneConnue < $compteLangueAncienne) {
            $return .= ($compteLangueAncienne - $maxLangueAncienneConnue).' langue(s) ancienne(s) en trop à vérifier';
        } elseif ($maxLangueAncienneConnue > $compteLangueAncienne) {
            $return .= ($maxLangueAncienneConnue - $compteLangueAncienne).' langue(s) ancienne(s) en manquante(s)';
        }

        return $return;
    }

    /**
     * Fourni le language.
     *
     * @param unknown $langue
     */
    public function getPersonnageLangue($langue)
    {
        foreach ($this->getPersonnageLangues() as $personnageLangue) {
            if ($personnageLangue->getLangue() == $langue) {
                return $personnageLangue;
            }
        }

        return null;
    }

    /**
     * Vérifie si le personnage dispose d'un trigger.
     *
     * @param unknown $tag
     */
    public function hasTrigger($tag): bool
    {
        foreach ($this->getPersonnageTriggers() as $personnageTrigger) {
            if ($personnageTrigger->getTag() == $tag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage dispose d'une compétence (quelque soit son niveau).
     *
     * @param unknown $label
     */
    public function hasCompetence($label): bool
    {
        foreach ($this->getCompetences() as $competence) {
            if ($competence->getCompetenceFamily()->getLabel() == $label) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni le niveau d'une compétence d'un personnage.
     */
    public function getCompetenceNiveau(string $label): int
    {
        $niveau = 0;
        foreach ($this->getCompetences() as $competence) {
            if ($competence->getCompetenceFamily()->getLabel() === $label && $niveau < $competence->getLevel()->getIndex()) {
                $niveau = $competence->getLevel()->getIndex();
            }
        }

        return $niveau;
    }

    /**
     * Fourni le niveau d'une compétence pour le score de pugilat.
     *
     * @param unknown $label
     */
    public function getCompetencePugilat($label): int|float
    {
        $niveau = 0;
        foreach ($this->getCompetences() as $competence) {
            if ($competence->getCompetenceFamily()->getLabel() == $label) {
                $niveau += $competence->getLevel()->getIndex();
            }
        }

        return $niveau;
    }

    /**
     * Fourni le score de pugilat.
     */
    public function getPugilat(): int|float
    {
        $pugilat = 1
            + $this->getCompetencePugilat('Agilité')
            + $this->getCompetencePugilat('Armes à distance')
            + $this->getCompetencePugilat('Armes à 1 main')
            + $this->getCompetencePugilat('Armes à 2 mains')
            + $this->getCompetencePugilat("Armes d'hast")
            + $this->getCompetencePugilat('Armure')
            + $this->getCompetencePugilat('Attaque sournoise')
            + $this->getCompetencePugilat('Protection')
            + $this->getCompetencePugilat('Résistance')
            + $this->getCompetencePugilat('Sauvagerie')
            + $this->getCompetencePugilat('Stratégie')
            + $this->getCompetencePugilat('Survie');

        // Forge au niveau Initié ajoute 5 points
        if ($this->getCompetenceNiveau('Forge') >= 2) {
            $pugilat += 5;
        }

        // Sauvegerie au niveau Initié ajoute 5 points
        if ($this->getCompetenceNiveau('Sauvagerie') >= 2) {
            $pugilat += 5;
        }

        foreach ($this->getPugilatHistories() as $pugilatHistory) {
            $pugilat += $pugilatHistory->getPugilat();
        }

        return $pugilat;
    }

    /**
     * Fourni le détail de pugilat à afficher.
     */
    public function getDisplayPugilat(): array
    {
        $pugilatHistories = [];

        $pugilatHistory = new PugilatHistory();
        $pugilatHistory->setPugilat(1);
        $pugilatHistory->setExplication('Score de base');
        $pugilatHistories[] = $pugilatHistory;

        foreach ($this->getPugilatHistories() as $pugilatHistory) {
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Agilité') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Agilité'));
            $pugilatHistory->setExplication('Compétence Agilité niveau '.$this->getCompetenceNiveau('Agilité'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armes à distance') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armes à distance'));
            $pugilatHistory->setExplication('Compétence Armes à distance niveau '.$this->getCompetenceNiveau('Armes à distance'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armes à 1 main') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armes à 1 main'));
            $pugilatHistory->setExplication('Compétence Armes à 1 main niveau '.$this->getCompetenceNiveau('Armes à 1 main'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armes à 2 mains') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armes à 2 mains'));
            $pugilatHistory->setExplication('Compétence Armes à 2 mains niveau '.$this->getCompetenceNiveau('Armes à 2 mains'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat("Armes d'hast") > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat("Armes d'hast"));
            $pugilatHistory->setExplication('Compétence Armes d\'hast niveau '.$this->getCompetenceNiveau("Armes d'hast"));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armure') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armure'));
            $pugilatHistory->setExplication('Compétence Armure niveau '.$this->getCompetenceNiveau('Armure'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetenceNiveau('Forge') >= 2) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat(5);
            $pugilatHistory->setExplication('Compétence Forge niveau '.$this->getCompetenceNiveau('Forge'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Attaque sournoise') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Attaque sournoise'));
            $pugilatHistory->setExplication('Compétence Attaque sournoise niveau '.$this->getCompetenceNiveau('Attaque sournoise'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Protection') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Protection'));
            $pugilatHistory->setExplication('Compétence Protection niveau '.$this->getCompetenceNiveau('Protection'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Résistance') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Résistance'));
            $pugilatHistory->setExplication('Compétence Résistance niveau '.$this->getCompetenceNiveau('Résistance'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Sauvagerie') > 0) {
            $pugilatHistory = new PugilatHistory();
            $extra = 0;
            if ($this->getCompetenceNiveau('Sauvagerie') >= 2) {
                $extra = 5;
            }

            $pugilatHistory->setPugilat($extra + $this->getCompetencePugilat('Sauvagerie'));
            $pugilatHistory->setExplication('Compétence Sauvagerie niveau '.$this->getCompetenceNiveau('Sauvagerie'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Stratégie') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Stratégie'));
            $pugilatHistory->setExplication('Compétence Stratégie niveau '.$this->getCompetenceNiveau('Stratégie'));
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Survie') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Survie'));
            $pugilatHistory->setExplication('Compétence Survie niveau '.$this->getCompetenceNiveau('Survie'));
            $pugilatHistories[] = $pugilatHistory;
        }

        return $pugilatHistories;
    }

    /**
     * Fourni le score d'héroïsme.
     */
    public function getHeroisme(): int
    {
        $heroisme = 0;

        if ($this->getCompetenceNiveau('Agilité') >= 2) {
            ++$heroisme;
        }

        if ($this->getCompetenceNiveau('Armes à 1 main') >= 3) {
            ++$heroisme;
        }

        if ($this->getCompetenceNiveau('Armes à 2 mains') >= 2) {
            ++$heroisme;
        }

        if ($this->getCompetenceNiveau('Protection') >= 4) {
            ++$heroisme;
        }

        if ($this->getCompetenceNiveau('Sauvagerie') >= 1) {
            ++$heroisme;
        }

        foreach ($this->getHeroismeHistories() as $heroismeHistory) {
            $heroisme += $heroismeHistory->getHeroisme();
        }

        return $heroisme;
    }

    /**
     * Fourni le détail d'héroïsme à afficher.
     *
     * @return mixed[]
     */
    public function getDisplayHeroisme(): array
    {
        $heroismeHistories = [];

        foreach ($this->getHeroismeHistories() as $heroismeHistory) {
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Agilité') >= 2) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Agilité niveau '.$this->getCompetenceNiveau('Agilité'));
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Armes à 1 main') >= 3) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Armes à 1 main niveau '.$this->getCompetenceNiveau('Armes à 1 main'));
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Armes à 2 mains') >= 2) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Armes à 2 mains niveau '.$this->getCompetenceNiveau('Armes à 2 mains'));
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Forge') >= 4) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Forge niveau '.$this->getCompetenceNiveau('Forge'));
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Protection') >= 4) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Protection niveau '.$this->getCompetenceNiveau('Protection'));
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Sauvagerie') >= 1) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication('Compétence Sauvagerie niveau '.$this->getCompetenceNiveau('Sauvagerie'));
            $heroismeHistories[] = $heroismeHistory;
        }

        return $heroismeHistories;
    }

    /**
     * Fourni le score de Renommée.
     *
     * @override BasePersonnage::getRenomme()
     */
    public function getRenomme(): int
    {
        // $renomme = $this->renomme ?? 0;
        $renomme = 0;

        foreach ($this->getRenommeHistories() as $renommeHistory) {
            $renomme += $renommeHistory->getRenomme();
        }

        return $renomme;
    }

    /**
     * Fourni le trigger correspondant au tag.
     *
     * @param unknown $tag
     */
    public function getTrigger($tag)
    {
        foreach ($this->getPersonnageTriggers() as $personnageTrigger) {
            if ($personnageTrigger->getTag() == $tag) {
                return $personnageTrigger;
            }
        }

        return null;
    }

    /**
     * Fourni le surnom si celui-ci a été précisé
     * sinon fourni le nom.
     */
    public function getPublicName()
    {
        if ('' !== $this->getSurnom() && '0' !== $this->getSurnom()) {
            return $this->getSurnom();
        }

        return $this->getNom();
    }

    /**
     * Fourni l'identité complete d'un personnage.
     */
    public function getIdentity(): string
    {
        $groupeLabel = null;
        $nomGn = '???';
        if ($this->getUser()) {
            dump('User = '.$this->getUser()->getUsername());
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getPersonnage() == $this) {
                    $nomGn = $participant->getGn()->getLabel();
                    dump('NomGn = '.$nomGn);
                    $groupeGn = $participant->getGroupeGn();
                    if (null != $groupeGn) {
                        dump('groupeGnId = '.$groupeGn->getId());
                        dump('groupeGnCode = '.$groupeGn->getCode());
                        $groupeLabel = $groupeGn->getGroupe()->getNom();
                    }
                }
            }
        }

        $identity = $this->getNom().' - '.$this->getSurnom().' (';
        if ($groupeLabel) {
            $identity .= $nomGn.' - '.$groupeLabel;
        } else {
            $identity .= $nomGn.' - *** GROUPE NON IDENTIFIABLE ***';
        }

        return $identity.')';
    }

    /**
     * Fourni l'identité publique d'un personnage.
     */
    public function getPublicIdentity(): string
    {
        $groupeLabel = null;
        $nomGn = '???';
        if ($this->getUser()) {
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getPersonnage() == $this) {
                    $nomGn = $participant->getGn()->getLabel();
                    $groupeGn = $participant->getGroupeGn();
                    if (null != $groupeGn) {
                        $groupeLabel = $groupeGn->getGroupe()->getNom();
                    }
                }
            }
        }

        $identity = $this->getPublicName().' (';
        if ($groupeLabel) {
            $identity .= $nomGn.' - '.$groupeLabel;
        } else {
            $identity .= $nomGn.' - *** GROUPE NON IDENTIFIABLE ***';
        }

        return $identity.')';
    }

    public function getGroupeByLabel($gnLabel)
    {
        if ($this->getUser()) {
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getBillet()
                    && $participant->getGn()->getLabel() == $gnLabel
                    && $participant->getPersonnage() == $this) {
                    return $participant->getGroupeGn()->getGroupe();
                }
            }
        }

        return null;
    }

    public function getIdentityByLabel($gnLabel): string
    {
        $groupeLabel = null;
        $nomGn = $gnLabel;
        if ($this->getUser()) {
            foreach ($this->getUser()->getParticipants() as $participant) {
                if ($participant->getBillet()
                    && $participant->getGn()->getLabel() == $gnLabel
                    && $participant->getPersonnage() == $this) {
                    $groupeGn = $participant->getGroupeGn();
                    if (null != $groupeGn) {
                        $groupeLabel = $groupeGn->getGroupe()->getNom();
                    }
                }
            }
        }

        $identity = $this->getPublicName().' (';
        if ($groupeLabel) {
            $identity .= $nomGn.' - '.$groupeLabel;
        } else {
            $identity .= $nomGn.' - *** GROUPE NON IDENTIFIABLE ***';
        }

        return $identity.')';
    }

    /**
     * Indique si le personnage est un Fanatique.
     */
    public function isFanatique(): bool
    {
        $personnagesReligions = $this->getPersonnagesReligions();
        foreach ($personnagesReligions as $personnageReligion) {
            if (3 == $personnageReligion->getReligionLevel()->getIndex()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si le personnage est un Fervent.
     */
    public function isFervent(): bool
    {
        $personnagesReligions = $this->getPersonnagesReligions();
        foreach ($personnagesReligions as $personnageReligion) {
            if (2 == $personnageReligion->getReligionLevel()->getIndex()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si le personnage est Croyant dans une religion.
     */
    public function isKnownReligion($religion): bool
    {
        $personnagesReligions = $this->getPersonnagesReligions();
        foreach ($personnagesReligions as $personnageReligion) {
            if ($personnageReligion->getReligion() == $religion) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la religion principale du personnage.
     */
    public function getMainReligion()
    {
        $religion = null;
        $index = 0;
        $personnagesReligions = $this->getPersonnagesReligions();
        foreach ($personnagesReligions as $personnageReligion) {
            if (!$religion) {
                $religion = $personnageReligion->getReligion();
                $index = $personnageReligion->getReligionLevel()->getIndex();
            } elseif ($index < $personnageReligion->getReligionLevel()->getIndex()) {
                $religion = $personnageReligion->getReligion();
                $index = $personnageReligion->getReligionLevel()->getIndex();
            }
        }

        return $religion;
    }

    /**
     * Fourni la liste des groupes secondaires pour lesquel ce personnage est chef.
     */
    public function getSecondaryGroupsAsChief()
    {
        return $this->getSecondaryGroups();
    }

    /**
     * Fourni la description du membre correspondant au groupe passé en paramètre.
     */
    public function getMembre(SecondaryGroup $groupe)
    {
        foreach ($this->getMembres() as $membre) {
            if ($membre->getSecondaryGroup() == $groupe) {
                return $membre;
            }
        }

        return false;
    }

    /**
     * Ajoute des points d'experience à un personnage.
     *
     * @param int $xp
     */
    public function addXp($xp): static
    {
        $this->setXp($this->getXp() + $xp);

        return $this;
    }

    /**
     * Retire des points d'expérience à un personnage.
     *
     * @param int $xp
     */
    public function removeXp($xp): static
    {
        $this->setXp($this->getXp() - $xp);

        return $this;
    }

    public function getXpTotal(): int|float
    {
        $total = 0;
        foreach ($this->getExperienceGains() as $gain) {
            $pos = strpos((string) $gain->getExplanation(), 'Suppression de la compétence');
            if (false === $pos) {
                $total += $gain->getXpGain();
            }
        }

        return $total;
    }

    /**
     * Ajoute des points d'héroisme à un personnage.
     *
     * @param unknown $heroisme
     */
    public function addHeroisme($heroisme): static
    {
        $this->setHeroisme($this->getHeroisme() + $heroisme);

        return $this;
    }

    /**
     * Ajoute des points de pugilat à un personnage.
     *
     * @param unknown $pugilat
     */
    public function addPugilat($pugilat): static
    {
        $this->setPugilat($this->getPugilat() + $pugilat);

        return $this;
    }

    /**
     * Ajoute des points de renomme à un personnage.
     *
     * @return Personnage
     */
    public function addRenomme(int $renomme): static
    {
        $this->setRenomme($this->getRenomme() + $renomme);

        return $this;
    }

    /**
     * Retire des points de renomme à un personnage.
     *
     * @return Personnage
     */
    public function removeRenomme(int $renomme): static
    {
        $this->setRenomme($this->getRenomme() - $renomme);

        return $this;
    }

    /**
     * Recupère le nom de classe genrifié du personnage.
     *
     * @todo : Evoluer vers un modèle de données ou les libélés de ressource sont variable en fonction du genre
     */
    public function getClasseName()
    {
        $lGenreMasculin = true;
        if (null != $this->getGenre()) {
            $lGenreMasculin = 'Masculin' == $this->getGenre()->getLabel();
        }

        if (null == $this->getClasse()) {
            return '';
        } elseif ($lGenreMasculin) {
            return $this->getClasse()->getLabelMasculin();
        } else {
            return $this->getClasse()->getLabelFeminin();
        }
    }

    /**
     * Retire un personnage d'un groupe.
     */
    public function removeGroupe(Groupe $groupe): void
    {
        $groupe->removePersonnage($this);
        $this->setGroupe(null);
    }

    public function getResumeParticipations(): string
    {
        $s = '';
        if (!$this->getVivant()) {
            $s = '💀 ';
        }

        $s .= $this->getFullName();

        if ($this->getUser()) {
            $first = true;
            foreach ($this->getUser()->getParticipants() as $participant) {
                if (null != $participant->getPersonnage() && $participant->getPersonnage()->getId() == $this->getId()) {
                    if ($first) {
                        $s .= ' [';
                        $first = false;
                    }

                    $s = $s.' '.$participant->getGn()->getLabel();
                }
            }

            if (!$first) {
                $s .= ' ]';
            }
        }

        return $s.' - '.$this->getClasseName();
    }

    /**
     * Retourne le dernier participant du personnage.
     */
    public function getLastParticipant(): ?Participant
    {
        if (!$this->getParticipants()->isEmpty()) {
            return $this->getParticipants()->last();
        }

        return null;
    }

    /**
     * Retourne true si le dernier participant du personnage est sur un gn actif et a un billet.
     */
    public function isLastParticipantOnActiveGn(): bool
    {
        $lastParticipant = $this->getLastParticipant();

        return $lastParticipant
            && $lastParticipant->getGn()
            && $lastParticipant->getGn()->getActif()
            && $lastParticipant->getBillet();
    }

    /**
     * Retourne le gn du dernier participant du personnage.
     */
    public function getLastParticipantGn(): ?Gn
    {
        $lastParticipant = $this->getLastParticipant();
        if ($lastParticipant instanceof Participant) {
            return $lastParticipant->getGn();
        }

        return null;
    }

    /**
     * Retourne le numéro du gn du dernier participant du personnage.
     */
    public function getLastParticipantGnNumber(): int
    {
        $lastParticipantGn = $this->getLastParticipantGn();
        if ($lastParticipantGn instanceof Gn) {
            return $lastParticipantGn->getNumber();
        }

        return 0;
    }

    /**
     * Retourne le groupe du gn du dernier participant du personnage, s'il est bien présent.
     */
    public function getLastParticipantGnGroupe(): ?Groupe
    {
        $lastParticipant = $this->getLastParticipant();
        if ($lastParticipant instanceof Participant) {
            $lastParticipantGn = $lastParticipant->getGn();
            $lastParticipantGroupeGn = $lastParticipant->getGroupeGn();
            if (!empty($lastParticipantGroupeGn)
                && $lastParticipantGn->getLabel() == $lastParticipantGroupeGn->getGn()->getLabel()) {
                return $lastParticipantGroupeGn->getGroupe();
            }
        }

        return null;
    }

    /**
     * Retourne le nom du groupe du gn du dernier participant du personnage, s'il est bien présent
     * Si pas defini, renvoie 'N\'est pas lié à un groupe''.
     */
    public function getLastParticipantGnGroupeNom(): string
    {
        $lastParticipantGnGroupe = $this->getLastParticipantGnGroupe();

        return $lastParticipantGnGroupe instanceof Groupe
            ? $lastParticipantGnGroupe->getNom()
            : 'N\'est pas lié à un groupe';
    }

    /**
     * Indique si le dernier participant était un PNJ ou non.
     */
    public function isPnj(): bool
    {
        $lastParticipant = $this->getLastParticipant();
        if ($lastParticipant instanceof Participant) {
            return $lastParticipant->isPnj();
        }

        return false;
    }

    /**
     * Retourne le nom complet de l'utilisateur (nom prénom).
     */
    public function getUserFullName(): ?string
    {
        if ($this->getUser()) {
            return $this->getUser()->getFullName();
        }

        return null;
    }

    /**
     * Retourne le nom complet du personnage.
     */
    public function getFullName(): string
    {
        return $this->getNom().(empty($this->getSurnom()) ? '' : ' ('.$this->getSurnom().')');
    }

    /**
     * Retourne true si le personnage a au moins une anomalie.
     */
    public function hasAnomalie(): bool
    {
        return
            !empty($this->getLanguesAnomaliesMessage())
            || !empty($this->getPotionAnomalieMessage())
            || !empty($this->getSortAnomalieMessage())
            || !empty($this->getPrieresAnomalieMessage());
    }

    /**
     * Retourne le statut suivant d'un joueur sous forme entier :
     * 0 = Mort
     * 1 = PJ vivant
     * 2 = PNJ vivant.
     */
    public function getStatusCode(): int
    {
        return $this->getVivant()
            ? ($this->isPnj() ? 2 : 1)
            : 0;
    }

    /**
     * Retourne le statut lié au gn actif d'un joueur sous forme entier :
     * 0 = Mort
     * 1 = PJ vivant ne participant pas au gn actif
     * 2 = PNJ vivant
     * 3 = PJ vivant participant au gn actif.
     */
    public function getStatusOnActiveGnCode(): int
    {
        return $this->getVivant()
            ? ($this->isPnj()
                ? 2
                : ($this->isLastParticipantOnActiveGn()
                    ? 3
                    : 1)
            )
            : 0;
    }

    /**
     * Retourne le statut d'un joueur sous forme entier prenant en compte le numéro du dernier GN participé :
     * -1 = PNJ
     * 0 = mort
     * 1 .. N = numéro du dernier GN auquel.
     */
    public function getStatusGnCode(): int
    {
        if (!$this->getVivant()) {
            return 0;
        }

        if ($this->isPnj()) {
            return -1;
        }

        return $this->getLastParticipantGnNumber();
    }

    /**
     * Vérifie si le personnage connait cette technologie.
     */
    public function isKnownTechnologie(Technologie $t): bool
    {
        foreach ($this->getTechnologies() as $technologie) {
            if ($technologie == $t) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le personnage connait ce document.
     */
    public function isKnownDocument(Document $d): bool
    {
        foreach ($this->getDocuments() as $document) {
            if ($document == $d) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si le personnage est sensible.
     *
     * @return bool
     */
    public function isSensible()
    {
        $User = $this->getUser();
        if (!$User) {
            return $this->getSensible();
        }

        if ($User->getAgeJoueur() < 18) {
            return true;
        } else {
            return $this->getSensible();
        }
    }

    /**
     * Retourne le score d'energie vitale.
     */
    public function getEnergieVitale(): int
    {
        $User = $this->getUser();
        if (!$User) {
            return 1;
        }

        if ($User->getAgeJoueur() < 18) {
            return 0;
        }

        return 1;
    }
}
