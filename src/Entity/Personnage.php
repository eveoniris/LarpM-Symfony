<?php

namespace App\Entity;

use App\Enum\CompetenceFamilyType;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Enum\LevelType;
use App\Repository\PersonnageRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: PersonnageRepository::class)]
class Personnage extends BasePersonnage implements \Stringable
{
    use EntityFileUploadTrait;

    #[Assert\File(['maxSize' => 6000000])]
    #[Assert\Image(
        minWidth: 200,
        minHeight: 200,
    )]
    protected ?UploadedFile $file;

    // For some FormBuilder search
    public Personnage $personnageChoosen;
    protected bool $isCreation = false;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setXp(0);
        $this->setVivant(true);
        $this->initFile();
    }

    public function initFile(): self
    {
        $this->setDocumentType(DocumentType::Photos)
            ->setFolderType(FolderType::Trombine)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);

        return $this;
    }

    /**
     * Affichage.
     */
    public function __toString(): string
    {
        return (string) $this->getPublicName();
    }

    public function handleUpload(
        FileUploader $fileUploader,
        DocumentType $docType = DocumentType::Photos,
        FolderType $folderType = FolderType::Trombine,
    ): void {
        // la propriété « file » peut être vide si le champ n'est pas requis
        if (empty($this->file)) {
            return;
        }

        $fileUploader->upload($this->file, $folderType, $docType, null, 70);

        // Try Rezise
        try {
            $image = (new Imagine())->open($fileUploader->getStoredFileWithPath());
            $image->resize($image->getSize()->widen(480));
            $image->save($fileUploader->getStoredFileWithPath());
        } catch (\RuntimeException $e) {
            dump($e);
        }

        $this->setTrombineUrl($fileUploader->getStoredFileName());

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;
    }

    public function getTrombine(string $projectDir): string
    {
        if (!isset($this->documentType)) {
            $this->initFile();
        }

        return $this->getDocumentFilePath($projectDir).$this->getTrombineUrl();
    }

    public function getFilename(): ?string
    {
        return $this->getTrombineUrl();
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
     * Ajoute des points d'héroisme à un personnage.
     *
     * @param unknown $heroisme
     */
    public function addHeroisme(int $heroisme): static
    {
        $this->setHeroisme($this->getHeroisme() + $heroisme);

        return $this;
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
     * Ajoute des points de pugilat à un personnage.
     *
     * @param unknown $pugilat
     */
    public function addPugilat(int $pugilat): static
    {
        $this->setPugilat($this->getPugilat() + (int) $pugilat);

        return $this;
    }

    /**
     * Fourni le score de pugilat.
     */
    public function getPugilat(): int|float
    {
        if (isset($this->pugilat)) {
            return $this->pugilat;
        }

        $this->pugilat = 1
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
            $this->pugilat += 5;
        }

        // Sauvagerie au niveau Initié ajoute 5 points
        if ($this->getCompetenceNiveau('Sauvagerie') >= 2) {
            $this->pugilat += 5;
        }

        foreach ($this->getPugilatHistories() as $pugilatHistory) {
            $this->pugilat += $pugilatHistory->getPugilat();
        }

        return $this->pugilat;
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

    public function getCompetenceLevel(Competence $competence): Level
    {
        return $competence->getLevel();
    }

    public function getCompetenceTypeLevel(string $type): ?Level
    {
        $level = null;

        if (!$famillyType = CompetenceFamilyType::getFromLabel($type)) {
            return $level;
        }

        $niveau = 0;
        foreach ($this->getCompetencesFromFamilyType($famillyType) as $competence) {
            $index = $competence->getLevel()?->getIndex();

            if (null === $level || $niveau < (int) $index) {
                $niveau = (int) $index;
                $level = $competence->getLevel();
            }
        }

        return $level;
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
            $heroismeHistory->setExplication(
                'Compétence Armes à 1 main niveau '.$this->getCompetenceNiveau('Armes à 1 main')
            );
            $heroismeHistories[] = $heroismeHistory;
        }

        if ($this->getCompetenceNiveau('Armes à 2 mains') >= 2) {
            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme(1);
            $heroismeHistory->setExplication(
                'Compétence Armes à 2 mains niveau '.$this->getCompetenceNiveau('Armes à 2 mains')
            );
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
            $pugilatHistory->setExplication(
                'Compétence Armes à distance niveau '.$this->getCompetenceNiveau('Armes à distance')
            );
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armes à 1 main') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armes à 1 main'));
            $pugilatHistory->setExplication(
                'Compétence Armes à 1 main niveau '.$this->getCompetenceNiveau('Armes à 1 main')
            );
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat('Armes à 2 mains') > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat('Armes à 2 mains'));
            $pugilatHistory->setExplication(
                'Compétence Armes à 2 mains niveau '.$this->getCompetenceNiveau('Armes à 2 mains')
            );
            $pugilatHistories[] = $pugilatHistory;
        }

        if ($this->getCompetencePugilat("Armes d'hast") > 0) {
            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat($this->getCompetencePugilat("Armes d'hast"));
            $pugilatHistory->setExplication(
                'Compétence Armes d\'hast niveau '.$this->getCompetenceNiveau("Armes d'hast")
            );
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
            $pugilatHistory->setExplication(
                'Compétence Attaque sournoise niveau '.$this->getCompetenceNiveau('Attaque sournoise')
            );
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
     * Fourni le niveau d'une compétence d'un personnage.
     */
    public function getCompetenceNiveau(string $label): int
    {
        if ($type = CompetenceFamilyType::getFromLabel($label)) {
            $competences = $this->getCompetencesFromFamilyType($type);
        } else {
            $competences = $this->getCompetencesByFamilyLabel($label);
        }

        $niveau = 0;
        foreach ($competences as $competence) {
            $niveau = max($niveau, $competence->getLevel()?->getIndex());
        }

        return $niveau;
    }

    public function getCompetencesByFamilyLabel(string $label): array
    {
        $competences = [];
        try {
            foreach ($this->getCompetences() as $competence) {
                if ($competence->getLabel() === $label) {
                    $competences[] = $competence;
                }
            }
        } catch (\Exception $e) {
            // LOG $e ?
        }

        return $competences;
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

    /**
     * Fourni l'identité complete d'un personnage.
     */
    public function getIdentity(bool $withId = true, bool $full = false): string
    {
        if ($full && $participant = $this->getParticipants()->last()) {
            $groupeLabel = $participant?->getGroupeGn()?->getGroupe()?->getNom();

            return sprintf(
                '%s (%s)',
                $withId ? $this->getIdName() : $this->getPublicName(),
                $participant?->getGn()?->getLabel().($groupeLabel ? ' - ' : '').$groupeLabel
            );
        }

        return $withId ? $this->getIdName() : $this->getNameSurname();
    }

    public function getLigneeIdentity(bool $withId = true, bool $full = false): string
    {
        return $this->getIdentity($withId, $full).' ('.$this->getAge()->getLabel().')';
    }

    public function getIdName(): string
    {
        return $this->getId().' - '.$this->getNameSurname();
    }

    public function getNameSurname(): string
    {
        return $this->getNom().(empty(trim($this->getSurnom())) ? '' : ' - ').$this->getSurnom();
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

    public function getFirstParticipantGnGroupe(): ?Groupe
    {
        $participant = $this->getFirstParticipant();
        if ($participant?->getGn()?->getLabel() === $participant?->getGroupeGn()?->getGn()?->getLabel()) {
            return $participant?->getGroupeGn()?->getGroupe();
        }

        return null;
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

    public function getFirstParticipant(): ?Participant
    {
        if (!$this->getParticipants()->isEmpty()) {
            return $this->getParticipants()->first();
        }

        return null;
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
     * Fourni l'origine du personnage.
     */
    public function getOrigine()
    {
        return $this->getTerritoire();
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
     * Retourne le nom complet du personnage.
     */
    public function getFullName(): string
    {
        return $this->getNom().(empty($this->getSurnom()) ? '' : ' ('.$this->getSurnom().')');
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
     * Fourni la liste des groupes secondaires pour lesquel ce personnage est chef.
     */
    public function getSecondaryGroupsAsChief()
    {
        return $this->getSecondaryGroups();
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
     * Retourne le nom complet de l'utilisateur (nom prénom).
     */
    public function getUserFullName(): ?string
    {
        if ($this->getUser()) {
            return $this->getUser()->getFullName();
        }

        return null;
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
     * Retourne les anomalies entre le nombre de langues autorisées et le nombre de langues affectées.
     *
     * @return Collection|null
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

    public function hasCompetenceLevel(CompetenceFamilyType $type, Level|LevelType $level): bool
    {
        $index = $level->getIndex();

        foreach ($this->getCompetencesFromFamilyType($type) as $competence) {
            if ($competence?->getLevel()?->getIndex() === $index) {
                return true;
            }
        }

        return false;
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

    public function hasReligionId(int $religionId, ?int $levelId = null): bool
    {
        /** @var PersonnagesReligions $religion */
        foreach ($this->getPersonnagesReligions() as $religion) {
            if ($religion->getReligion()?->getId() === $religionId) {
                if (null === $levelId) {
                    return true;
                }

                if ((int) $religion->getReligionLevel()?->getId() === $levelId) {
                    return true;
                }
            }
        }

        return false;
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

    public function isCreation(): bool
    {
        return $this->isCreation;
    }

    public function setIsCreation(bool $isCreation): void
    {
        $this->isCreation = $isCreation;
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

    public function isPriest(): bool
    {
        return $this->hasCompetence(CompetenceFamilyType::PRIESTHOOD);
    }

    /**
     * Vérifie si le personnage dispose d'une compétence (quelque soit son niveau).
     */
    public function hasCompetence(string|CompetenceFamilyType $type): bool
    {
        if (!$type instanceof CompetenceFamilyType) {
            $type = CompetenceFamilyType::getFromLabel($type);
        }

        if (!$type) {
            return false;
        }

        return null !== $this->getCompetencesFromFamilyType($type);
    }

    public function hasCompetenceId(int $id): bool
    {
        try {
            foreach ($this->getCompetences() as $competence) {
                if ($competence->getId() === $id) {
                    return true;
                }
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @return Competence[]
     */
    public function getCompetencesFromFamilyType(CompetenceFamilyType $type): array
    {
        $competences = [];
        try {
            foreach ($this->getCompetences() as $competence) {
                if ($competence->getCompetenceFamily()?->getId() === $type->getId()) {
                    $competences[] = $competence;
                }
            }
        } catch (\Exception $e) {
            // LOG $e ?
        }

        return $competences;
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
     * Retire un personnage d'un groupe.
     */
    public function removeGroupe(Groupe $groupe): void
    {
        $groupe->removePersonnage($this);
        $this->setGroupe(null);
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
     * Retire des points d'expérience à un personnage.
     *
     * @param int $xp
     */
    public function removeXp($xp): static
    {
        $this->setXp($this->getXp() - $xp);

        return $this;
    }

    /**
     * Retire le personnage de son groupe.
     */
    public function setGroupeNull(): static
    {
        $this->groupe = null;

        return $this;
    }

    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel());
    }
}
