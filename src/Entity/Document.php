<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\DocumentRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Entity(repositoryClass: DocumentRepository::class)]
class Document extends BaseDocument implements \Stringable
{
    use EntityFileUploadTrait;

    private ?string $label = null;

    public function __construct()
    {
        parent::__construct();
        $this->initFile();
    }

    public function initFile(): static
    {
        $this->setDocumentType(DocumentType::Document)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4)
            ->setCreationDate(new \DateTime('now'))
            ->setUpdateDate(new \DateTime('now'))
            ->setImpression(false);

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getIdentity();
    }

    /**
     * Fourni l'identité d'un document (code + titre).
     */
    public function getIdentity(): string
    {
        return $this->getCode().' '.$this->getTitre();
    }

    /**
     * Fourni la description du document au bon format pour impression.
     */
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }

    protected function afterUpload(FileUploader $fileUploader): FileUploader
    {
        $this->setDocumentUrl($fileUploader->getStoredFileName());

        return $fileUploader;
    }

    public function getDocument(?string $projectDir = null): string
    {
        return $this->getDocumentFilePath($projectDir).$this->getDocumentUrl();
    }

    public function getDocumentUrl(): string
    {
        return parent::getDocumentUrl() ?: $this->getFilename() ?: $this->getPrintLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel() ?: $this->getFilename() ?: $this->getCode() ?: time());
        //return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->filename ?: $this->getCode()));
    }

    public function getLabel(): string
    {
        return $this->label ?: $this->getIdentity() ?: '';
    }

    public function setLabel(?string $label): Document
    {
        $this->label = $label;

        return $this;
    }

    public function getOldV1Document(?string $projectDir = null): string
    {
        return $this->getDocumentFilePath($projectDir, true).$this->getDocumentUrl();
    }
}
