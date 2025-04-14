<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\CompetenceRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: CompetenceRepository::class)]
class Competence extends BaseCompetence implements \Stringable
{
    use EntityFileUploadTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initFile();
    }

    public function initFile(): static
    {
        $this->setDocumentType(DocumentType::Documents)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getPersonnagesGn(int $gnId): Collection
    {
        $liste = new ArrayCollection();
        foreach ($this->getPersonnages() as $personnage) {
            foreach ($personnage->getParticipants() as $participant) {
                $gn = $participant->getGn();
                if ($gn && $gn->getId() === $gnId) {
                    $liste[] = $participant;
                }
            }
        }

        return $liste;
    }

    public function getLabel(): string
    {
        return $this->getCompetenceFamily()?->getLabel().' - '.$this->getLevel()?->getLabel();
    }

    // TODO use a StringHelper
    public function getPrintLabel(): array|string|null
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }

    // TODO use a StringHelper
    public function getMaterielRaw(): string
    {
        return html_entity_decode(strip_tags($this->getMateriel()));
    }

    // TODO use a StringHelper
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }

    /**
     * Fourni la compétence de niveau immédiatement supérieur appartenant à la même famille de compétence.
     */
    public function getNext(): ?Competence
    {
        $competenceFamily = $this->getCompetenceFamily();
        $levelIndex = $this->getLevel()?->getIndex();

        $competences = $competenceFamily->getCompetences();
        $nextCompetences = new ArrayCollection();

        foreach ($competences as $competence) {
            if ($competence->getLevel()?->getIndex() > $levelIndex) {
                $nextCompetences->add($competence);
            }
        }

        $minimumIndex = null;
        $competenceFirst = null;
        foreach ($nextCompetences as $competence) {
            if (null === $minimumIndex) {
                $competenceFirst = $competence;
                $minimumIndex = $competence->getLevel()->getIndex();
            } elseif ($competence->getLevel()->getIndex() < $minimumIndex) {
                $competenceFirst = $competence;
                $minimumIndex = $competence->getLevel()->getIndex();
            }
        }

        return $competenceFirst;
    }

    // TODO : use __toString call ?
    public function getCompetenceAttributesAsString(): string
    {
        $r = '';
        foreach ($this->getCompetenceAttributes() as $attribute) {
            $r .= $attribute->getAttributeTypeId().':'.$attribute->getValue().';';
        }

        return $r;
    }

    public function findAttributeByTypeId($typeId)
    {
        foreach ($this->getCompetenceAttributes() as $attr) {
            if ($attr->getAttributeTypeId() === $typeId) {
                return $attr;
            }
        }

        return null;
    }

    public function setCompetenceAttributesAsString(?int $value, $ormEm, $attributeRepos): static
    {
        $keepTypeIds = [];
        if (null !== $value) {
            $entries = explode(';', $value, 30);

            foreach ($entries as $entry) {
                $arrayIdValue = explode(':', $entry, 2);
                if (2 !== \count($arrayIdValue)) {
                    continue;
                }

                $typeId = (int) $arrayIdValue[0];
                $value = (int) $arrayIdValue[1];

                $keepTypeIds[] = $typeId;
                $attr = $this->findAttributeByTypeId($typeId);
                if (null === $attr) {
                    $attr = new CompetenceAttribute();
                    $attr->setCompetence($this);
                    $attr->setCompetenceId($this->id);
                    $attr->setAttributeTypeId($typeId);
                    $attr->setAttributeType($attributeRepos->find($typeId));
                    $this->addCompetenceAttribute($attr);
                }

                $attr->setValue($value);
            }
        }

        // Si $value est null => $keepTypeIds est vide, on va donc tout supprimer.

        foreach ($this->getCompetenceAttributes() as $attr) {
            if (\in_array($attr->getAttributeTypeId(), $keepTypeIds, true)) {
                continue;
            }

            $attributeType = $attr->getAttributeType();
            $attributeType->removeCompetenceAttribute($attr);
            $this->removeCompetenceAttribute($attr);
            $ormEm->remove($attr);
        }

        return $this;
    }

    public function getAttributeValue($key)
    {
        foreach ($this->getCompetenceAttributes() as $attr) {
            if ($attr->getAttributeType()->getLabel() === $key) {
                return $attr->getValue();
            }
        }

        return null;
    }

    public function getDocument(string $projectDir): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        $this->setDocumentUrl($fileUploader->getStoredFileName());

        return $fileUploader;
    }
}
