<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Repository\DocumentRepository;
use App\Service\FileUploader;
use App\Trait\EntityFileUploadTrait;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: DocumentRepository::class)]
class Document extends BaseDocument implements \Stringable
{
    use EntityFileUploadTrait;

    public function __toString(): string
    {
        return (string) $this->getIdentity();
    }

    public function __construct()
    {
        parent::__construct();
        $this->setDocumentType(DocumentType::Documents)
            ->setFolderType(FolderType::Private)
            // DocumentUrl is set to 45 maxLength, UniqueId is 23 length, extension is 4
            ->setFilenameMaxLength(45 - 24 - 4)
            ->setCreationDate(new \DateTime('now'))
            ->setUpdateDate(new \DateTime('now'))
            ->setImpression(false);
    }

    /**
     * Fourni la description du document au bon format pour impression.
     */
    public function getDescriptionRaw(): string
    {
        return html_entity_decode(strip_tags($this->getDescription()));
    }

    public function getLabel()
    {
        return $this->getIdentity();
    }

    /**
     * Fourni l'identité d'un document (code + titre).
     */
    public function getIdentity(): string
    {
        return $this->getCode().' '.$this->getTitre();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getCode()));
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
