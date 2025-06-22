<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\ReligionRepository;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Entity(repositoryClass: ReligionRepository::class)]
class Religion extends BaseReligion implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getBlasonFileUrl()
    {
        // TODO params ?
        return 'img/religions/'.$this->getBlason();
    }

    /**
     * Fourni la liste des territoires ou la religion est la religion principale.
     */
    public function getTerritoirePrincipaux()
    {
        return $this->getTerritoires();
    }

    public function hasStl(): bool
    {
        // dump((new AsciiSlugger())->slug($this->getLabel()).'.stl');

        return file_exists('../'.$this->getStl()->getDocument());
    }

    public function getStl(): Document
    {
        $document = new Document();

        $document->setDocumentType(DocumentType::Religion3D);
        $document->setFolderType(FolderType::Photos);
        $document->setFilename((new AsciiSlugger())->slug($this->getLabel()).'.stl');
        $document->setLabel($this->getLabel());
        $document->setFilenameMaxLength(45);
        $document->setDocumentExtension('.stl');
        $document->setDocumentMimeType('model/stl');

        return $document;
    }

    public function isSans(): bool
    {
        return 'sans' === strtolower($this->getLabel());
    }

    public function isSecret(): bool
    {
        return (bool) $this->getSecret();
    }
}
